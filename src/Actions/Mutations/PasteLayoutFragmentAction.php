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

        if ($fragment->isWidgetFragment()) {
            return $this->pasteWidget($state, $fragment, $targetContainerKey, $targetIndex);
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
                widgetIndex: null,
            ),
        ]);
    }

    private function pasteWidget(
        LayoutBuilderStateData $state,
        LayoutFragmentData $fragment,
        string $targetContainerKey,
        ?int $targetIndex,
    ): LayoutMutationResultData {
        if ($fragment->widget === null) {
            return new LayoutMutationResultData($state);
        }

        $containers = $state->containers;
        $widgets = $containers[$targetContainerKey]['widgets'] ?? [];
        $targetIndex = min(count($widgets), max(0, $targetIndex ?? count($widgets)));
        $usedAnchors = $this->usedAnchors($containers);
        $widget = $this->withUniqueWidgetAnchor($fragment->widget, $usedAnchors);

        $containers[$targetContainerKey]['widgets'] = $this->insertSlot($widgets, $targetIndex, $widget);

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
                type: 'widget_pasted',
                label: __('capell-layout-builder::message.widget_pasted', ['container' => $targetContainerKey]),
                containerKey: $targetContainerKey,
                widgetIndex: $targetIndex,
            ),
        ]);
    }

    /**
     * @param  array<array-key, mixed>  $containers
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
     * @param  array<array-key, mixed>  $containers
     * @return array<string, bool>
     */
    private function usedAnchors(array $containers): array
    {
        $anchors = [];

        foreach ($containers as $container) {
            if (! is_array($container)) {
                continue;
            }

            $widgets = is_array($container['widgets'] ?? null) ? $container['widgets'] : [];

            foreach ($widgets as $widget) {
                if (! is_array($widget)) {
                    continue;
                }

                $anchor = $widget['meta']['widget_settings']['anchor_id'] ?? null;
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
     * @param  array<array-key, mixed>  $container
     * @return array<array-key, mixed>
     */
    private function withUniqueAnchors(array $container, array &$usedAnchors): array
    {
        $widgets = is_array($container['widgets'] ?? null) ? $container['widgets'] : [];

        foreach ($widgets as &$widget) {
            $widget = $this->withUniqueWidgetAnchor($widget, $usedAnchors);
        }

        $container['widgets'] = $widgets;

        return $container;
    }

    /**
     * @param  array<string, bool>  $usedAnchors
     */
    private function withUniqueWidgetAnchor(mixed $widget, array &$usedAnchors): mixed
    {
        if (! is_array($widget)) {
            return $widget;
        }

        $anchor = $widget['meta']['widget_settings']['anchor_id'] ?? null;
        if (! is_string($anchor) || trim($anchor) === '') {
            return $widget;
        }

        $baseAnchor = Str::slug($anchor);
        $uniqueAnchor = $baseAnchor;
        $suffix = 2;

        while (isset($usedAnchors[$uniqueAnchor])) {
            $uniqueAnchor = $baseAnchor . '-' . $suffix;
            $suffix++;
        }

        $widget['meta']['widget_settings']['anchor_id'] = $uniqueAnchor;
        $usedAnchors[$uniqueAnchor] = true;

        return $widget;
    }
}
