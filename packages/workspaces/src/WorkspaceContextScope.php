<?php

declare(strict_types=1);

namespace Capell\Workspaces;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global query scope applied to every model using {@see BelongsToWorkspace}.
 *
 * Behavior:
 *  - When {@see WorkspaceContext::current()} is `null` → filter to live rows
 *    only (`workspace_id = 0`). The live read path is a single-column indexed
 *    predicate: the hot path frontend visitors hit stays fast.
 *  - When a workspace is active → return the workspace's own rows PLUS any
 *    live rows that are NOT shadowed by a row inside this workspace. The
 *    `shadowed_by_workspace_id` flag is maintained on live rows by the
 *    observers on copy-on-write, so no UNION / no sub-select is required —
 *    the two predicates are each covered by an index.
 *
 * Rationale: frontend performance is the primary concern. Keeping live reads
 * to a single indexed predicate and admin reads to two indexed predicates
 * (both on the same table, OR'd) is materially cheaper than UNION / window
 * functions that would be needed to "prefer workspace row if it exists".
 */
final class WorkspaceContextScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! $this->modelHasWorkspaceColumns($model)) {
            return;
        }

        $activeWorkspaceId = WorkspaceContext::currentId();
        $workspaceColumn = $model->qualifyColumn('workspace_id');
        $shadowedColumn = $model->qualifyColumn('shadowed_by_workspace_id');

        if ($activeWorkspaceId === null) {
            $builder->where($workspaceColumn, 0);

            return;
        }

        $builder->where(
            static function (Builder $inner) use ($workspaceColumn, $shadowedColumn, $activeWorkspaceId): void {
                // The workspace's own edited rows.
                $inner->where($workspaceColumn, $activeWorkspaceId)
                    // ...plus live rows that this workspace has not shadowed.
                    ->orWhere(
                        static function (Builder $liveBranch) use ($workspaceColumn, $shadowedColumn, $activeWorkspaceId): void {
                            $liveBranch->where($workspaceColumn, 0)
                                ->where($shadowedColumn, '!=', $activeWorkspaceId);
                        },
                    );
            },
        );
    }

    private function modelHasWorkspaceColumns(Model $model): bool
    {
        $schema = $model->getConnection()->getSchemaBuilder();
        $table = $model->getTable();

        return $schema->hasColumn($table, 'workspace_id')
            && $schema->hasColumn($table, 'shadowed_by_workspace_id');
    }
}
