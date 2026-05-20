<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Blocks\Schemas;

use Capell\Admin\Data\Configurators\ConfiguratorContextData;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\LayoutBuilder\Enums\BlockAssetConfiguratorEnum;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Filament\Configurators\Blocks\PageBlockAssetForm;
use Capell\LayoutBuilder\Filament\Configurators\Blocks\RegisteredAssetBlockAssetForm;
use Capell\LayoutBuilder\Models\BlockAsset;
use Filament\Schemas\Schema;
use RuntimeException;

class BlockAssetForm implements FormConfigurator
{
    public static function configure(Schema $configurator, ?ConfiguratorContextData $context = null): Schema
    {
        $record = $configurator->getRecord();
        $state = $configurator->getRawState();
        $assetType = $state['asset_type'] ?? ($record instanceof BlockAsset ? $record->asset_type : null);

        throw_unless($assetType, RuntimeException::class, 'Asset type is required to load the asset schema');

        $adminSchema = null;

        if ($record instanceof BlockAsset && $record->exists) {
            $block = $record->block;

            $adminSchema = $block->admin['block_asset_configurator'][$assetType]
                ?? $block->type->admin['block_asset_configurator'][$assetType]
                ?? null;
        }

        $enumCase = BlockAssetConfiguratorEnum::class . '::' . ucfirst((string) $assetType);

        if ($adminSchema === null && defined($enumCase)) {
            $adminSchema = BlockAssetConfiguratorEnum::fromName(ucfirst((string) $assetType))->value::getKey();
        }

        if ($adminSchema === null && $assetType !== 'page') {
            $adminSchema = RegisteredAssetBlockAssetForm::getKey();
        }

        if ($adminSchema === null) {
            $adminSchema = PageBlockAssetForm::getKey();
        }

        $adminType = AdminSurfaceLookup::configurator(ConfiguratorTypeEnum::BlockAsset, $adminSchema);

        return $adminType::configure($configurator)->columns();
    }
}
