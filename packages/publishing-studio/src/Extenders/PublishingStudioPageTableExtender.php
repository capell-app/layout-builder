<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Extenders;

use Capell\Admin\Contracts\Extenders\PageTableExtender;
use Capell\PublishingStudio\WorkspaceContextScope;
use Illuminate\Database\Eloquent\Builder;

class PublishingStudioPageTableExtender implements PageTableExtender
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
