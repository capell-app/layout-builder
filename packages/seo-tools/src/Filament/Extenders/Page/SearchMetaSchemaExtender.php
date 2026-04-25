<?php

declare(strict_types=1);

namespace Capell\SeoTools\Filament\Extenders\Page;

use Capell\Admin\Contracts\Extenders\PageSchemaExtender;
use Capell\Admin\Enums\PageTranslationSchemaHookEnum;
use Capell\SeoTools\Filament\Components\Forms\Page\TranslationSeoMetaSchema;
use Capell\SeoTools\Filament\Components\Forms\SearchMetaDataSection;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class SearchMetaSchemaExtender implements PageSchemaExtender
{
    /**
     * @return array<int, Component>
     */
    public function extendTranslationComponentsForHook(Schema $schema, PageTranslationSchemaHookEnum $hook): array
    {
        if ($hook !== PageTranslationSchemaHookEnum::BeforeSearchMeta) {
            return [];
        }

        return [
            SearchMetaDataSection::make()
                ->statePath('meta')
                ->visibleOn(['edit', 'editOption'])
                ->schema(TranslationSeoMetaSchema::make()),
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
    public function extendTabs(Schema $schema, array $tabs): array
    {
        return $tabs;
    }
}
