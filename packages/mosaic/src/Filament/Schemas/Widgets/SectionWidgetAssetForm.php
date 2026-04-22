<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Schemas\Widgets;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Mosaic\Enums\TypeSchemaEnum;
use Capell\Mosaic\Filament\Schemas\Sections\DefaultSectionSchema;
use Filament\Schemas\Schema;
use Override;

class SectionWidgetAssetForm extends AbstractWidgetAssetSchema
{
    #[Override]
    protected function getAssetSchema(Schema $schema): array
    {
        $adminSchema = CapellAdmin::getSchema(TypeSchemaEnum::Section, DefaultSectionSchema::getKey());

        return resolve($adminSchema)->make($schema);
    }
}
