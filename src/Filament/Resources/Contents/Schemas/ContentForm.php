<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Contents\Schemas;

use Capell\Admin\Filament\Components\Forms\Type\TypeSchema;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Layout\Enums\SchemaTypeEnum;
use Capell\Layout\Filament\Resources\Contents\Schemas\Types\DefaultContentSchema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ContentForm implements FormConfigurator
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(static::getFormSchema($schema->columns()));
    }

    public static function getFormSchema(Schema $schema): array
    {
        return [
            TypeSchema::make()
                ->columns($schema->getColumns())
                ->schema(
                    function (Get $get, Set $set, TypeSchema $component) use ($schema): array {
                        if (! $get('cached_type_id')) {
                            $set('cached_type_id', $get('type_id'));
                        }

                        $typeId = $get('cached_type_id');

                        $record = $component->getRecord();

                        if ($record?->relationLoaded('type') && $record->type?->id === $typeId) {
                            $type = $record->type;
                        } else {
                            $type = $typeId ? CapellCore::getModel(ModelEnum::Type)::find($typeId, ['admin']) : null;
                        }

                        $name = $type->admin['schema'] ?? DefaultContentSchema::getKey();

                        return $component->getTypeSchema($schema, SchemaTypeEnum::Content->value, $name);
                    },
                ),
        ];
    }
}
