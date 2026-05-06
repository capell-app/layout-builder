<?php

declare(strict_types=1);

namespace Capell\PublishingStudio;

use Capell\PublishingStudio\Models\Version;
use Capell\PublishingStudio\Models\Workspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Re-aligns a stale workspace with the current live version.
 *
 * A workspace becomes stale whenever *another* workspace publishes while
 * this one is still open. Rebasing:
 *
 *  1. Fast-forwards `base_version_id` to point at the current live version.
 *  2. Returns a diagnostic report of records whose live counterpart has
 *     changed since the workspace forked (uuid matches, last-updated differs).
 *
 * The rebaser does NOT automatically merge field changes — that's a UI-level
 * decision. Its job is to surface the set of possibly-conflicting records so
 * the editor can keep, rebase, or re-edit each one via the Filament diff view.
 */
final readonly class Rebaser
{
    public function __construct(private WorkspaceRegistry $registry = new WorkspaceRegistry) {}

    /**
     * Produce a diagnostic report for the workspace against current live.
     */
    public function analyse(Workspace $workspace): RebaseReport
    {
        $currentLive = Version::currentLive();
        $report = new RebaseReport(
            workspace: $workspace,
            currentLiveVersionId: $currentLive?->id,
            conflicts: [],
        );

        if (! $currentLive instanceof Version
            || $workspace->base_version_id === null
            || $workspace->base_version_id >= $currentLive->id) {
            return $report;
        }

        foreach (array_keys($this->registry::all()) as $modelClass) {
            $modelInstance = new $modelClass;
            $table = $modelInstance->getTable();

            if (! $this->tableHasColumn($table, 'uuid')) {
                continue;
            }

            $workspaceUuids = $modelClass::query()
                ->withoutGlobalScopes()
                ->where('workspace_id', $workspace->id)
                ->whereNotNull('uuid')
                ->pluck('uuid')
                ->all();

            if ($workspaceUuids === []) {
                continue;
            }

            $liveUpdates = DB::table($table)
                ->where('workspace_id', 0)
                ->whereIn('uuid', $workspaceUuids)
                ->where('updated_at', '>', $workspace->updated_at)
                ->pluck('uuid')
                ->all();

            foreach ($liveUpdates as $uuid) {
                $report->addConflict($modelClass, (string) $uuid);
            }
        }

        return $report;
    }

    /**
     * Apply reviewer-chosen resolutions for the uuids surfaced by
     * {@see self::analyse()}. Choice values:
     *
     *   - `keep-mine` — leave the workspace-scoped row in place.
     *   - `take-live` — discard the workspace copy so live shines through.
     *
     * After applying choices the caller is expected to re-run
     * {@see self::analyse()} to confirm a clean state and then
     * {@see self::fastForward()} to move the base pointer.
     *
     * @param  array<class-string<Model>, array<string, string>>  $choices
     */
    public function resolve(Workspace $workspace, array $choices): void
    {
        DB::transaction(function () use ($workspace, $choices): void {
            foreach ($choices as $modelClass => $perUuid) {
                foreach ($perUuid as $uuid => $choice) {
                    if ($choice === 'keep-mine') {
                        continue;
                    }

                    if ($choice === 'take-live') {
                        $modelClass::query()
                            ->withoutGlobalScopes()
                            ->where('workspace_id', $workspace->id)
                            ->where('uuid', $uuid)
                            ->delete();

                        continue;
                    }

                    throw new InvalidArgumentException(sprintf(
                        'Unknown rebase choice "%s" for %s:%s. Expected "keep-mine" or "take-live".',
                        $choice,
                        $modelClass,
                        $uuid,
                    ));
                }
            }
        });
    }

    /**
     * Fast-forward the workspace's base_version_id to current live. Safe to
     * call after the editor has resolved conflicts via the UI; does not
     * itself mutate any draftable records.
     */
    public function fastForward(Workspace $workspace): Workspace
    {
        $currentLive = Version::currentLive();

        if ($currentLive instanceof Version) {
            $workspace->base_version_id = $currentLive->id;
            $workspace->save();
        }

        return $workspace;
    }

    private function tableHasColumn(string $table, string $column): bool
    {
        return DB::getSchemaBuilder()->hasColumn($table, $column);
    }
}
