<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms\Block;

use Capell\Admin\Filament\Components\Forms\AssetTypeSelect;
use Capell\Admin\Filament\Components\Forms\ConfiguratorSelect;
use Capell\Admin\Filament\Components\Forms\IconPicker;
use Capell\Admin\Filament\Components\Forms\MediaLibraryFileUpload;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Models\Block;
use Filament\Schemas\Components\Fieldset;

class AdminSchema
{
    public static function make(): array
    {
        return [
            ConfiguratorSelect::make('configurator')
                ->helperText(__('capell-layout-builder::generic.admin_block_schema_info'))
                ->setupOptions(ConfiguratorTypeEnum::Block),

            ConfiguratorSelect::make('layout_block_configurator')
                ->label(__('capell-layout-builder::form.layout_block_configurator'))
                ->helperText(__('capell-layout-builder::generic.admin_layout_builder_block_schema_info'))
                ->setupOptions(ConfiguratorTypeEnum::LayoutBlock),

            IconPicker::make('icon')
                ->label(__('capell-admin::form.admin_icon')),

            MediaLibraryFileUpload::make('image'),

            Fieldset::make(__('capell-admin::generic.assets'))
                ->gridContainer()
                ->columns(['lg' => null, '@lg' => 2])
                ->columnSpanFull()
                ->visible(fn (?Block $record): bool => isset($record->type?->admin['asset_types']) && $record->type->admin['asset_types'] !== [])
                ->schema([
                    ConfiguratorSelect::make('block_asset_configurator')
                        ->label(__('capell-layout-builder::form.block_asset_configurator'))
                        ->helperText(__('capell-layout-builder::generic.block_asset_configurator_info'))
                        ->setupOptions(ConfiguratorTypeEnum::BlockAsset),

                    AssetTypeSelect::make('asset_types')
                        ->label(__('capell-admin::form.asset_type'))
                        ->multiple(),
                ]),
        ];
    }
}
