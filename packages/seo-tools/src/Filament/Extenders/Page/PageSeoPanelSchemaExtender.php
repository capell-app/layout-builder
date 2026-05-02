<?php

declare(strict_types=1);

namespace Capell\SeoTools\Filament\Extenders\Page;

use Capell\Admin\Contracts\Extenders\PageSchemaExtender;
use Capell\Admin\Enums\PageTranslationSchemaHookEnum;
use Capell\SeoTools\Filament\Components\Forms\Page\PageSeoPanel;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class PageSeoPanelSchemaExtender implements PageSchemaExtender
{
    /**
     * @return array<int, Component>
     */
    public function extendTranslationComponentsForHook(Schema $configurator, PageTranslationSchemaHookEnum $hook): array
    {
        if ($hook !== PageTranslationSchemaHookEnum::AfterSearchMeta) {
            return [];
        }

        return [
            PageSeoPanel::make(),
        ];
    }

    /**
     * @param  array<int, mixed>  $relationManagers
     * @return array<int, mixed>
     */
    public function extendRelationManagers(Model $record, array $relationManagers): array
    {
        return $relationManagers;
    }

    /**
     * @param  array<int, mixed>  $tabs
     * @return array<int, mixed>
     */
    public function extendTabs(Schema $configurator, array $tabs): array
    {
        return $tabs;
    }

    /**
     * @return array<int, Component>
     */
    public function extendSettingsTabComponents(): array
    {
        return [];
    }
}
