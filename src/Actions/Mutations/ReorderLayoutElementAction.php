<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\Mutations;

use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Data\LayoutChangeData;
use Capell\LayoutBuilder\Data\LayoutMutationResultData;
use Lorisleiva\Actions\Concerns\AsAction;

final class ReorderLayoutElementAction
{
    use AsAction;

    public function handle(
        LayoutBuilderStateData $state,
        string $originalContainer,
        string $targetContainer,
        int $originalIndex,
        int $targetIndex,
    ): LayoutMutationResultData {
        if (! isset($state->containers[$originalContainer]['elements'][$originalIndex])) {
            return new LayoutMutationResultData($state);
        }

        if (! isset($state->containers[$targetContainer])) {
            return new LayoutMutationResultData($state);
        }

        $containers = $state->containers;
        $assets = $state->assets;
        $originalAssets = $state->originalAssets;
        $selectedRecords = $state->selectedRecords;

        $element = $containers[$originalContainer]['elements'][$originalIndex];
        $elementAssets = $assets[$originalContainer][$originalIndex] ?? [];
        $elementOriginalAssets = $originalAssets[$originalContainer][$originalIndex] ?? [];
        $elementSelectedRecords = $selectedRecords[$originalContainer][$originalIndex] ?? [];

        $containers[$originalContainer]['elements'] = $this->removeSlot(
            items: $containers[$originalContainer]['elements'],
            index: $originalIndex,
        );
        $assets[$originalContainer] = $this->removeSlot($assets[$originalContainer] ?? [], $originalIndex);
        $originalAssets[$originalContainer] = $this->removeSlot($originalAssets[$originalContainer] ?? [], $originalIndex);
        $selectedRecords[$originalContainer] = $this->removeSlot($selectedRecords[$originalContainer] ?? [], $originalIndex);

        $targetIndex = min(
            count($containers[$targetContainer]['elements'] ?? []),
            max(0, $targetIndex),
        );

        $containers[$targetContainer]['elements'] = $this->insertSlot(
            items: $containers[$targetContainer]['elements'] ?? [],
            index: $targetIndex,
            item: $element,
        );
        $assets[$targetContainer] = $this->insertSlot($assets[$targetContainer] ?? [], $targetIndex, $elementAssets);
        $originalAssets[$targetContainer] = $this->insertSlot($originalAssets[$targetContainer] ?? [], $targetIndex, $elementOriginalAssets);
        $selectedRecords[$targetContainer] = $this->insertSlot($selectedRecords[$targetContainer] ?? [], $targetIndex, $elementSelectedRecords);

        $containers[$targetContainer]['elements'] = $this->normalizeOccurrences($containers[$targetContainer]['elements']);

        if ($originalContainer !== $targetContainer) {
            $containers[$originalContainer]['elements'] = $this->normalizeOccurrences($containers[$originalContainer]['elements']);
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
                type: 'element_reordered',
                label: __('capell-layout-builder::message.element_reordered', ['container' => $targetContainer]),
                containerKey: $targetContainer,
                elementIndex: $targetIndex,
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
     * @param  array<int, array<string, mixed>>  $elements
     * @return array<int, array<string, mixed>>
     */
    private function normalizeOccurrences(array $elements): array
    {
        $occurrences = [];

        foreach ($elements as $elementIndex => $element) {
            $elementKey = (string) ($element['element_key'] ?? '');
            $occurrences[$elementKey] = ($occurrences[$elementKey] ?? 0) + 1;
            $elements[$elementIndex]['occurrence'] = $occurrences[$elementKey];
        }

        return $elements;
    }

    /**
     * @param  array<string, array<string, mixed>>  $containers
     * @param  array<string, array<int, array<int, array<string, mixed>>>>  $assets
     * @return array<string, array<int, array<int, array<string, mixed>>>>
     */
    private function syncAssetOccurrences(array $containers, array $assets): array
    {
        foreach ($containers as $containerKey => $container) {
            foreach (($container['elements'] ?? []) as $elementIndex => $element) {
                foreach (array_keys($assets[$containerKey][$elementIndex] ?? []) as $assetIndex) {
                    $assets[$containerKey][$elementIndex][$assetIndex]['occurrence'] = $element['occurrence'] ?? 1;
                }
            }
        }

        return $assets;
    }
}
