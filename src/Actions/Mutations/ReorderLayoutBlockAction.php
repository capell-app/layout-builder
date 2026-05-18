<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\Mutations;

use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Data\LayoutChangeData;
use Capell\LayoutBuilder\Data\LayoutMutationResultData;
use Lorisleiva\Actions\Concerns\AsAction;

final class ReorderLayoutBlockAction
{
    use AsAction;

    public function handle(
        LayoutBuilderStateData $state,
        string $originalContainer,
        string $targetContainer,
        int $originalIndex,
        int $targetIndex,
    ): LayoutMutationResultData {
        if (! isset($state->containers[$originalContainer]['blocks'][$originalIndex])) {
            return new LayoutMutationResultData($state);
        }

        if (! isset($state->containers[$targetContainer])) {
            return new LayoutMutationResultData($state);
        }

        $containers = $state->containers;
        $assets = $state->assets;
        $originalAssets = $state->originalAssets;
        $selectedRecords = $state->selectedRecords;

        $block = $containers[$originalContainer]['blocks'][$originalIndex];
        $blockAssets = $assets[$originalContainer][$originalIndex] ?? [];
        $blockOriginalAssets = $originalAssets[$originalContainer][$originalIndex] ?? [];
        $blockSelectedRecords = $selectedRecords[$originalContainer][$originalIndex] ?? [];

        $containers[$originalContainer]['blocks'] = $this->removeSlot(
            items: $containers[$originalContainer]['blocks'],
            index: $originalIndex,
        );
        $assets[$originalContainer] = $this->removeSlot($assets[$originalContainer] ?? [], $originalIndex);
        $originalAssets[$originalContainer] = $this->removeSlot($originalAssets[$originalContainer] ?? [], $originalIndex);
        $selectedRecords[$originalContainer] = $this->removeSlot($selectedRecords[$originalContainer] ?? [], $originalIndex);

        $targetIndex = min(
            count($containers[$targetContainer]['blocks'] ?? []),
            max(0, $targetIndex),
        );

        $containers[$targetContainer]['blocks'] = $this->insertSlot(
            items: $containers[$targetContainer]['blocks'] ?? [],
            index: $targetIndex,
            item: $block,
        );
        $assets[$targetContainer] = $this->insertSlot($assets[$targetContainer] ?? [], $targetIndex, $blockAssets);
        $originalAssets[$targetContainer] = $this->insertSlot($originalAssets[$targetContainer] ?? [], $targetIndex, $blockOriginalAssets);
        $selectedRecords[$targetContainer] = $this->insertSlot($selectedRecords[$targetContainer] ?? [], $targetIndex, $blockSelectedRecords);

        $containers[$targetContainer]['blocks'] = $this->normalizeOccurrences($containers[$targetContainer]['blocks']);

        if ($originalContainer !== $targetContainer) {
            $containers[$originalContainer]['blocks'] = $this->normalizeOccurrences($containers[$originalContainer]['blocks']);
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
                type: 'block_reordered',
                label: __('capell-layout-builder::message.block_reordered', ['container' => $targetContainer]),
                containerKey: $targetContainer,
                blockIndex: $targetIndex,
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
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<int, array<string, mixed>>
     */
    private function normalizeOccurrences(array $blocks): array
    {
        $occurrences = [];

        foreach ($blocks as $blockIndex => $block) {
            $blockKey = (string) ($block['block_key'] ?? '');
            $occurrences[$blockKey] = ($occurrences[$blockKey] ?? 0) + 1;
            $blocks[$blockIndex]['occurrence'] = $occurrences[$blockKey];
        }

        return $blocks;
    }

    /**
     * @param  array<string, array<string, mixed>>  $containers
     * @param  array<string, array<int, array<int, array<string, mixed>>>>  $assets
     * @return array<string, array<int, array<int, array<string, mixed>>>>
     */
    private function syncAssetOccurrences(array $containers, array $assets): array
    {
        foreach ($containers as $containerKey => $container) {
            foreach (($container['blocks'] ?? []) as $blockIndex => $block) {
                foreach (array_keys($assets[$containerKey][$blockIndex] ?? []) as $assetIndex) {
                    $assets[$containerKey][$blockIndex][$assetIndex]['occurrence'] = $block['occurrence'] ?? 1;
                }
            }
        }

        return $assets;
    }
}
