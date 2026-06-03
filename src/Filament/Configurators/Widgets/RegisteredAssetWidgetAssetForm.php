<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Widgets;

use Capell\Admin\Facades\CapellAdmin;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\PublishingStudio\WorkspaceRegistry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Override;

class RegisteredAssetWidgetAssetForm extends AbstractWidgetAssetConfigurator
{
    private const string WORKSPACE_REGISTRY_CLASS = WorkspaceRegistry::class;

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
        $rawState = $configurator->getRawState();
        $state = $rawState instanceof Arrayable ? $rawState->toArray() : $rawState;
        $assetType = $state['asset_type'] ?? ($record instanceof Model ? $record->getAttribute('asset_type') : null);
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
        $workspaceRegistry = self::WORKSPACE_REGISTRY_CLASS;

        if (! class_exists($workspaceRegistry)) {
            return false;
        }

        $record = $configurator->getRecord();

        if (! $record instanceof WidgetAsset) {
            return false;
        }

        $record->loadMissing('asset');
        $asset = $record->asset;

        return $asset instanceof Model && $workspaceRegistry::isRegistered($asset::class);
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
