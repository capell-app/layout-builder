<?php

declare(strict_types=1);

namespace Capell\Workspaces;

use Capell\Workspaces\Enums\WorkspaceStatusEnum;
use Capell\Workspaces\Enums\WorkspaceTransitionEnum;
use Capell\Workspaces\Events\WorkspaceEventDispatcher;
use Capell\Workspaces\Events\WorkspaceStateChanged;
use Capell\Workspaces\Exceptions\ReleaseWindowClosedException;
use Capell\Workspaces\Exceptions\StaleWorkspaceException;
use Capell\Workspaces\Exceptions\UrlCollisionException;
use Capell\Workspaces\Models\Version;
use Capell\Workspaces\Models\Workspace;
use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use Throwable;

/**
 * Atomically publishes a workspace into a new live Version.
 *
 * Steps, all inside a single DB transaction:
 *
 *   1. Assert the workspace is approved and not stale.
 *   2. Collect every workspace-scoped record across registered draftable models.
 *   3. URL collision precheck against live page_urls.
 *   4. Delete the live rows that share a `uuid` with workspace rows (drafts
 *      that are replacing live). Models without `uuid` are replaced by
 *      primary key.
 *   5. Flip `workspace_id` from the workspace's id to 0 on every remaining
 *      workspace record, making them live.
 *   6. Create a new Version row, set is_live, demote the previous live.
 *   7. Update the workspace status to Published.
 *
 * On any exception the transaction rolls back and no live data has changed.
 */
class Publisher
{
    public function __construct(private readonly WorkspaceRegistry $registry = new WorkspaceRegistry) {}

    public function publish(
        Workspace $workspace,
        ?Authenticatable $publishedBy = null,
        ?string $versionName = null,
        ?string $notes = null,
        bool $makeLive = true,
        bool $bypassWindow = false,
    ): Version {
        if ($workspace->status !== WorkspaceStatusEnum::Approved
            && $workspace->status !== WorkspaceStatusEnum::Scheduled) {
            throw new LogicException(sprintf(
                'Workspace #%d must be approved before publish. Current status: %s.',
                $workspace->id,
                $workspace->status->value,
            ));
        }

        /** @var WorkspaceEventDispatcher $dispatcher */
        $dispatcher = resolve(WorkspaceEventDispatcher::class);

        // Dispatch beforePublish event
        throw_unless($dispatcher->beforePublish($workspace), Exception::class, 'Publish prevented by subscriber');

        if (! $bypassWindow) {
            $windowGuard = new ReleaseWindowGuard;
            if (! $windowGuard->isOpen()) {
                throw new ReleaseWindowClosedException($workspace, $windowGuard->nextOpensAt());
            }
        }

        $currentLive = Version::currentLive();
        if ($currentLive instanceof Version
            && $workspace->base_version_id !== null
            && $workspace->base_version_id < $currentLive->id) {
            throw new StaleWorkspaceException($workspace, $currentLive->id);
        }

        $previousStatus = $workspace->status;

        $version = DB::transaction(function () use ($workspace, $publishedBy, $versionName, $notes, $makeLive, $currentLive): Version {
            $lockedLiveId = $this->lockCurrentLiveVersionId();

            throw_if($lockedLiveId !== ($currentLive instanceof Version ? $currentLive->id : null), StaleWorkspaceException::class, $workspace, (int) $lockedLiveId);

            throw_if($workspace->base_version_id !== null
                && $lockedLiveId !== null
                && $workspace->base_version_id < $lockedLiveId, StaleWorkspaceException::class, $workspace, $lockedLiveId);

            $workspace->status = WorkspaceStatusEnum::Publishing;
            $workspace->save();

            $this->assertNoUrlCollisions($workspace);

            $manifest = [];

            foreach ($this->registry::all() as $modelClass => $registeredDraftable) {
                $modelInstance = new $modelClass;
                $table = $modelInstance->getTable();
                $hasUuid = in_array('uuid', $modelInstance->getFillable(), true)
                    || $this->tableHasColumn($table, 'uuid');

                $workspaceRows = $modelClass::query()
                    ->withoutGlobalScopes()
                    ->where('workspace_id', $workspace->id)
                    ->get();

                foreach ($workspaceRows as $workspaceRow) {
                    $registeredDraftable->finalizeOnPublish($workspaceRow);

                    if ($hasUuid && $workspaceRow->getAttribute('uuid') !== null) {
                        $modelClass::query()
                            ->withoutGlobalScopes()
                            ->where('workspace_id', 0)
                            ->where('uuid', $workspaceRow->getAttribute('uuid'))
                            ->delete();
                    }
                }

                $modelClass::query()
                    ->withoutGlobalScopes()
                    ->where('workspace_id', $workspace->id)
                    ->update(['workspace_id' => 0]);

                $liveIds = $modelClass::query()
                    ->withoutGlobalScopes()
                    ->where('workspace_id', 0)
                    ->pluck($modelInstance->getKeyName())
                    ->all();

                $manifest[$modelClass] = array_map(intval(...), $liveIds);
            }

            if ($makeLive && $currentLive instanceof Version) {
                $currentLive->is_live = false;
                $currentLive->save();
            }

            $newVersion = Version::query()->create([
                'uuid' => (string) Str::uuid(),
                'number' => (int) (Version::query()->max('number') ?? 0) + 1,
                'name' => $versionName ?? $workspace->name,
                'notes' => $notes,
                'is_live' => $makeLive,
                'manifest' => $manifest,
                'source_workspace_id' => $workspace->id,
                'published_by_type' => $publishedBy?->getMorphClass(),
                'published_by_id' => $publishedBy?->getKey(),
                'published_at' => now(),
            ]);

            $workspace->status = WorkspaceStatusEnum::Published;
            $workspace->published_at = now();
            $workspace->save();

            return $newVersion;
        });

        // Dispatch afterPublish event
        $dispatcher->afterPublish($workspace);

        event(new WorkspaceStateChanged($workspace, $previousStatus, $workspace->status, WorkspaceTransitionEnum::Published->value, $publishedBy, $notes));

        return $version;
    }

