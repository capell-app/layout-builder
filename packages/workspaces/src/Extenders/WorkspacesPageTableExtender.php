<?php

declare(strict_types=1);

namespace Capell\Workspaces\Extenders;

use Capell\Admin\Contracts\Extenders\PageTableExtender;
use Capell\Workspaces\WorkspaceContextScope;
use Illuminate\Database\Eloquent\Builder;

class WorkspacesPageTableExtender implements PageTableExtender
{
    public function getColumns(): array
    {
        return [];
    }

    public function getBulkActions(): array
    {
        return [];
    }

    public function getFilters(): array
    {
        return [];
    }

    public function modifyQuery(Builder $query): Builder
    {
        return $query->withoutGlobalScope(WorkspaceContextScope::class);
    }
}
