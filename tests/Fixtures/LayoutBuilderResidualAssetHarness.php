<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures;

use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;
use Capell\LayoutBuilder\Models\LayoutPreset;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Illuminate\Database\Eloquent\Collection;
use Override;

final class LayoutBuilderResidualAssetHarness extends LayoutBuilder
{
    #[Override]
    public function assertCanUpdateLayout(): void {}

    #[Override]
    public function assertCanEditContent(): void {}

    /**
     * @param  array<string, array<int, Widget>>  $containerWidgets
     */
    public function setContainerWidgets(array $containerWidgets): void
    {
        $this->containerWidgets = $containerWidgets;
    }

    /**
     * @param  array<array-key, mixed>  $assetsMeta
     */
    public function exposeAddAssets(
        string $containerKey,
        int $widgetIndex,
        ?bool $hasPageAssets,
        string $type,
        mixed $assets,
        array $assetsMeta = [],
    ): void {
        $this->addAssets($containerKey, $widgetIndex, $hasPageAssets, $type, $assets, $assetsMeta);
    }

    public function exposeUpdateAssets(string $containerKey, int $widgetIndex, ?string $oldContainerKey = null): void
    {
        $this->updateAssets($containerKey, $widgetIndex, $oldContainerKey);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function assetSlot(string $containerKey, int $widgetIndex): array
    {
        $containerAssets = $this->assets[$containerKey] ?? null;
        $slot = is_array($containerAssets) ? ($containerAssets[$widgetIndex] ?? null) : null;

        if (! is_array($slot)) {
            return [];
        }

        $assets = [];

        foreach ($slot as $asset) {
            if (! is_array($asset)) {
                continue;
            }

            $normalizedAsset = [];

            foreach ($asset as $key => $value) {
                if (is_string($key)) {
                    $normalizedAsset[$key] = $value;
                }
            }

            $assets[] = $normalizedAsset;
        }

        return $assets;
    }

    /**
     * @param  list<mixed>  $records
     */
    public function setSelectedRecordSlot(string $containerKey, int $widgetIndex, array $records): void
    {
        $containerRecords = $this->selectedRecords[$containerKey] ?? [];
        $containerRecords = is_array($containerRecords) ? $containerRecords : [];
        $containerRecords[$widgetIndex] = $records;
        $this->selectedRecords[$containerKey] = $containerRecords;
    }

    /**
     * @return Collection<int, WidgetAsset>
     */
    public function exposeLoadWidgetAssetsFor(Widget $widget, string $containerKey, int $widgetIndex): Collection
    {
        return $this->loadWidgetAssetsFor($widget, $containerKey, $widgetIndex);
    }

    /**
     * @return Collection<int, WidgetAsset>
     */
    public function exposeLoadWidgetAssets(Widget $widget, string $containerKey, int $widgetOccurrence): Collection
    {
        return $this->loadWidgetAssets($widget, $containerKey, $widgetOccurrence);
    }

    /**
     * @return Collection<int, WidgetAsset>|null
     */
    public function exposePreloadAllWidgetAssets(): ?Collection
    {
        return $this->preloadAllWidgetAssets();
    }

    /**
     * @return array<array-key, mixed>
     */
    public function exposeActiveWidgetAssetIds(Widget $widget): array
    {
        return $this->activeWidgetAssetIds($widget);
    }

    /**
     * @param  array<array-key, mixed>  $asset
     */
    public function exposeCreateWidgetAsset(
        Widget $widget,
        string $containerKey,
        int $occurrence,
        bool $hasPageAssets,
        int $order,
        array $asset,
    ): WidgetAsset {
        return $this->createWidgetAsset($widget, $containerKey, $occurrence, $hasPageAssets, $order, $asset);
    }

    public function exposeDeleteRemovedWidgetAssets(): void
    {
        $this->deleteRemovedWidgetAssets();
    }

    #[Override]
    protected function assertCanEditLayout(): void {}

    #[Override]
    protected function assertCanUseLayoutBuilder(): void {}

    #[Override]
    protected function assertCanCreateLayoutPreset(Site $site): void {}

    #[Override]
    protected function assertCanApplyLayoutPreset(LayoutPreset $preset, Site $site): void {}
}
