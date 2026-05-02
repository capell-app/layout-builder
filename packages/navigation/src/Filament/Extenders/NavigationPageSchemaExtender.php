<?php

declare(strict_types=1);

namespace Capell\Navigation\Filament\Extenders;

use Capell\Admin\Contracts\Extenders\PageSchemaExtender;
use Capell\Admin\Enums\PageTranslationSchemaHookEnum;
use Capell\Navigation\Filament\Components\Forms\Page\Tab\NavigationTab;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class NavigationPageSchemaExtender implements PageSchemaExtender
{
    public function extendTabs(Schema $configurator, array $tabs): array
    {
        $tabs[] = NavigationTab::make();

        return $tabs;
    }

    public function extendRelationManagers(Model $record, array $relationManagers): array
    {
        return $relationManagers;
    }

    public function extendTranslationComponentsForHook(Schema $configurator, PageTranslationSchemaHookEnum $hook): array
    {
        return [];
    }

    public function extendSettingsTabComponents(): array
    {
        return [];
    }
}
