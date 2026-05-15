<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Elements\Schemas;

use Capell\Admin\Data\Configurators\ConfiguratorContextData;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Enums\ElementAssetConfiguratorEnum;
use Capell\LayoutBuilder\Filament\Configurators\Elements\PageElementAssetForm;
use Capell\LayoutBuilder\Models\ElementAsset;
use Filament\Schemas\Schema;
use RuntimeException;

class ElementAssetForm implements FormConfigurator
{
    public static function configure(Schema $configurator, ?ConfiguratorContextData $context = null): Schema
    {
        $record = $configurator->getRecord();
        $state = $configurator->getRawState();
        $assetType = $state['asset_type'] ?? ($record instanceof ElementAsset ? $record->asset_type : null);

        throw_unless($assetType, RuntimeException::class, 'Asset type is required to load the asset schema');

        $adminSchema = null;

        if ($record instanceof ElementAsset && $record->exists) {
            $element = $record->element;

            $adminSchema = $element->admin['element_asset_configurator'][$assetType]
                ?? $element->type->admin['element_asset_configurator'][$assetType]
                ?? null;
        }

        $enumCase = ElementAssetConfiguratorEnum::class . '::' . ucfirst((string) $assetType);

        if ($adminSchema === null && defined($enumCase)) {
            $adminSchema = ElementAssetConfiguratorEnum::fromName(ucfirst((string) $assetType))->value::getKey();
        }

        if ($adminSchema === null) {
            $adminSchema = PageElementAssetForm::getKey();
        }

        $adminType = AdminSurfaceLookup::configurator(ConfiguratorTypeEnum::ElementAsset, $adminSchema);

        return $adminType::configure($configurator)->columns();
    }
}
