<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Components\Forms\Widget;

use Capell\Admin\Filament\Components\Forms\AssetTypeSelect;
use Capell\Admin\Filament\Components\Forms\ConfiguratorSelect;
use Capell\Admin\Filament\Components\Forms\IconPicker;
use Capell\Admin\Filament\Components\Forms\MediaLibraryFileUpload;
use Capell\Mosaic\Enums\ConfiguratorTypeEnum;
use Capell\Mosaic\Models\Widget;
use Filament\Schemas\Components\Fieldset;

class AdminSchema
{
    public static function make(): array
    {
        return [
            ConfiguratorSelect::make('configurator')
                ->helperText(__('capell-mosaic::generic.admin_widget_schema_info'))
                ->setupOptions(ConfiguratorTypeEnum::Widget),

            ConfiguratorSelect::make('layout_widget_configurator')
                ->label(__('capell-mosaic::form.layout_widget_configurator'))
                ->helperText(__('capell-mosaic::generic.admin_layout_builder_widget_schema_info'))
                ->setupOptions(ConfiguratorTypeEnum::LayoutWidget),

            IconPicker::make('icon')
                ->label(__('capell-admin::form.admin_icon')),

            MediaLibraryFileUpload::make('image'),

            Fieldset::make(__('capell-admin::generic.assets'))
                ->gridContainer()
                ->columns(['lg' => null, '@lg' => 2])
                ->columnSpanFull()
                ->visible(fn (?Widget $record): bool => isset($record->type?->admin['asset_types']) && $record->type->admin['asset_types'] !== [])
                ->schema([
                    ConfiguratorSelect::make('widget_asset_configurator')
                        ->label(__('capell-mosaic::form.widget_asset_configurator'))
                        ->helperText(__('capell-mosaic::generic.widget_asset_configurator_info'))
                        ->setupOptions(ConfiguratorTypeEnum::WidgetAsset),

                    AssetTypeSelect::make('asset_types')
                        ->label(__('capell-admin::form.asset_type'))
                        ->multiple(),
                ]),
        ];
    }
}
