<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\ContentResource\RelationManagers;

use Capell\Admin\Filament\Concerns\HideEmptyRelationManager;
use Capell\Admin\Filament\RelationManagers\AbstractPagesRelationManager;

/**
 * @property \Capell\Layout\Models\Content $ownerRecord
 */
class PagesRelationManager extends AbstractPagesRelationManager
{
    use HideEmptyRelationManager;

    protected function getTableDescription(): ?string
    {
        return __('capell-admin::generic.content_pages_info', ['total' => $this->getTable()->getQuery()->count()]);
    }
}
