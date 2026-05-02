<?php

declare(strict_types=1);

namespace Capell\Workspaces\Concerns;

use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\WorkspaceContext;
use Illuminate\Database\Eloquent\Builder;

/**
 * Provides uniform active-context filtering for dashboard widget queries.
 *
 * The admin panel does not maintain a single "active site" or "active language"
 * session context — users may manage multiple sites simultaneously. Widgets that
 * need to restrict to a specific site should override `activeSite()` and supply
 * one (e.g. from a widget property set by the user).
 *
 * `activeWorkspace()` delegates to the process-local `WorkspaceContext` singleton,
 * which is set by `ResolveWorkspaceContext` middleware on every request.
 *
 * `scopeToActive()` skips any scoping dimension for which the active value is null
 * or for which the underlying model table lacks the corresponding column — so this
 * trait is safe to use with models that do not participate in workspaces or
 * multi-site.
 *
 * `scopeToActive()` applies site + workspace scoping. Language-dimension
 * scoping is not applied here — widgets that need it should filter
 * explicitly off `activeLanguage()`.
 */
trait ScopedToActiveContext
{
    /** @var array<string, bool> */
    private static array $columnCache = [];

    /**
     * Return the currently active site, or null if no site is selected.
     *
     * The admin panel does not maintain a global "active site" context; this
     * method returns null by default. Widgets that should be scoped to a single
     * site should override this method.
     */
    protected function activeSite(): ?Site
    {
        return null;
    }

    /**
     * Return the workspace currently active for this request, or null for live.
     *
     * Delegates to the `WorkspaceContext` process-local singleton, which is
     * populated by `ResolveWorkspaceContext` middleware on every request.
     */
    protected function activeWorkspace(): ?Workspace
    {
        return WorkspaceContext::current();
    }

    /**
     * Return the currently active language, or null if none is selected.
     *
     * The admin panel does not maintain a global "active language" context; this
     * method returns null by default. Widgets that should be scoped to a single
     * language should override this method.
     */
    protected function activeLanguage(): ?Language
    {
        return null;
    }

    /**
     * Apply active site and workspace scoping to the given query.
     *
     * Scoping is skipped when:
     * - The corresponding context value is null (no active site / workspace).
     * - The model's table does not have the `site_id` or `workspace_id` column.
     *
     * This ensures the trait degrades gracefully on models that do not
     * participate in multi-site or workspace workflows.
     */
    protected function scopeToActive(Builder $query): Builder
    {
        $site = $this->activeSite();

        if ($site instanceof Site && $this->modelHasColumn($query, 'site_id')) {
            $query->where($query->getModel()->getTable() . '.site_id', $site->id);
        }

        $workspace = $this->activeWorkspace();

        if ($workspace instanceof Workspace && $this->modelHasColumn($query, 'workspace_id')) {
            $query->where($query->getModel()->getTable() . '.workspace_id', $workspace->id);
        }

        return $query;
    }

    private function modelHasColumn(Builder $query, string $column): bool
    {
        $model = $query->getModel();
        $table = $model->getTable();
        $connectionName = $model->getConnectionName() ?? 'default';
        $cacheKey = $connectionName . ':' . $table . ':' . $column;

        if (array_key_exists($cacheKey, self::$columnCache)) {
            return self::$columnCache[$cacheKey];
        }

        return self::$columnCache[$cacheKey] = $model->getConnection()
            ->getSchemaBuilder()
            ->hasColumn($table, $column);
    }
}
