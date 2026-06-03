<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures;

use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Override;

final class LayoutBuilderAssetHarness extends LayoutBuilder
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

    public function exposeUpdatePageAssets(string $containerKey, int $widgetIndex, ?bool $hasPageAssets = null): void
    {
        $this->updatePageAssets($containerKey, $widgetIndex, $hasPageAssets);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function exposeMapWidgetAssets(Widget $widget, string $containerKey, ?string $oldContainerKey = null): array
    {
        return $this->mapWidgetAssets($widget, $containerKey, $oldContainerKey);
    }

    /**
     * @param  array<array-key, mixed>  $widgetAssets
     * @param  EloquentCollection<int, WidgetAsset>|null  $allWidgetAssets
     * @return EloquentCollection<int, WidgetAsset>
     */
    public function exposeSetupWidgetAssets(
        string $containerKey,
        int $widgetIndex,
        array $widgetAssets,
        ?EloquentCollection $allWidgetAssets,
        Widget $widget,
    ): EloquentCollection {
        return $this->setupWidgetAssets($containerKey, $widgetIndex, $widgetAssets, $allWidgetAssets, $widget);
    }

    /**
     * @param  EloquentCollection<int, WidgetAsset>  $assets
     * @return EloquentCollection<int, WidgetAsset>
     */
    public function exposeFilterContainerWidgetAssets(
        EloquentCollection $assets,
        string $containerKey,
        int $widgetOccurrence,
        ?Widget $widget = null,
    ): EloquentCollection {
        return $this->filterContainerWidgetAssets($assets, $containerKey, $widgetOccurrence, $widget);
    }

    public function exposeSaveOriginalAssets(): void
    {
        $this->saveOriginalAssets();
    }

    public function exposeDeleteRemovedWidgetAssets(): void
    {
        $this->deleteRemovedWidgetAssets();
    }
}
