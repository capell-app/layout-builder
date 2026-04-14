<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Resources\Tags\RelationManagers;

use Capell\Admin\Filament\RelationManagers\AbstractPagesRelationManager;
use Filament\Tables\Table;

class PagesRelationManager extends AbstractPagesRelationManager
{
    protected function getDescription(Table $table): ?string
    {
        return __('capell-blog::generic.tag_pages_info');
    }
}
