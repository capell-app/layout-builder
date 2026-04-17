<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Resources\Collections\Schemas;

use Capell\Admin\Filament\Components\Forms\Type\TypeSchema;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Type;
use Capell\Mosaic\Enums\TypeSchemaEnum;
use Capell\Mosaic\Filament\Resources\Collections\Schemas\Types\DefaultContentSchema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class CollectionForm implements FormConfigurator
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
                        if ($get('cached_type_id') === null) {
                            $set('cached_type_id', $get('type_id'));
                        }

                        $typeId = $get('cached_type_id');

                        $record = $component->getRecord();

                        if ($record?->relationLoaded('type') && $record->type?->id === $typeId) {
                            $type = $record->type;
                        } else {
                            /** @var class-string<Type> $model */
                            $model = CapellCore::getModel(ModelEnum::Type);

                            $type = $typeId !== null ? $model::query()->find($typeId, ['admin']) : null;
                        }

                        $name = $type->admin['schema'] ?? DefaultContentSchema::getKey();

                        return $component->getTypeSchema($schema, TypeSchemaEnum::Content, $name);
                    },
                ),
        ];
    }
}
