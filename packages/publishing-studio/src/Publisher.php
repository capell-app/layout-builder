<?php

declare(strict_types=1);

namespace Capell\PublishingStudio;

use Capell\Core\Contracts\Pageable;
use Capell\PublishingStudio\Actions\InvalidatePublishedWorkspaceFrontendCacheAction;
use Capell\PublishingStudio\Checks\PublishCheckPipeline;
use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Enums\WorkspaceTransitionEnum;
use Capell\PublishingStudio\Events\WorkspaceEventDispatcher;
use Capell\PublishingStudio\Events\WorkspaceStateChanged;
use Capell\PublishingStudio\Exceptions\EmbargoActiveException;
use Capell\PublishingStudio\Exceptions\PublishBlockedByChecksException;
use Capell\PublishingStudio\Exceptions\ReleaseWindowClosedException;
use Capell\PublishingStudio\Exceptions\StaleWorkspaceException;
use Capell\PublishingStudio\Exceptions\UrlCollisionException;
use Capell\PublishingStudio\Models\Version;
use Capell\PublishingStudio\Models\Workspace;
use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
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
        bool $bypassChecks = false,
    ): Version {
        if ($workspace->status !== WorkspaceStatusEnum::Approved
            && $workspace->status !== WorkspaceStatusEnum::Scheduled) {
            throw new LogicException(sprintf(
                'Workspace #%d must be approved before publish. Current status: %s.',
                $workspace->id,
                $workspace->status->value,
            ));
        }

        if ($workspace->embargo_until !== null && $workspace->embargo_until->isFuture()) {
            throw new EmbargoActiveException($workspace, $workspace->embargo_until);
        }

        if (! $bypassChecks) {
            $pipeline = resolve(PublishCheckPipeline::class);
            $checkResults = $pipeline->run($workspace);
            throw_if($pipeline->hasBlockingErrors($checkResults), PublishBlockedByChecksException::class, $checkResults);
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

        $publishedModelIds = [];

        $version = DB::transaction(function () use ($workspace, $publishedBy, $versionName, $notes, $makeLive, $currentLive, &$publishedModelIds): Version {
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

                if (! DB::getSchemaBuilder()->hasTable($table)) {
                    continue;
                }

                if (! $this->tableHasColumn($table, 'workspace_id')) {
                    continue;
                }

                $hasUuid = in_array('uuid', $modelInstance->getFillable(), true)
                    || $this->tableHasColumn($table, 'uuid');

                $workspaceRows = $modelClass::query()
                    ->withoutGlobalScopes()
                    ->where('workspace_id', $workspace->id)
                    ->get();

                if ($modelInstance instanceof Pageable) {
                    $publishedModelIds[$modelClass] = array_merge(
                        $publishedModelIds[$modelClass] ?? [],
                        array_map(intval(...), $workspaceRows->modelKeys()),
                    );
                }

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

        (new InvalidatePublishedWorkspaceFrontendCacheAction)->handle($publishedModelIds);

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
        $checkPipeline = resolve(PublishCheckPipeline::class);
        $checkResults = $checkPipeline->run($workspace);

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
                checkResults: $checkResults,
            );
        }

        $failure = null;
        $wouldPublish = false;

        try {
            DB::transaction(function () use ($workspace, &$wouldPublish): void {
                $this->publish($workspace, bypassChecks: true);

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
            checkResults: $checkResults,
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

        return DB::table('page_urls as workspace_urls')
            ->join('page_urls as live_urls', function (JoinClause $join): void {
                $join->on('live_urls.site_id', '=', 'workspace_urls.site_id')
                    ->on('live_urls.language_id', '=', 'workspace_urls.language_id')
                    ->on('live_urls.url', '=', 'workspace_urls.url')
                    ->where('live_urls.workspace_id', 0)
                    ->whereNull('live_urls.deleted_at');
            })
            ->where('workspace_urls.workspace_id', $workspace->id)
            ->whereNull('workspace_urls.deleted_at')
            ->where(function (Builder $query): void {
                $query->whereNull('workspace_urls.pageable_type')
                    ->orWhereNull('workspace_urls.pageable_id')
                    ->orWhereColumn('live_urls.pageable_type', '!=', 'workspace_urls.pageable_type')
                    ->orWhereColumn('live_urls.pageable_id', '!=', 'workspace_urls.pageable_id');
            })
            ->distinct()
            ->get(['workspace_urls.site_id', 'workspace_urls.language_id', 'workspace_urls.url'])
            ->map(static fn (object $collision): array => [
                'site_id' => (int) $collision->site_id,
                'language_id' => (int) $collision->language_id,
                'url' => (string) $collision->url,
            ])
            ->all();
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
            $table = (new $modelClass)->getTable();

            if (! DB::getSchemaBuilder()->hasTable($table)) {
                continue;
            }

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
