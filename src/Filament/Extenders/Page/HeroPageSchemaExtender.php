<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Extenders\Page;

use Capell\Admin\Contracts\Extenders\PageSchemaExtender;
use Capell\Admin\Enums\PageTranslationSchemaHookEnum;
use Capell\LayoutBuilder\Filament\Components\Forms\Page\HeroEditor;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class HeroPageSchemaExtender implements PageSchemaExtender
{
    public function extendRelationManagers(Model $record, array $relationManagers): array
    {
        return $relationManagers;
    }

    public function extendTabs(Schema $configurator, array $tabs): array
    {
        return $tabs;
    }

    /**
     * @return array<int, Component>
     */
    public function extendTranslationComponentsForHook(Schema $configurator, PageTranslationSchemaHookEnum $hook): array
    {
        if ($hook !== PageTranslationSchemaHookEnum::AfterTitle) {
            return [];
        }

        return [HeroEditor::make()];
    }

    public function extendSidebarComponents(Schema $configurator): array
    {
        return [];
    }
}
