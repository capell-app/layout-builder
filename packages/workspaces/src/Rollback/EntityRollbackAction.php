<?php

declare(strict_types=1);

namespace Capell\Workspaces\Rollback;

use Capell\Core\Models\Version;
use Capell\Workspaces\Events\VersionRolledBack;
use Capell\Workspaces\Exceptions\EntityNotInVersionException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Rolls back a single entity (identified by uuid) to the row that was live
 * at a target {@see Version}, leaving every other entity untouched.
 *
 * Strategy: locate the id in the target version's manifest whose row has
 * the requested uuid. Promote that row back to live (workspace_id=0,
 * un-soft-deleted), and delete any sibling rows at live with the same
 * uuid so there is exactly one live record for the entity.
 *
 * Intentionally narrow — bundled-level rollback lives in
 * {@see Rollback}. Callers that need a bundle
 * rollback should use that class instead.
 */
class EntityRollbackAction
{
    /**
     * @param  class-string<Model>  $modelClass
     */
    public function handle(
        string $modelClass,
        string $entityUuid,
        Version $targetVersion,
        ?Authenticatable $actor = null,
        ?string $reason = null,
    ): EntityRollbackReport {
        if ($targetVersion->published_at === null) {
            throw new LogicException(sprintf(
                'Version #%d cannot be used as a rollback target: it was never published.',
                $targetVersion->id,
            ));
        }

        $targetIds = $targetVersion->manifestIdsFor($modelClass);
        if ($targetIds === []) {
            throw EntityNotInVersionException::missing($modelClass, $entityUuid, $targetVersion->id);
        }

        $targetRow = $modelClass::query()
            ->withoutGlobalScopes()
            ->whereIn((new $modelClass)->getKeyName(), $targetIds)
            ->where('uuid', $entityUuid)
            ->first();

        if (! $targetRow instanceof Model) {
            throw EntityNotInVersionException::missing($modelClass, $entityUuid, $targetVersion->id);
        }

        $report = DB::transaction(function () use ($modelClass, $entityUuid, $targetRow, $targetVersion): EntityRollbackReport {
            $prototype = new $modelClass;
            $keyName = $prototype->getKeyName();
            $usesSoftDeletes = $this->usesSoftDeletes($prototype);

            $currentLive = $modelClass::query()
                ->withoutGlobalScopes()
                ->where('workspace_id', 0)
                ->where('uuid', $entityUuid)
                ->where($keyName, '!=', $targetRow->getKey())
                ->first();

            if ($targetRow->getAttribute('workspace_id') === 0
                && ($usesSoftDeletes ? $targetRow->getAttribute('deleted_at') : null) === null
                && ! $currentLive instanceof Model) {
                return new EntityRollbackReport(
                    modelClass: $modelClass,
                    entityUuid: $entityUuid,
                    targetVersionId: $targetVersion->id,
                    restoredId: (int) $targetRow->getKey(),
                    replacedId: null,
                    noOp: true,
                );
            }

            if ($currentLive instanceof Model) {
                if ($usesSoftDeletes) {
                    $modelClass::query()
                        ->withoutGlobalScopes()
                        ->whereKey($currentLive->getKey())
                        ->forceDelete();
                } else {
                    $modelClass::query()
                        ->withoutGlobalScopes()
                        ->whereKey($currentLive->getKey())
                        ->delete();
                }
            }

            $targetRow->forceFill([
                'workspace_id' => 0,
                'shadowed_by_workspace_id' => 0,
            ]);

            if ($usesSoftDeletes && ($usesSoftDeletes ? $targetRow->getAttribute('deleted_at') : null) !== null) {
                $targetRow->setAttribute('deleted_at', null);
            }

            $targetRow->save();

            return new EntityRollbackReport(
                modelClass: $modelClass,
                entityUuid: $entityUuid,
                targetVersionId: $targetVersion->id,
                restoredId: (int) $targetRow->getKey(),
                replacedId: $currentLive?->getKey() === null ? null : (int) $currentLive->getKey(),
            );
        });

        if (! $report->noOp) {
            event(new VersionRolledBack(
                version: $targetVersion,
                rolledBackTo: $targetVersion,
                previousLiveVersion: null,
                actor: $actor,
                reason: $reason,
            ));
        }

        return $report;
    }

    private function usesSoftDeletes(Model $record): bool
    {
        $traitNames = array_map(
            static fn (string $fqcn): string => ltrim($fqcn, '\\'),
            class_uses_recursive($record),
        );

        return in_array(SoftDeletes::class, $traitNames, true);
    }
}
