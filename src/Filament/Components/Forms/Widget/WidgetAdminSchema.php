<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Widget;

use Capell\Admin\Filament\Components\Forms\AssetTypeSelect;
use Capell\Admin\Filament\Components\Forms\IconPicker;
use Capell\Admin\Filament\Components\Forms\MediaLibraryFileUpload;
use Capell\Admin\Filament\Components\Forms\SchemaSelect;
use Capell\Layout\Enums\SchemaTypeEnum;
use Capell\Layout\Enums\WidgetSchemaEnum;
use Capell\Layout\Models\Widget;
use Filament\Schemas\Components\Fieldset;

class WidgetAdminSchema
{
    public static function make(): array
    {
        return [
            SchemaSelect::make('schema')
                ->default(fn (): string => WidgetSchemaEnum::Default->name)
                ->setupOptions(SchemaTypeEnum::Widget->value),

            SchemaSelect::make('layout_container_widget_schema')
                ->label(__('capell-admin::form.container_widget_schema'))
                ->setupOptions(SchemaTypeEnum::LayoutWidget->value),

            IconPicker::make('icon')
                ->label(__('capell-admin::form.admin_icon')),

            MediaLibraryFileUpload::make('image'),

            Fieldset::make(__('capell-admin::generic.assets'))
                ->gridContainer()
                ->columns(['lg' => null, '@lg' => 2])
                ->columnSpanFull()
                ->visible(fn (?Widget $record): bool => ! empty($record->type?->admin['asset_types']))
                ->schema([
                    SchemaSelect::make('widget_asset_schema')
                        ->label(__('capell-admin::form.widget_asset_schema'))
                        ->helperText(__('capell-admin::generic.widget_asset_schema_info'))
                        ->setupOptions(SchemaTypeEnum::WidgetAsset->value),

                    AssetTypeSelect::make('asset_types')
                        ->label(__('capell-admin::form.asset_type'))
                        ->multiple(),
                ]),
        ];
    }
}
