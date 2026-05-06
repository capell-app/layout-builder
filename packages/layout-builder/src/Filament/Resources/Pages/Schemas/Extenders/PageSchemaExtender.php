<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Pages\Schemas\Extenders;

use Capell\Admin\Contracts\Extenders;
use Capell\Admin\Enums\PageTranslationSchemaHookEnum;
use Capell\LayoutBuilder\Filament\Components\Forms\Page\Tab\LayoutTab;
use Capell\LayoutBuilder\Filament\Resources\Pages\RelationManagers\SectionsRelationManager;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class PageSchemaExtender implements Extenders\PageSchemaExtender
{
    public function extendRelationManagers(Model $record, array $relationManagers): array
    {
        $alreadyHasContents = in_array(SectionsRelationManager::class, $relationManagers, true);

        if (! $alreadyHasContents) {
            $relationManagers[] = SectionsRelationManager::class;
        }

        return $relationManagers;
    }

    public function extendTabs(Schema $configurator, array $tabs): array
    {
        $hasLayoutTab = collect($tabs)->contains(fn (Tab $tab): bool => $tab instanceof LayoutTab);

        if (! $hasLayoutTab) {
            array_unshift($tabs, LayoutTab::make());
        }

        return $tabs;
    }

    /**
     * @return array<int, Component>
     */
    public function extendTranslationComponentsForHook(Schema $configurator, PageTranslationSchemaHookEnum $hook): array
    {
        return [];
    }

    /**
     * @return array<int, Component>
     */
    public function extendSettingsTabComponents(): array
    {
        return [];
    }
}
