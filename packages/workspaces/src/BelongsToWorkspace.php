<?php

declare(strict_types=1);

namespace Capell\Workspaces;

use Capell\Core\Models\Workspace;
use Capell\Workspaces\Actions\CopyOnWriteAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Gives an Eloquent model workspace awareness:
 *
 *  - A `workspace()` relation back to the Workspace model (`workspace_id = 0`
 *    resolves to null — the sentinel for "live").
 *  - Local scopes for filtering by live / workspace / combined context.
 *  - Automatic application of {@see WorkspaceContextScope} as a global scope,
 *    so every query a model issues respects the active workspace context.
 *
 * The trait intentionally does NOT perform copy-on-write. Copy-on-write is
 * handled explicitly by the code paths that edit records (observers and
 * Filament actions) to keep the rule transparent and testable.
 *
 * @phpstan-require-extends Model
 */
trait BelongsToWorkspace
{
    public static function bootBelongsToWorkspace(): void
    {
        static::addGlobalScope(new WorkspaceContextScope);

        static::creating(static function (Model $record): void {
            $activeWorkspaceId = WorkspaceContext::currentId();

            if ($activeWorkspaceId === null) {
                return;
            }

            // New records authored inside a workspace context are stamped with
            // the workspace id up-front; copy-on-write only applies to edits of
            // pre-existing live rows.
            $currentWorkspaceId = $record->getAttribute('workspace_id');
            if ($currentWorkspaceId === null || (int) $currentWorkspaceId === 0) {
                $record->setAttribute('workspace_id', $activeWorkspaceId);
            }
        });

        static::saving(static function (Model $record): ?bool {
            $activeWorkspace = WorkspaceContext::current();

            if (! $activeWorkspace instanceof Workspace) {
                return null;
            }

            // Only existing live rows need copy-on-write. Fresh records were
            // already stamped in the `creating` hook; workspace-scoped rows
            // save normally inside their own context.
            if (! $record->exists) {
                return null;
            }

            if ((int) $record->getAttribute('workspace_id') !== 0) {
                return null;
            }

            if (! $record->isDirty()) {
                return null;
            }

            // Fork: clone live into the workspace, carry dirty attrs across,
            // stamp shadow on live, cancel this save so the live DB row is
            // left untouched. Returning `false` halts the event chain and
            // aborts the save on the original live row; returning `null` lets
            // other listeners (e.g. nested-set) continue.
            (new CopyOnWriteAction)->cloneForEdit($record, $activeWorkspace);

            return false;
        });

        static::deleting(static function (Model $record): ?bool {
            $activeWorkspace = WorkspaceContext::current();

            if (! $activeWorkspace instanceof Workspace) {
                return null;
            }

            if (! $record->exists) {
                return null;
            }

            if ((int) $record->getAttribute('workspace_id') !== 0) {
                return null;
            }

            (new CopyOnWriteAction)->cloneForDelete($record, $activeWorkspace);

            return false;
        });
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function isLive(): bool
    {
        return $this->getAttribute('workspace_id') === 0;
    }

    public function isInWorkspace(): bool
    {
        return (int) $this->getAttribute('workspace_id') > 0;
    }

    protected function scopeLive(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('workspace_id'), 0);
    }

    protected function scopeInWorkspace(Builder $query, Workspace|int $workspace): Builder
    {
        $workspaceId = $workspace instanceof Workspace ? $workspace->id : $workspace;

        return $query->where($this->qualifyColumn('workspace_id'), $workspaceId);
    }

    protected function scopeForContext(Builder $query, Workspace|int|null $workspace): Builder
    {
        $workspaceColumn = $this->qualifyColumn('workspace_id');

        if ($workspace === null) {
            return $query->where($workspaceColumn, 0);
        }

        $workspaceId = $workspace instanceof Workspace ? $workspace->id : $workspace;
        $shadowedColumn = $this->qualifyColumn('shadowed_by_workspace_id');

        // Same shape as WorkspaceContextScope: workspace rows + live rows not
        // shadowed by this workspace. Two indexed predicates, no union.
        return $query->where(
            static function (Builder $inner) use ($workspaceColumn, $shadowedColumn, $workspaceId): void {
                $inner->where($workspaceColumn, $workspaceId)
                    ->orWhere(
                        static function (Builder $liveBranch) use ($workspaceColumn, $shadowedColumn, $workspaceId): void {
                            $liveBranch->where($workspaceColumn, 0)
                                ->where($shadowedColumn, '!=', $workspaceId);
                        },
                    );
            },
        );
    }

    /**
     * Escape hatch: return a query that ignores the workspace context scope
     * entirely. Use for publisher/rebaser code that must see live + every
     * workspace at once.
     */
    protected function scopeWithoutWorkspaceScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope(WorkspaceContextScope::class);
    }
}
