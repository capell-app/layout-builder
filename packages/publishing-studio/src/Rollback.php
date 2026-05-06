<?php

declare(strict_types=1);

namespace Capell\PublishingStudio;

use Capell\PublishingStudio\Events\VersionRolledBack;
use Capell\PublishingStudio\Exceptions\StaleWorkspaceException;
use Capell\PublishingStudio\Models\Version;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

/**
 * Perform-builder an emergency rollback: promotes a previously-published
 * {@see Version} back to live. Intended for incident response when a
 * recent publish broke production.
 *
 * Steps, inside a single DB transaction with a row-level lock on the
 * live versions row:
 *
 *   1. Assert the target is a real, published, non-live version.
 *   2. Re-lock the current live id and bail if it has shifted since the
 *      target was inspected (prevents two simultaneous rollbacks).
 *   3. For every registered draftable, delete the live rows whose ids
 *      are NOT in the target's manifest — those were added by publishes
 *      that happened after the target and are being un-done.
 *   4. Restore (undelete) rows referenced by the target manifest that
 *      were soft-deleted by later publishes.
 *   5. Flip `is_live` on versions so the target is live again and the
 *      formerly-live version is demoted.
 *   6. Persist a new audit-trail Version row referencing the target via
 *      `rollback_of_version_id`.
 *   7. Dispatch {@see VersionRolledBack} outside the transaction.
 */
final readonly class Rollback
{
    public function __construct(private WorkspaceRegistry $registry = new WorkspaceRegistry) {}

    public function rollbackTo(
        Version $target,
        ?Authenticatable $actor = null,
        ?string $reason = null,
    ): Version {
        if ($target->published_at === null) {
            throw new LogicException(sprintf(
                'Version #%d cannot be used as a rollback target: it was never published.',
                $target->id,
            ));
        }

        $target->refresh();

        if ($target->is_live) {
            throw new LogicException(sprintf(
                'Version #%d is already live — rollback is a no-op.',
                $target->id,
            ));
        }

        $this->assertManifestIsSafeToRollback($target);

        $previousLiveSnapshot = Version::currentLive();
        $previousLiveId = $previousLiveSnapshot?->id;

        $result = DB::transaction(function () use ($target, $actor, $reason, $previousLiveId): array {
            $lockedLiveId = $this->lockCurrentLiveVersionId();

            throw_if($lockedLiveId !== $previousLiveId, StaleWorkspaceException::class, null, $lockedLiveId ?? 0);

            $previousLive = $lockedLiveId === null
                ? null
                : Version::query()->whereKey($lockedLiveId)->first();

            foreach ($this->registry::all() as $modelClass => $registeredDraftable) {
                unset($registeredDraftable);

                $modelInstance = new $modelClass;
                $keyName = $modelInstance->getKeyName();
                $targetIds = $target->manifestIdsFor($modelClass);

                $deleteQuery = $modelClass::query()
                    ->withoutGlobalScopes()
                    ->where('workspace_id', 0);

                if ($targetIds !== []) {
                    $deleteQuery->whereNotIn($keyName, $targetIds);
                }

                if ($this->usesSoftDeletes($modelInstance)) {
                    $deleteQuery->forceDelete();
                } else {
                    $deleteQuery->delete();
                }

                if ($targetIds !== [] && $this->usesSoftDeletes($modelInstance)) {
                    $modelClass::query()
                        ->withoutGlobalScopes()
                        ->where('workspace_id', 0)
                        ->whereIn($keyName, $targetIds)
                        ->whereNotNull('deleted_at')
                        ->update(['deleted_at' => null]);
                }
            }

            if ($previousLive instanceof Version) {
                $previousLive->is_live = false;
                $previousLive->save();
            }

            $target->is_live = true;
            $target->save();

            $rollbackRecord = Version::query()->create([
                'uuid' => (string) Str::uuid(),
                'number' => (int) (Version::query()->max('number') ?? 0) + 1,
                'name' => 'Rollback to ' . ($target->name ?? ('#' . $target->id)),
                'notes' => $reason,
                'is_live' => false,
                'manifest' => $target->manifest,
                'source_workspace_id' => null,
                'rollback_of_version_id' => $target->id,
                'published_by_type' => $actor?->getMorphClass(),
                'published_by_id' => $actor?->getKey(),
                'published_at' => now(),
            ]);

            return [$rollbackRecord, $previousLive];
        });

        [$rollbackRecord, $previousLive] = $result;

        event(new VersionRolledBack(
            version: $rollbackRecord,
            rolledBackTo: $target,
            previousLiveVersion: $previousLive,
            actor: $actor,
            reason: $reason,
        ));

        return $rollbackRecord;
    }

    private function lockCurrentLiveVersionId(): ?int
    {
        $query = Version::query()->where('is_live', true);

        if (DB::connection()->getDriverName() !== 'sqlite') {
            $query->lockForUpdate();
        }

        $id = $query->value('id');

        return $id === null ? null : (int) $id;
    }

    private function usesSoftDeletes(Model $record): bool
    {
        $traitNames = array_map(
            static fn (string $fqcn): string => ltrim($fqcn, '\\'),
            class_uses_recursive($record),
        );

        return in_array(SoftDeletes::class, $traitNames, true);
    }

    private function assertManifestIsSafeToRollback(Version $target): void
    {
        $manifest = $target->manifest;

        if ($manifest === []) {
            throw new LogicException(sprintf(
                'Version #%d cannot be used as a rollback target: it has an empty manifest.',
                $target->id,
            ));
        }

        $registeredModelClasses = array_keys($this->registry::all());
        $missingModelClasses = array_values(array_diff($registeredModelClasses, array_keys($manifest)));

        if ($missingModelClasses !== []) {
            throw new LogicException(sprintf(
                'Version #%d cannot be used as a rollback target: it is missing manifest entries for %s.',
                $target->id,
                implode(', ', $missingModelClasses),
            ));
        }
    }
}
