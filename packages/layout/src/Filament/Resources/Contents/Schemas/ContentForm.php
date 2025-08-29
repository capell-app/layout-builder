<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Contents\Schemas;

use Capell\Admin\Filament\Components\Forms\Type\TypeSchema;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Layout\Enums\SchemaEnum;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ContentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(static::getFormSchema($schema))
            ->columns();
    }

    protected static function getFormSchema(Schema $schema): array
    {
        return [
            TypeSchema::make()
                ->schema(
                    function (Get $get, TypeSchema $component) use ($schema): array {
                        $typeId = $get('type_id');

                        $type = $typeId ? CapellCore::getModel(ModelEnum::Type)::find($typeId, ['admin']) : null;

                        $name = $type->admin['schema'] ?? ContentForm::getKey();

                        return $component->getTypeSchema($schema, SchemaEnum::Content->value, $name);
                    }
                ),
        ];
    }
}
