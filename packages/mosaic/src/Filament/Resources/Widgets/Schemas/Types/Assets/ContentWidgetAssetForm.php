<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Resources\Widgets\Schemas\Types\Assets;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Mosaic\Enums\TypeSchemaEnum;
use Capell\Mosaic\Filament\Resources\Contents\Schemas\Types\DefaultContentSchema;
use Filament\Schemas\Schema;
use Override;

class ContentWidgetAssetForm extends AbstractWidgetAssetSchema
{
    #[Override]
    protected function getAssetSchema(Schema $schema): array
    {
        $adminSchema = CapellAdmin::getSchema(TypeSchemaEnum::Content, DefaultContentSchema::getKey());

        return resolve($adminSchema)->make($schema);
    }
}
