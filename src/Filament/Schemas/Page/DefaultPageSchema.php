<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Schemas\Page;

use Capell\Layout\Filament\Components\Forms\Page\Tab\PageLayoutTab;
use Capell\Layout\Filament\Resources\PageResource\RelationManagers\ContentsRelationManager;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationGroup;
use Illuminate\Database\Eloquent\Model;

class DefaultPageSchema extends \Capell\Admin\Filament\Schemas\Page\DefaultPageSchema
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

    protected static function getTabs(Forms\Form $form): array
    {
        return [
            PageLayoutTab::make(),
            ...parent::getTabs($form),
        ];
    }
}
