<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Widgets\Schemas;

use Capell\Admin\Filament\Components\Forms\Type\TypeSchema;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Layout\Enums\SchemaTypeEnum;
use Capell\Layout\Filament\Resources\Widgets\Schemas\Types\DefaultWidgetSchema;
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
                        $typeId = $get('type_id');

                        $record = $component->getRecord();

                        $adminSchema = $record?->admin['schema'] ?? null;

                        if (! $adminSchema) {
                            $type = $typeId ? CapellCore::getModel(ModelEnum::Type)::find($typeId, ['admin']) : null;

                            $adminSchema = $type?->admin['schema'] ?? DefaultWidgetSchema::getKey();
                        }

                        return $component->getTypeSchema($schema, SchemaTypeEnum::Widget, $adminSchema);
                    },
                ),
        ];
    }
}
