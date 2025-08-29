<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Contents\RelationManagers;

use Capell\Admin\Filament\Concerns\HideEmptyRelationManager;
use Capell\Admin\Filament\RelationManagers\AbstractPagesRelationManager;
use Capell\Layout\Models\Content;
use Filament\Tables\Table;

/**
 * @property Content $ownerRecord
 */
class PagesRelationManager extends AbstractPagesRelationManager
{
    use HideEmptyRelationManager;

    protected function getDescription(Table $table): ?string
    {
        return __('capell-admin::generic.content_pages_info', ['total' => $table->getQuery()->count()]);
    }
}
