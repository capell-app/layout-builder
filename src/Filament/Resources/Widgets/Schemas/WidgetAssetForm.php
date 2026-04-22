<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Resources\Widgets\Schemas;

use Capell\Admin\Filament\Components\Forms\Type\TypeSchema;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Capell\Mosaic\Enums\TypeSchemaEnum;
use Capell\Mosaic\Enums\WidgetAssetSchemaEnum;
use Capell\Mosaic\Models\WidgetAsset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use RuntimeException;

class WidgetAssetForm implements FormConfigurator
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
                ->schema(function (TypeSchema $component, Get $get, ?WidgetAsset $record) use ($schema): array {
                    $assetType = $get('asset_type') ?? $record?->asset_type;

                    throw_unless($assetType, RuntimeException::class, 'Asset type is required to load the asset schema');

                    $adminSchema = null;

                    if ($record instanceof WidgetAsset) {
                        $widget = $record->widget;

                        $adminSchema = $widget->admin['widget_asset_schema'][$assetType]
                            ?? $widget->type->admin['widget_asset_schema'][$assetType]
                            ?? null;
                    }

                    if ($adminSchema === null) {
                        $adminSchema = WidgetAssetSchemaEnum::fromName(ucfirst($assetType))->value::getKey();
                    }

                    return $component->getTypeSchema($schema, TypeSchemaEnum::WidgetAsset, name: $adminSchema);
                }),
        ];
    }
}
