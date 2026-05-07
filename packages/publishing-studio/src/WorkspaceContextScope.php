<?php

declare(strict_types=1);

namespace Capell\PublishingStudio;

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
    /**
     * @var array<string, bool>
     */
    private static array $workspaceColumnCache = [];

    /**
     * @var array<string, bool>
     */
    private static array $workspaceColumnCachePrimed = [];

    public static function flushWorkspaceColumnCache(): void
    {
        self::$workspaceColumnCache = [];
        self::$workspaceColumnCachePrimed = [];
    }

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
        $connection = $model->getConnection();
        $table = $model->getTable();
        $cacheKey = implode(':', [
            $connection->getName(),
            $connection->getDatabaseName(),
            $table,
        ]);

        if (array_key_exists($cacheKey, self::$workspaceColumnCache)) {
            return self::$workspaceColumnCache[$cacheKey];
        }

        $this->primeWorkspaceColumnCache($model);

        if (array_key_exists($cacheKey, self::$workspaceColumnCache)) {
            return self::$workspaceColumnCache[$cacheKey];
        }

        $schema = $connection->getSchemaBuilder();

        return self::$workspaceColumnCache[$cacheKey] = $schema->hasColumns($table, [
            'workspace_id',
            'shadowed_by_workspace_id',
        ]);
    }

    private function primeWorkspaceColumnCache(Model $model): void
    {
        $connection = $model->getConnection();

        if ($connection->getDriverName() !== 'mysql') {
            return;
        }

        $databaseName = $connection->getDatabaseName();
        $connectionName = $connection->getName();
        $primeKey = $connectionName . ':' . $databaseName;

        if (isset(self::$workspaceColumnCachePrimed[$primeKey])) {
            return;
        }

        self::$workspaceColumnCachePrimed[$primeKey] = true;

        $modelConnectionName = $model->getConnectionName();
        $tablesByCacheKey = [];

        foreach (WorkspaceRegistry::modelClasses() as $modelClass) {
            $candidate = new $modelClass;

            if ($candidate->getConnectionName() !== $modelConnectionName) {
                continue;
            }

            $candidateTable = $candidate->getTable();

            $tablesByCacheKey[$connectionName . ':' . $databaseName . ':' . $candidateTable] = $candidateTable;
        }

        if ($tablesByCacheKey === []) {
            return;
        }

        $columns = $connection->table('information_schema.columns')
            ->select(['table_name', 'column_name'])
            ->where('table_schema', $databaseName)
            ->whereIn('table_name', array_values($tablesByCacheKey))
            ->whereIn('column_name', ['workspace_id', 'shadowed_by_workspace_id'])
            ->get();

        $columnsByTable = [];

        foreach ($columns as $column) {
            $columnsByTable[(string) $column->table_name][(string) $column->column_name] = true;
        }

        foreach ($tablesByCacheKey as $cacheKey => $table) {
            self::$workspaceColumnCache[$cacheKey] = isset(
                $columnsByTable[$table]['workspace_id'],
                $columnsByTable[$table]['shadowed_by_workspace_id'],
            );
        }
    }
}
