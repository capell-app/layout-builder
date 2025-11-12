<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Widgets\Schemas\Types\Assets;

use Capell\Admin\Enums\SchemaTypeEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Resources\Pages\Schemas\Types\DefaultPageSchema;
use Filament\Schemas\Schema;
use Override;

class PageWidgetAssetForm extends AbstractWidgetAssetSchema
{
    #[Override]
    protected function getAssetSchema(Schema $schema): array
    {
        $adminSchema = CapellAdmin::getSchema(SchemaTypeEnum::Page, DefaultPageSchema::getKey());

        return app($adminSchema)->make($schema);
    }
}
