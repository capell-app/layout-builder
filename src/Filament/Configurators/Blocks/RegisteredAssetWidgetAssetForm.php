<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Blocks;

use Capell\Admin\Facades\CapellAdmin;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Filament\Schemas\Schema;
use Override;

class RegisteredAssetWidgetAssetForm extends AbstractBlockAssetConfigurator
{
    #[Override]
    protected function getAssetSchema(Schema $configurator): array
    {
        $record = $configurator->getRecord();
        $assetType = $configurator->getRawState()['asset_type'] ?? $record?->getAttribute('asset_type');
        $asset = CapellAdmin::getAsset((string) $assetType);

        $assetConfigurator = clone $configurator;

        if ($record instanceof WidgetAsset) {
            $record->loadMissing('asset');
            $assetConfigurator->record($record->asset);
        }

        return $asset->formClass::configure($assetConfigurator)->getComponents();
    }
}
