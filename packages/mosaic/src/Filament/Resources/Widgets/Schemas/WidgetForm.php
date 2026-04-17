<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Resources\Widgets\Schemas;

use Capell\Admin\Filament\Components\Forms\Type\TypeSchema;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Type;
use Capell\Mosaic\Enums\TypeSchemaEnum;
use Capell\Mosaic\Filament\Resources\Widgets\Schemas\Types\DefaultWidgetSchema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class WidgetForm implements FormConfigurator
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
                ->columns($schema->getColumns())
                ->schema(
                    function (Get $get, TypeSchema $component) use ($schema): array {
                        $typeId = $get('type_id') ?? null;

                        $record = $component->getRecord();

                        $adminSchema = $record?->admin['schema'] ?? null;

                        if (! $adminSchema) {
                            /** @var class-string<Type> $model */
                            $model = CapellCore::getModel(ModelEnum::Type);

                            $type = $typeId !== null ? $model::query()->find($typeId, ['admin']) : null;

                            $adminSchema = $type?->admin['schema'] ?? DefaultWidgetSchema::getKey();
                        }

                        return $component->getTypeSchema($schema, TypeSchemaEnum::Widget, $adminSchema);
                    },
                ),
        ];
    }
}
