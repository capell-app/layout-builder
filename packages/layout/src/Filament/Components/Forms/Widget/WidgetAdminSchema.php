<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Widget;

use Capell\Admin\Filament\Components\Forms\AdminSchemaSelect;
use Capell\Admin\Filament\Components\Forms\IconPicker;
use Capell\Admin\Filament\Components\Forms\ImageUpload;
use Capell\Layout\Enums\SchemaEnum;
use Capell\Layout\Filament\Schemas\Widget\DefaultWidgetSchema;
use Filament\Forms;

class WidgetAdminSchema
{
    public static function make(): array
    {
        return [
            Forms\Components\TextInput::make('notes')
                ->label(__('capell-admin::form.admin_notes'))
                ->hint(__('capell-admin::generic.internal'))
                ->columnSpanFull(),

            AdminSchemaSelect::make('schema')
                ->default(fn (): string => DefaultWidgetSchema::getKey())
                ->setupOptions(SchemaEnum::Widget->value),

            AdminSchemaSelect::make('widget_asset_schema')
                ->label(__('capell-admin::form.widget_asset_schema'))
                ->helperText(__('capell-admin::generic.widget_asset_schema_info'))
                ->setupOptions(SchemaEnum::WidgetAsset->value),

            AdminSchemaSelect::make('layout_container_widget_schema')
                ->label(__('capell-admin::form.container_widget_asset_schema'))
                ->helperText(__('capell-admin::generic.container_widget_asset_schema_info'))
                ->setupOptions(SchemaEnum::LayoutWidget->value),

            IconPicker::make('icon')
                ->label(__('capell-admin::form.admin_icon')),

            ImageUpload::make('image')
                ->directory('widgets'),

            Forms\Components\Select::make('asset_types')
                ->label(__('capell-admin::form.asset_type'))
                ->helperText(__('capell-admin::generic.asset_type_info'))
                ->multiple()
                ->options([
                    'content' => __('capell-admin::generic.content'),
                    'media' => __('capell-admin::generic.media'),
                    'page' => __('capell-admin::generic.page'),
                ]),
        ];
    }
}
