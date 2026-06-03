<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms\Widget;

use Capell\Admin\Filament\Components\Forms\AssetTypeSelect;
use Capell\Admin\Filament\Components\Forms\ConfiguratorSelect;
use Capell\Admin\Filament\Components\Forms\IconPicker;
use Capell\Admin\Filament\Components\Forms\MediaLibraryFileUpload;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Models\Widget;
use Filament\Schemas\Components\Fieldset;

class AdminSchema
{
    /**
     * @return array<array-key, mixed>
     */
    public static function make(): array
    {
        return [
            ConfiguratorSelect::make('configurator')
                ->helperText(__('capell-layout-builder::generic.admin_widget_schema_info'))
                ->setupOptions(ConfiguratorTypeEnum::Widget)
                ->withCreateConfiguratorAction(ConfiguratorTypeEnum::Widget),

            ConfiguratorSelect::make('layout_widget_configurator')
                ->label(__('capell-layout-builder::form.layout_widget_configurator'))
                ->helperText(__('capell-layout-builder::generic.admin_layout_builder_widget_schema_info'))
                ->setupOptions(ConfiguratorTypeEnum::LayoutWidget)
                ->withCreateConfiguratorAction(ConfiguratorTypeEnum::LayoutWidget),

            IconPicker::make('icon')
                ->label(__('capell-admin::form.admin_icon')),

            MediaLibraryFileUpload::make('image'),

            Fieldset::make(__('capell-admin::generic.assets'))
                ->gridContainer()
                ->columns(['lg' => null, '@lg' => 2])
                ->columnSpanFull()
                ->visible(function (?Widget $record): bool {
                    if (! $record instanceof Widget || $record->type === null) {
                        return false;
                    }

                    return ($record->type->admin['asset_types'] ?? []) !== [];
                })
                ->schema([
                    ConfiguratorSelect::make('widget_asset_configurator')
                        ->label(__('capell-layout-builder::form.widget_asset_configurator'))
                        ->helperText(__('capell-layout-builder::generic.widget_asset_configurator_info'))
                        ->setupOptions(ConfiguratorTypeEnum::WidgetAsset)
                        ->withCreateConfiguratorAction(ConfiguratorTypeEnum::WidgetAsset),

                    AssetTypeSelect::make('asset_types')
                        ->label(__('capell-admin::form.asset_type'))
                        ->multiple(),
                ]),
        ];
    }
}
