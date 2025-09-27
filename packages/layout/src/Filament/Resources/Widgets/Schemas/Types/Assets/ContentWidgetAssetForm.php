<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Widgets\Schemas\Types\Assets;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Layout\Enums\SchemaTypeEnum;
use Capell\Layout\Filament\Resources\Contents\Schemas\Types\DefaultContentSchema;
use Filament\Schemas\Schema;
use Override;

class ContentWidgetAssetForm extends AbstractWidgetAssetSchema
{
    #[Override]
    protected function getAssetSchema(Schema $schema): array
    {
        $adminSchema = CapellAdmin::getSchema(
            SchemaTypeEnum::Content->value,
            DefaultContentSchema::getKey()
        );

        return app($adminSchema)->make($schema);
    }
}
