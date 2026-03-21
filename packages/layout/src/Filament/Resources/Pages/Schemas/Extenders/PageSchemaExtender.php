<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Pages\Schemas\Extenders;

use Capell\Admin\Contracts\Extenders;
use Capell\Admin\Enums\PageTranslationSchemaHookEnum;
use Capell\Layout\Filament\Components\Forms\Page\Tab\PageLayoutTab;
use Capell\Layout\Filament\Resources\Pages\RelationManagers\ContentsRelationManager;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class PageSchemaExtender implements Extenders\PageSchemaExtender
{
    public function extendRelationManagers(Model $record, array $relationManagers): array
    {
        $alreadyHasContents = in_array(ContentsRelationManager::class, $relationManagers, true);

        if (! $alreadyHasContents) {
            $relationManagers[] = ContentsRelationManager::class;
        }

        return $relationManagers;
    }

    public function extendTabs(Schema $schema, array $tabs): array
    {
        $hasLayoutTab = collect($tabs)->contains(fn (Tab $tab): bool => $tab instanceof PageLayoutTab);

        if (! $hasLayoutTab) {
            array_unshift($tabs, PageLayoutTab::make());
        }

        return $tabs;
    }

    /**
     * @return array<int, Component>
     */
    public function extendTranslationComponentsForHook(Schema $schema, PageTranslationSchemaHookEnum $hook): array
    {
        return [];
    }
}
