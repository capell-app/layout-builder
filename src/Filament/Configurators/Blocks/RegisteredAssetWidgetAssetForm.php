<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Blocks;

use Capell\Admin\Facades\CapellAdmin;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\PublishingStudio\WorkspaceRegistry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Override;

class RegisteredAssetWidgetAssetForm extends AbstractBlockAssetConfigurator
{
    /**
     * @return array<array-key, mixed>
     */
    #[Override]
    public function make(Schema $configurator): array
    {
        if (! $this->usesDraftStatePath($configurator)) {
            return parent::make($configurator);
        }

        return [
            Grid::make()
                ->statePath('asset')
                ->model($this->draftableAssetRecord($configurator))
                ->columnSpanFull()
                ->schema($this->extendAssetComponents($configurator, $this->getAssetSchema($configurator))),
        ];
    }

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

    private function usesDraftStatePath(Schema $configurator): bool
    {
        if (! class_exists(WorkspaceRegistry::class)) {
            return false;
        }

        $record = $configurator->getRecord();

        if (! $record instanceof WidgetAsset) {
            return false;
        }

        $record->loadMissing('asset');
        $asset = $record->asset;

        return $asset instanceof Model && WorkspaceRegistry::isRegistered($asset::class);
    }

    private function draftableAssetRecord(Schema $configurator): ?Model
    {
        $record = $configurator->getRecord();

        if (! $record instanceof WidgetAsset) {
            return null;
        }

        $record->loadMissing('asset');
        $asset = $record->asset;

        return $asset instanceof Model ? $asset : null;
    }
}
