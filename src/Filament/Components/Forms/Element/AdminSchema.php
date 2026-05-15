<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms\Element;

use Capell\Admin\Filament\Components\Forms\AssetTypeSelect;
use Capell\Admin\Filament\Components\Forms\ConfiguratorSelect;
use Capell\Admin\Filament\Components\Forms\IconPicker;
use Capell\Admin\Filament\Components\Forms\MediaLibraryFileUpload;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Models\Element;
use Filament\Schemas\Components\Fieldset;

class AdminSchema
{
    public static function make(): array
    {
        return [
            ConfiguratorSelect::make('configurator')
                ->helperText(__('capell-layout-builder::generic.admin_element_schema_info'))
                ->setupOptions(ConfiguratorTypeEnum::Element),

            ConfiguratorSelect::make('layout_element_configurator')
                ->label(__('capell-layout-builder::form.layout_element_configurator'))
                ->helperText(__('capell-layout-builder::generic.admin_layout_builder_element_schema_info'))
                ->setupOptions(ConfiguratorTypeEnum::LayoutElement),

            IconPicker::make('icon')
                ->label(__('capell-admin::form.admin_icon')),

            MediaLibraryFileUpload::make('image'),

            Fieldset::make(__('capell-admin::generic.assets'))
                ->gridContainer()
                ->columns(['lg' => null, '@lg' => 2])
                ->columnSpanFull()
                ->visible(fn (?Element $record): bool => isset($record->type?->admin['asset_types']) && $record->type->admin['asset_types'] !== [])
                ->schema([
                    ConfiguratorSelect::make('element_asset_configurator')
                        ->label(__('capell-layout-builder::form.element_asset_configurator'))
                        ->helperText(__('capell-layout-builder::generic.element_asset_configurator_info'))
                        ->setupOptions(ConfiguratorTypeEnum::ElementAsset),

                    AssetTypeSelect::make('asset_types')
                        ->label(__('capell-admin::form.asset_type'))
                        ->multiple(),
                ]),
        ];
    }
}
