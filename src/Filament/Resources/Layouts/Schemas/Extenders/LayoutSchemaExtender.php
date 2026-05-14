<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Layouts\Schemas\Extenders;

use Capell\Admin\Contracts\Extenders;
use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Filament\Components\Forms\Layout\LayoutTab;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class LayoutSchemaExtender implements Extenders\LayoutSchemaExtender
{
    public function extendRelationManagers(Model $record, array $relationManagers): array
    {
        return $relationManagers;
    }

    public function extendTabs(Schema $configurator, array $tabs): array
    {
        $record = $configurator->getRecord();

        if ($record instanceof Layout && data_get($record->admin ?? [], 'disable_layout_builder') === true) {
            return $tabs;
        }

        $hasLayoutTab = collect($tabs)->contains(fn (mixed $tab): bool => $tab instanceof LayoutTab);

        if (! $hasLayoutTab) {
            array_unshift($tabs, LayoutTab::make());
        }

        return $tabs;
    }
}
