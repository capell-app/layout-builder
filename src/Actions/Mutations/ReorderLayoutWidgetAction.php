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
        if (! isset($state->containers[$originalContainer]['widgets'][$originalIndex])) {
            return new LayoutMutationResultData($state);
        }

        if (! isset($state->containers[$targetContainer])) {
            return new LayoutMutationResultData($state);
        }

        $containers = $state->containers;
        $assets = $state->assets;
        $originalAssets = $state->originalAssets;
        $selectedRecords = $state->selectedRecords;

        $widget = $containers[$originalContainer]['widgets'][$originalIndex];
        $widgetAssets = $assets[$originalContainer][$originalIndex] ?? [];
        $widgetOriginalAssets = $originalAssets[$originalContainer][$originalIndex] ?? [];
        $widgetSelectedRecords = $selectedRecords[$originalContainer][$originalIndex] ?? [];

        $containers[$originalContainer]['widgets'] = $this->removeSlot(
            items: $containers[$originalContainer]['widgets'],
            index: $originalIndex,
        );
        $assets[$originalContainer] = $this->removeSlot($assets[$originalContainer] ?? [], $originalIndex);
        $originalAssets[$originalContainer] = $this->removeSlot($originalAssets[$originalContainer] ?? [], $originalIndex);
        $selectedRecords[$originalContainer] = $this->removeSlot($selectedRecords[$originalContainer] ?? [], $originalIndex);

        $targetIndex = min(
            count($containers[$targetContainer]['widgets'] ?? []),
            max(0, $targetIndex),
        );

        $containers[$targetContainer]['widgets'] = $this->insertSlot(
            items: $containers[$targetContainer]['widgets'] ?? [],
            index: $targetIndex,
            item: $widget,
        );
        $assets[$targetContainer] = $this->insertSlot($assets[$targetContainer] ?? [], $targetIndex, $widgetAssets);
        $originalAssets[$targetContainer] = $this->insertSlot($originalAssets[$targetContainer] ?? [], $targetIndex, $widgetOriginalAssets);
        $selectedRecords[$targetContainer] = $this->insertSlot($selectedRecords[$targetContainer] ?? [], $targetIndex, $widgetSelectedRecords);

        $containers[$targetContainer]['widgets'] = $this->normalizeOccurrences($containers[$targetContainer]['widgets']);

        if ($originalContainer !== $targetContainer) {
            $containers[$originalContainer]['widgets'] = $this->normalizeOccurrences($containers[$originalContainer]['widgets']);
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
     * @param  array<int, array<string, mixed>>  $widgets
     * @return array<int, array<string, mixed>>
     */
    private function normalizeOccurrences(array $widgets): array
    {
        $occurrences = [];

        foreach ($widgets as $widgetIndex => $widget) {
            $widgetKey = (string) ($widget['widget_key'] ?? '');
            $occurrences[$widgetKey] = ($occurrences[$widgetKey] ?? 0) + 1;
            $widgets[$widgetIndex]['occurrence'] = $occurrences[$widgetKey];
        }

        return $widgets;
    }

    /**
     * @param  array<string, array<string, mixed>>  $containers
     * @param  array<string, array<int, array<int, array<string, mixed>>>>  $assets
     * @return array<string, array<int, array<int, array<string, mixed>>>>
     */
    private function syncAssetOccurrences(array $containers, array $assets): array
    {
        foreach ($containers as $containerKey => $container) {
            foreach (($container['widgets'] ?? []) as $widgetIndex => $widget) {
                foreach (array_keys($assets[$containerKey][$widgetIndex] ?? []) as $assetIndex) {
                    $assets[$containerKey][$widgetIndex][$assetIndex]['occurrence'] = $widget['occurrence'] ?? 1;
                }
            }
        }

        return $assets;
    }
}
