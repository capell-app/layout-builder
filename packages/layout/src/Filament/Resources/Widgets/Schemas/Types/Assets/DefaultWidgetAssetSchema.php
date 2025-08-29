<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Widgets\Schemas\Types\Assets;

use Capell\Admin\Contracts\TypeSchemaInterface;
use Capell\Admin\Filament\Concerns\HasTypeSchema;
use Capell\Layout\Enums\SchemaEnum;
use Filament\Schemas\Schema;

class DefaultWidgetAssetSchema implements TypeSchemaInterface
{
    use HasTypeSchema;

    protected static string $schemaType = SchemaEnum::WidgetAsset->value;

    public static function make(Schema $schema): array
    {
        return [
            self::getAssetFormSchema($schema, PageResource::getFormSchema($schema)),
        ];
    }
}
