<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Extenders\Page;

use Capell\Admin\Contracts\Extenders\PageSchemaExtender;
use Capell\Admin\Enums\PageTranslationSchemaHookEnum;
use Capell\Mosaic\Filament\Components\Forms\Page\HeroEditor;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class HeroPageSchemaExtender implements PageSchemaExtender
{
    public function extendRelationManagers(Model $record, array $relationManagers): array
    {
        return $relationManagers;
    }

    public function extendTabs(Schema $schema, array $tabs): array
    {
        return $tabs;
    }

    /**
     * @return array<int, Component>
     */
    public function extendTranslationComponentsForHook(Schema $schema, PageTranslationSchemaHookEnum $hook): array
    {
        if ($hook !== PageTranslationSchemaHookEnum::AfterTitle) {
            return [];
        }

        return [HeroEditor::make()];
    }
}
