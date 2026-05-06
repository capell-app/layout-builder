<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Filament\Extenders\Site;

use Capell\Admin\Support\Schemas\AbstractSiteSchemaExtender;
use Capell\SeoSuite\Filament\Components\Forms\Site\MetaSchema;
use Filament\Schemas\Schema;

class SiteDetailsMetaExtender extends AbstractSiteSchemaExtender
{
    public function extendSiteMetaDetailsComponents(Schema $configurator, array $components): array
    {
        return [MetaSchema::make(), ...$components];
    }
}
