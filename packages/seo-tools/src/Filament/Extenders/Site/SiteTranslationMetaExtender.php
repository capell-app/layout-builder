<?php

declare(strict_types=1);

namespace Capell\SeoTools\Filament\Extenders\Site;

use Capell\Admin\Enums\PageTranslationSchemaHookEnum;
use Capell\Admin\Support\Schemas\AbstractSiteSchemaExtender;
use Capell\SeoTools\Filament\Components\Forms\Site\TranslationMetaSchema;
use Filament\Schemas\Schema;

class SiteTranslationMetaExtender extends AbstractSiteSchemaExtender
{
    public function extendTranslationComponentsForHook(Schema $configurator, PageTranslationSchemaHookEnum $hook): array
    {
        if ($hook !== PageTranslationSchemaHookEnum::AfterTitle) {
            return [];
        }

        return TranslationMetaSchema::make();
    }
}
