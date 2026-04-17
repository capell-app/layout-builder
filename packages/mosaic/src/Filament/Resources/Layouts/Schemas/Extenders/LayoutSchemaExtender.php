<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Resources\Layouts\Schemas\Extenders;

use Capell\Admin\Contracts\Extenders;
use Capell\Mosaic\Filament\Components\Forms\Layout\LayoutTab;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class LayoutSchemaExtender implements Extenders\LayoutSchemaExtender
{
    public function extendRelationManagers(Model $record, array $relationManagers): array
    {
        return [];
    }

    public function extendTabs(Schema $schema, array $tabs): array
    {
        $hasLayoutTab = collect($tabs)->contains(fn (Tab $tab): bool => $tab instanceof LayoutTab);

        if (! $hasLayoutTab) {
            array_unshift($tabs, LayoutTab::make());
        }

        return $tabs;
    }
}