    /**
     * Execute the full publish pipeline inside a transaction that is always
     * rolled back. Returns a {@see DryRunReport} describing what would
     * happen: whether the workspace is stale, the rebase conflict set, URL
     * collisions, and per-model row counts. Any exception raised during the
     * simulated publish is captured on the report rather than surfacing.
     */
    public function dryRun(Workspace $workspace): DryRunReport
    {
        $rebaseReport = (new Rebaser($this->registry))->analyse($workspace);
        $collisions = $this->detectUrlCollisions($workspace);
        $rowCounts = $this->countWorkspaceRows($workspace);

        if ($workspace->status !== WorkspaceStatusEnum::Approved
            && $workspace->status !== WorkspaceStatusEnum::Scheduled) {
            return new DryRunReport(
                workspace: $workspace,
                wouldPublish: false,
                rebaseReport: $rebaseReport,
                collisions: $collisions,
                rowCounts: $rowCounts,
                failure: new LogicException(sprintf(
                    'Workspace #%d is not approved (current status: %s).',
                    $workspace->id,
                    $workspace->status->value,
                )),
            );
        }

        $failure = null;
        $wouldPublish = false;

        try {
            DB::transaction(function () use ($workspace, &$wouldPublish): void {
                $this->publish($workspace);

                $wouldPublish = true;

                throw new DryRunRollback;
            });
        } catch (DryRunRollback) {
            // Intentional rollback — everything the simulated publish did is
            // now undone and $wouldPublish reflects that the pipeline ran
            // to completion.
        } catch (Throwable $exception) {
            $failure = $exception;
        }

        return new DryRunReport(
            workspace: $workspace,
            wouldPublish: $wouldPublish,
            rebaseReport: $rebaseReport,
            collisions: $collisions,
            rowCounts: $rowCounts,
            failure: $failure,
        );
    }

    /**
     * Return the list of page_urls rows that would collide with existing live
     * rows if the workspace's workspace_id columns were flipped to 0. A
     * collision is any (site_id, language_id, url) tuple that exists in both
     * live (excluding the rows we're about to delete) AND the workspace.
     *
     * @return array<int, array{site_id: int, language_id: int, url: string}>
     */
    public function detectUrlCollisions(Workspace $workspace): array
    {
        if (! $this->tableHasColumn('page_urls', 'workspace_id')) {
            return [];
        }

        $workspaceUrls = DB::table('page_urls')
            ->select(['site_id', 'language_id', 'url', 'pageable_type', 'pageable_id'])
            ->where('workspace_id', $workspace->id)
            ->whereNull('deleted_at')
            ->get();

        if ($workspaceUrls->isEmpty()) {
            return [];
        }

        $collisions = [];

        foreach ($workspaceUrls as $workspaceUrl) {
            $liveExists = DB::table('page_urls')
                ->where('workspace_id', 0)
                ->where('site_id', $workspaceUrl->site_id)
                ->where('language_id', $workspaceUrl->language_id)
                ->where('url', $workspaceUrl->url)
                ->whereNull('deleted_at')
                ->when(
                    $workspaceUrl->pageable_type !== null && $workspaceUrl->pageable_id !== null,
                    fn (Builder $query): Builder => $query->where(function (Builder $inner) use ($workspaceUrl): void {
                        $inner->where('pageable_type', '!=', $workspaceUrl->pageable_type)
                            ->orWhere('pageable_id', '!=', $workspaceUrl->pageable_id);
                    }),
                )
                ->exists();

            if ($liveExists) {
                $collisions[] = [
                    'site_id' => (int) $workspaceUrl->site_id,
                    'language_id' => (int) $workspaceUrl->language_id,
                    'url' => (string) $workspaceUrl->url,
                ];
            }
        }

        return $collisions;
    }

    /**
     * Acquire a row-level lock on the current live version (if any) for the
     * duration of the publish transaction. Returns the live version id, or
     * null if no version is live. SQLite has no row-level locking but the
     * surrounding transaction already serialises writers, so the query is
     * still safe on that driver.
     */
    protected function lockCurrentLiveVersionId(): ?int
    {
        $query = Version::query()->where('is_live', true);

        if (DB::connection()->getDriverName() !== 'sqlite') {
            $query->lockForUpdate();
        }

        $id = $query->value('id');

        return $id === null ? null : (int) $id;
    }

    private function assertNoUrlCollisions(Workspace $workspace): void
    {
        $collisions = $this->detectUrlCollisions($workspace);

        throw_if($collisions !== [], UrlCollisionException::class, $workspace, $collisions);
    }

    /**
     * @return array<class-string<Model>, int>
     */
    private function countWorkspaceRows(Workspace $workspace): array
    {
        $counts = [];

        foreach (array_keys($this->registry::all()) as $modelClass) {
            $count = $modelClass::query()
                ->withoutGlobalScopes()
                ->where('workspace_id', $workspace->id)
                ->count();

            if ($count > 0) {
                $counts[$modelClass] = $count;
            }
        }

        return $counts;
    }

    private function tableHasColumn(string $table, string $column): bool
    {
        return DB::getSchemaBuilder()->hasColumn($table, $column);
    }
}
