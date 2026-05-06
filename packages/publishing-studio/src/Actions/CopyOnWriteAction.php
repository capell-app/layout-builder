<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Actions;

use Capell\PublishingStudio\Models\Workspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Clones a live row into a workspace as part of the copy-on-write flow.
 *
 * Called from the {@see BelongsToWorkspace} trait's
 * `saving` and `deleting` hooks whenever an editor mutates a live row inside
 * an active workspace context. The action perform-builder three DB operations:
 *
 *   1. Clone the live row into a new row scoped to the workspace, carrying
 *      across the editor's in-memory dirty attributes so the edit lands on
 *      the clone rather than the live record.
 *   2. Stamp `shadowed_by_workspace_id` on the live row (direct update, no
 *      model events) so the workspace scope hides live behind the clone.
 *   3. For deletes, soft-delete the freshly cloned workspace row so publish
 *      carries the deletion through.
 *
 * Shadow maintenance is a direct DB update on purpose: it keeps observer /
 * activitylog noise off the live row (editors haven't touched live — they
 * only branched off it) and it means the action is reentrancy-safe from
 * inside model events.
 */
final readonly class CopyOnWriteAction
{
    /**
     * Clone a live row into the workspace, carrying dirty attributes across.
     * Returns the persisted workspace-scoped clone so callers can compare
     * primary keys or continue work with the new record.
     *
     * @template TModel of Model
     *
     * @param  TModel  $liveRecord
     * @return TModel
     */
    public function cloneForEdit(Model $liveRecord, Workspace $workspace): Model
    {
        return DB::transaction(function () use ($liveRecord, $workspace): Model {
            $this->guardLive($liveRecord);
            $this->guardNotShadowedByAnotherWorkspace($liveRecord, $workspace);

            $dirtyAttributes = $liveRecord->getDirty();

            $excludeFromClone = array_filter(
                array_keys($liveRecord->getAttributes()),
                static fn (string $attribute): bool => str_ends_with($attribute, '_count'),
            );
            $clone = $liveRecord->replicate($excludeFromClone);
            $clone->setAttribute('workspace_id', $workspace->id);
            $clone->setAttribute('shadowed_by_workspace_id', 0);

            // Merge dirty attributes via setRawAttributes to avoid double-encoding:
            // getDirty() returns raw stored values (e.g. JSON strings for json casts),
            // and setAttribute() would re-apply the cast, corrupting already-encoded values.
            $filteredDirty = array_diff_key(
                $dirtyAttributes,
                ['workspace_id' => null, 'shadowed_by_workspace_id' => null],
            );

            if ($filteredDirty !== []) {
                $clone->setRawAttributes(array_merge($clone->getAttributes(), $filteredDirty));
            }

            // Preserve the live row's primary-key-linked uuid when present so the
            // publisher can match the clone back to its live counterpart on flip.
            if (array_key_exists('uuid', $liveRecord->getAttributes())
                && $clone->getAttribute('uuid') === null
                && $liveRecord->getAttribute('uuid') !== null) {
                $clone->setAttribute('uuid', $liveRecord->getAttribute('uuid'));
            }

            $clone->save();

            $this->stampShadow($liveRecord, $workspace);

            return $clone;
        });
    }

    /**
     * Clone a live row into the workspace as a soft-deleted tombstone so
     * the deletion is carried through on publish. The caller must ensure
     * the model uses {@see SoftDeletes}; a hard-delete of a live row from
     * inside a workspace context is never allowed — it would mutate live
     * data out-of-band.
     *
     * @template TModel of Model
     *
     * @param  TModel  $liveRecord
     * @return TModel
     */
    public function cloneForDelete(Model $liveRecord, Workspace $workspace): Model
    {
        return DB::transaction(function () use ($liveRecord, $workspace): Model {
            $this->guardLive($liveRecord);
            $this->guardSoftDeletes($liveRecord);
            $this->guardNotShadowedByAnotherWorkspace($liveRecord, $workspace);

            $clone = $liveRecord->replicate();
            $clone->setAttribute('workspace_id', $workspace->id);
            $clone->setAttribute('shadowed_by_workspace_id', 0);

            if (array_key_exists('uuid', $liveRecord->getAttributes())
                && $liveRecord->getAttribute('uuid') !== null) {
                $clone->setAttribute('uuid', $liveRecord->getAttribute('uuid'));
            }

            $clone->save();
            $clone->delete();

            $this->stampShadow($liveRecord, $workspace);

            return $clone;
        });
    }

    /**
     * Reset the shadow flag on a live row — used when a workspace is
     * abandoned, rebased, or when the workspace-scoped clone is itself
     * destroyed without publishing.
     */
    public function clearShadow(Model $liveRecord, Workspace $workspace): void
    {
        DB::table($liveRecord->getTable())
            ->where($liveRecord->getKeyName(), $liveRecord->getKey())
            ->where('workspace_id', 0)
            ->where('shadowed_by_workspace_id', $workspace->id)
            ->update(['shadowed_by_workspace_id' => 0]);
    }

    private function stampShadow(Model $liveRecord, Workspace $workspace): void
    {
        DB::table($liveRecord->getTable())
            ->where($liveRecord->getKeyName(), $liveRecord->getKey())
            ->where('workspace_id', 0)
            ->update(['shadowed_by_workspace_id' => $workspace->id]);

        $liveRecord->setAttribute('shadowed_by_workspace_id', $workspace->id);
        $liveRecord->syncOriginalAttribute('shadowed_by_workspace_id');
    }

    private function guardNotShadowedByAnotherWorkspace(Model $liveRecord, Workspace $workspace): void
    {
        $query = DB::table($liveRecord->getTable())
            ->where($liveRecord->getKeyName(), $liveRecord->getKey())
            ->where('workspace_id', 0);

        if (DB::connection()->getDriverName() !== 'sqlite') {
            $query->lockForUpdate();
        }

        $shadowingWorkspaceId = (int) ($query->value('shadowed_by_workspace_id') ?? 0);

        if ($shadowingWorkspaceId !== 0 && $shadowingWorkspaceId !== $workspace->id) {
            throw new LogicException(sprintf(
                '%s#%s is already shadowed by workspace #%d; workspace #%d cannot draft it concurrently.',
                $liveRecord::class,
                (string) $liveRecord->getKey(),
                $shadowingWorkspaceId,
                $workspace->id,
            ));
        }
    }

    private function guardLive(Model $record): void
    {
        if ((int) $record->getAttribute('workspace_id') !== 0) {
            throw new LogicException(sprintf(
                'CopyOnWriteAction expected a live row (workspace_id = 0); got workspace_id=%s for %s#%s.',
                (string) $record->getAttribute('workspace_id'),
                $record::class,
                (string) $record->getKey(),
            ));
        }
    }

    private function guardSoftDeletes(Model $record): void
    {
        $traitNames = array_map(
            static fn (string $fqcn): string => ltrim($fqcn, '\\'),
            class_uses_recursive($record),
        );

        if (! in_array(SoftDeletes::class, $traitNames, true)) {
            throw new LogicException(sprintf(
                'Cannot copy-on-write delete %s inside a workspace: the model does not use SoftDeletes, '
                . 'so the workspace tombstone cannot be represented. Delete from live (null context) instead.',
                $record::class,
            ));
        }
    }
}
