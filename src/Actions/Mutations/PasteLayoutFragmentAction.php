<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\Mutations;

use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Data\LayoutChangeData;
use Capell\LayoutBuilder\Data\LayoutFragmentData;
use Capell\LayoutBuilder\Data\LayoutMutationResultData;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

final class PasteLayoutFragmentAction
{
    use AsAction;

    public function handle(
        LayoutBuilderStateData $state,
        LayoutFragmentData $fragment,
        string $targetContainerKey,
        ?int $targetIndex = null,
    ): LayoutMutationResultData {
        if (! isset($state->containers[$targetContainerKey])) {
            return new LayoutMutationResultData($state);
        }

        if ($fragment->isContainerFragment()) {
            return $this->pasteContainer($state, $fragment);
        }

        if ($fragment->isElementFragment()) {
            return $this->pasteElement($state, $fragment, $targetContainerKey, $targetIndex);
        }

        return new LayoutMutationResultData($state);
    }

    private function pasteContainer(LayoutBuilderStateData $state, LayoutFragmentData $fragment): LayoutMutationResultData
    {
        if ($fragment->container === null) {
            return new LayoutMutationResultData($state);
        }

        $containers = $state->containers;
        $containerKey = $this->availableContainerKey($containers, $fragment->sourceContainerKey);

        $usedAnchors = $this->usedAnchors($containers);
        $containers[$containerKey] = $this->withUniqueAnchors($fragment->container, $usedAnchors);

        $assets = $state->assets;
        $assets[$containerKey] = $fragment->assets;

        $originalAssets = $state->originalAssets;
        $originalAssets[$containerKey] = $fragment->originalAssets;

        $selectedRecords = $state->selectedRecords;
        $selectedRecords[$containerKey] = $fragment->selectedRecords;

        return NormalizeLayoutBuilderStateAction::run(new LayoutBuilderStateData(
            containers: $containers,
            assets: $assets,
            originalAssets: $originalAssets,
            selectedRecords: $selectedRecords,
        ))->withChanges([
            new LayoutChangeData(
                type: 'container_pasted',
                label: __('capell-layout-builder::message.container_pasted', ['container' => $containerKey]),
                containerKey: $containerKey,
                elementIndex: null,
            ),
        ]);
    }

    private function pasteElement(
        LayoutBuilderStateData $state,
        LayoutFragmentData $fragment,
        string $targetContainerKey,
        ?int $targetIndex,
    ): LayoutMutationResultData {
        if ($fragment->element === null) {
            return new LayoutMutationResultData($state);
        }

        $containers = $state->containers;
        $elements = $containers[$targetContainerKey]['elements'] ?? [];
        $targetIndex = min(count($elements), max(0, $targetIndex ?? count($elements)));
        $usedAnchors = $this->usedAnchors($containers);
        $element = $this->withUniqueElementAnchor($fragment->element, $usedAnchors);

        $containers[$targetContainerKey]['elements'] = $this->insertSlot($elements, $targetIndex, $element);

        $assets = $state->assets;
        $assets[$targetContainerKey] = $this->insertSlot($assets[$targetContainerKey] ?? [], $targetIndex, $fragment->assets);

        $originalAssets = $state->originalAssets;
        $originalAssets[$targetContainerKey] = $this->insertSlot($originalAssets[$targetContainerKey] ?? [], $targetIndex, $fragment->originalAssets);

        $selectedRecords = $state->selectedRecords;
        $selectedRecords[$targetContainerKey] = $this->insertSlot($selectedRecords[$targetContainerKey] ?? [], $targetIndex, $fragment->selectedRecords);

        return NormalizeLayoutBuilderStateAction::run(new LayoutBuilderStateData(
            containers: $containers,
            assets: $assets,
            originalAssets: $originalAssets,
            selectedRecords: $selectedRecords,
        ))->withChanges([
            new LayoutChangeData(
                type: 'element_pasted',
                label: __('capell-layout-builder::message.element_pasted', ['container' => $targetContainerKey]),
                containerKey: $targetContainerKey,
                elementIndex: $targetIndex,
            ),
        ]);
    }

    /**
     * @param  array<string, mixed>  $containers
     */
    private function availableContainerKey(array $containers, string $baseKey): string
    {
        $candidate = $baseKey . '-copy';
        $suffix = 2;

        while (array_key_exists($candidate, $containers)) {
            $candidate = $baseKey . '-copy-' . $suffix;
            $suffix++;
        }

        return $candidate;
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
     * @param  array<string, mixed>  $containers
     * @return array<string, bool>
     */
    private function usedAnchors(array $containers): array
    {
        $anchors = [];

        foreach ($containers as $container) {
            if (! is_array($container)) {
                continue;
            }

            $elements = is_array($container['elements'] ?? null) ? $container['elements'] : [];

            foreach ($elements as $element) {
                if (! is_array($element)) {
                    continue;
                }

                $anchor = $element['meta']['block_settings']['anchor_id'] ?? null;
                if (! is_string($anchor)) {
                    continue;
                }

                if (trim($anchor) === '') {
                    continue;
                }

                $anchors[Str::slug($anchor)] = true;
            }
        }

        return $anchors;
    }

    /**
     * @param  array<string, bool>  $usedAnchors
     * @return array<string, mixed>
     */
    private function withUniqueAnchors(array $container, array &$usedAnchors): array
    {
        $elements = is_array($container['elements'] ?? null) ? $container['elements'] : [];

        foreach ($elements as &$element) {
            $element = $this->withUniqueElementAnchor($element, $usedAnchors);
        }

        $container['elements'] = $elements;

        return $container;
    }

    /**
     * @param  array<string, bool>  $usedAnchors
     */
    private function withUniqueElementAnchor(mixed $element, array &$usedAnchors): mixed
    {
        if (! is_array($element)) {
            return $element;
        }

        $anchor = $element['meta']['block_settings']['anchor_id'] ?? null;
        if (! is_string($anchor) || trim($anchor) === '') {
            return $element;
        }

        $baseAnchor = Str::slug($anchor);
        $uniqueAnchor = $baseAnchor;
        $suffix = 2;

        while (isset($usedAnchors[$uniqueAnchor])) {
            $uniqueAnchor = $baseAnchor . '-' . $suffix;
            $suffix++;
        }

        $element['meta']['block_settings']['anchor_id'] = $uniqueAnchor;
        $usedAnchors[$uniqueAnchor] = true;

        return $element;
    }
}
