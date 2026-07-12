<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\Mutations;

use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Data\LayoutChangeData;
use Capell\LayoutBuilder\Data\LayoutMutationResultData;
use Lorisleiva\Actions\Concerns\AsAction;

final class ReorderLayoutWidgetAction
{
    use AsAction;

    public function handle(
        LayoutBuilderStateData $state,
        string $originalContainer,
        string $targetContainer,
        int $originalIndex,
        int $targetIndex,
    ): LayoutMutationResultData {
        $originalContainerData = is_array($state->containers[$originalContainer] ?? null)
            ? $state->containers[$originalContainer]
            : [];
        $originalWidgets = is_array($originalContainerData['widgets'] ?? null)
            ? $originalContainerData['widgets']
            : [];

        if (! isset($originalWidgets[$originalIndex])) {
            return new LayoutMutationResultData($state);
        }

        if (! isset($state->containers[$targetContainer])) {
            return new LayoutMutationResultData($state);
        }

        $containers = $state->containers;
        $assets = $state->assets;
        $originalAssets = $state->originalAssets;
        $selectedRecords = $state->selectedRecords;

        $targetContainerData = is_array($containers[$targetContainer] ?? null)
            ? $containers[$targetContainer]
            : [];
        $targetWidgets = is_array($targetContainerData['widgets'] ?? null)
            ? $targetContainerData['widgets']
            : [];

        $widget = $originalWidgets[$originalIndex];
        $widgetAssets = $this->slotItems($assets, $originalContainer, $originalIndex);
        $widgetOriginalAssets = $this->slotItems($originalAssets, $originalContainer, $originalIndex);
        $widgetSelectedRecords = $this->slotItems($selectedRecords, $originalContainer, $originalIndex);

        $originalWidgets = $this->removeSlot(
            items: $originalWidgets,
            index: $originalIndex,
        );
        $originalContainerData['widgets'] = $originalWidgets;
        $containers[$originalContainer] = $originalContainerData;

        $assets[$originalContainer] = $this->removeSlot($this->containerSlots($assets, $originalContainer), $originalIndex);
        $originalAssets[$originalContainer] = $this->removeSlot($this->containerSlots($originalAssets, $originalContainer), $originalIndex);
        $selectedRecords[$originalContainer] = $this->removeSlot($this->containerSlots($selectedRecords, $originalContainer), $originalIndex);

        if ($originalContainer === $targetContainer) {
            $targetWidgets = $originalWidgets;
            $targetContainerData = $originalContainerData;
        }

        $targetIndex = min(
            count($targetWidgets),
            max(0, $targetIndex),
        );

        $targetWidgets = $this->insertSlot(
            items: $targetWidgets,
            index: $targetIndex,
            item: $widget,
        );
        $assets[$targetContainer] = $this->insertSlot($this->containerSlots($assets, $targetContainer), $targetIndex, $widgetAssets);
        $originalAssets[$targetContainer] = $this->insertSlot($this->containerSlots($originalAssets, $targetContainer), $targetIndex, $widgetOriginalAssets);
        $selectedRecords[$targetContainer] = $this->insertSlot($this->containerSlots($selectedRecords, $targetContainer), $targetIndex, $widgetSelectedRecords);

        $targetWidgets = $this->normalizeOccurrences($targetWidgets);
        $targetContainerData['widgets'] = $targetWidgets;
        $containers[$targetContainer] = $targetContainerData;

        if ($originalContainer !== $targetContainer) {
            $originalContainerData['widgets'] = $this->normalizeOccurrences($originalWidgets);
            $containers[$originalContainer] = $originalContainerData;
        }

        $assets = $this->syncAssetOccurrences($containers, $assets);
        $originalAssets = $this->syncAssetOccurrences($containers, $originalAssets);

        return NormalizeLayoutBuilderStateAction::run(new LayoutBuilderStateData(
            containers: $containers,
            assets: $assets,
            originalAssets: $originalAssets,
            selectedRecords: $selectedRecords,
        ))->withChanges([
            new LayoutChangeData(
                type: 'widget_reordered',
                label: __('capell-layout-builder::message.widget_reordered', ['container' => $targetContainer]),
                containerKey: $targetContainer,
                widgetIndex: $targetIndex,
            ),
        ]);
    }

    /**
     * @param  array<array-key, mixed>  $collection
     * @return array<int, mixed>
     */
    private function containerSlots(array $collection, string $containerKey): array
    {
        $slots = $collection[$containerKey] ?? [];

        return is_array($slots) ? array_values($slots) : [];
    }

    /**
     * @param  array<array-key, mixed>  $collection
     * @return array<array-key, mixed>
     */
    private function slotItems(array $collection, string $containerKey, int $index): array
    {
        $items = $this->containerSlots($collection, $containerKey)[$index] ?? [];

        return is_array($items) ? $items : [];
    }

    /**
     * @param  array<int, mixed>  $items
     * @return array<int, mixed>
     */
    private function removeSlot(array $items, int $index): array
    {
        unset($items[$index]);

        return array_values($items);
    }

    /**
     * @param  array<int, mixed>  $items
     * @return array<int, mixed>
     */
    private function insertSlot(array $items, int $index, mixed $item): array
    {
        return [
            ...array_slice($items, 0, $index),
            $item,
            ...array_slice($items, $index),
        ];
    }

    /**
     * @param  array<int, mixed>  $widgets
     * @return array<int, array<string, mixed>>
     */
    private function normalizeOccurrences(array $widgets): array
    {
        $occurrences = [];
        $normalized = [];

        foreach ($widgets as $widgetIndex => $widget) {
            $widget = is_array($widget) ? $widget : [];
            $widgetKeyValue = $widget['widget_key'] ?? '';
            $widgetKey = is_string($widgetKeyValue) ? $widgetKeyValue : '';
            $occurrences[$widgetKey] = ($occurrences[$widgetKey] ?? 0) + 1;
            $widget['occurrence'] = $occurrences[$widgetKey];
            $normalized[$widgetIndex] = $widget;
        }

        return $normalized;
    }

    /**
     * @param  array<array-key, mixed>  $containers
     * @param  array<array-key, mixed>  $assets
     * @return array<array-key, mixed>
     */
    private function syncAssetOccurrences(array $containers, array $assets): array
    {
        foreach ($containers as $containerKey => $container) {
            $container = is_array($container) ? $container : [];
            $widgets = is_array($container['widgets'] ?? null) ? $container['widgets'] : [];

            $containerAssets = is_array($assets[$containerKey] ?? null) ? $assets[$containerKey] : [];

            foreach ($widgets as $widgetIndex => $widget) {
                $widget = is_array($widget) ? $widget : [];
                $widgetAssets = is_array($containerAssets[$widgetIndex] ?? null) ? $containerAssets[$widgetIndex] : [];

                foreach (array_keys($widgetAssets) as $assetIndex) {
                    $assetData = is_array($widgetAssets[$assetIndex]) ? $widgetAssets[$assetIndex] : [];
                    $assetData['occurrence'] = $widget['occurrence'] ?? 1;
                    $widgetAssets[$assetIndex] = $assetData;
                }

                $containerAssets[$widgetIndex] = $widgetAssets;
            }

            $assets[$containerKey] = $containerAssets;
        }

        return $assets;
    }
}
