<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Pages\Schemas\Types;

use Capell\Layout\Filament\Components\Forms\Page\Tab\PageLayoutTab;
use Capell\Layout\Filament\Resources\Pages\RelationManagers\ContentsRelationManager;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class DefaultPageSchema extends \Capell\Admin\Filament\Resources\Pages\Schemas\Types\DefaultPageSchema
{
    public static function relationManagers(Model $record): array
    {
        return [
            ...parent::relationManagers($record),
            RelationGroup::make(__('Relations'), [
                ContentsRelationManager::class,
            ]),
        ];
    }

    protected static function getTabs(Schema $schema): array
    {
        return [
            PageLayoutTab::make(),
            ...parent::getTabs($schema),
        ];
    }
}
