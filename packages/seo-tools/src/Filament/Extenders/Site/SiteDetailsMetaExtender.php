<?php

declare(strict_types=1);

namespace Capell\SeoTools\Filament\Extenders\Site;

use Capell\Admin\Support\Schemas\AbstractSiteSchemaExtender;
use Capell\SeoTools\Filament\Components\Forms\Site\MetaSchema;
use Filament\Schemas\Schema;

class SiteDetailsMetaExtender extends AbstractSiteSchemaExtender
{
    public function extendSiteMetaDetailsComponents(Schema $schema, array $components): array
    {
        return [MetaSchema::make(), ...$components];
    }
}
