<?php

declare(strict_types=1);

namespace Capell\Navigation\Filament\Resources\Navigations\Schemas;

use Capell\Admin\Enums\SchemaTypeEnum;
use Capell\Admin\Filament\Components\Forms\Type\TypeSchema;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Capell\Core\Models\Type;
use Capell\Navigation\Filament\Schemas\Navigations\DefaultNavigationSchema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class NavigationForm implements FormConfigurator
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(self::getFormSchema($schema));
    }

    protected static function getFormSchema(Schema $schema): array
    {
        return [
            TypeSchema::make()
                ->columns($schema->getColumns())
                ->schema(function (Get $get, TypeSchema $component) use ($schema): array {
                    $typeId = $get('type_id');

                    /** @var class-string<Type> $model */
                    $model = Type::class;

                    $type = $typeId !== null ? $model::query()->find($typeId, ['admin']) : null;

                    $adminSchema = $type?->admin['schema'] ?? DefaultNavigationSchema::getKey();

                    return $component->getTypeSchema($schema, SchemaTypeEnum::Navigation, name: $adminSchema);
                }),
        ];
    }
}
