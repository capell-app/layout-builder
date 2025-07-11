<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Widget;

use Capell\Admin\Filament\Components\Forms\AssetTypeSelect;
use Capell\Admin\Filament\Components\Forms\IconPicker;
use Capell\Admin\Filament\Components\Forms\ImageUpload;
use Capell\Admin\Filament\Components\Forms\SchemaSelect;
use Capell\Layout\Enums\SchemaEnum;
use Capell\Layout\Filament\Schemas\Widget\DefaultWidgetSchema;
use Capell\Layout\Models\Widget;
use Filament\Forms;

class WidgetAdminSchema
{
    public static function make(): array
    {
        return [
            SchemaSelect::make('schema')
                ->default(fn (): string => DefaultWidgetSchema::getKey())
                ->setupOptions(SchemaEnum::Widget->value),

            SchemaSelect::make('layout_container_widget_schema')
                ->label(__('capell-admin::form.container_widget_schema'))
                ->setupOptions(SchemaEnum::LayoutWidget->value),

            IconPicker::make('icon')
                ->label(__('capell-admin::form.admin_icon')),

            ImageUpload::make('image')
                ->directory('widgets'),

            Forms\Components\Fieldset::make(__('capell-admin::generic.assets'))
                ->visible(fn (?Widget $record): bool => ! empty($record->type?->admin['asset_types']))
                ->schema([
                    SchemaSelect::make('widget_asset_schema')
                        ->label(__('capell-admin::form.widget_asset_schema'))
                        ->helperText(__('capell-admin::generic.widget_asset_schema_info'))
                        ->setupOptions(SchemaEnum::WidgetAsset->value),

                    AssetTypeSelect::make('asset_types')
                        ->label(__('capell-admin::form.asset_type'))
                        ->multiple(),
                ]),
        ];
    }
}
