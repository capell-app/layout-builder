<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Data\LayoutChangeData;
use Capell\LayoutBuilder\Data\LayoutMutationResultData;
use Capell\LayoutBuilder\Data\LayoutPresetLinkData;
use Capell\LayoutBuilder\Enums\LayoutPresetMode;
use Capell\LayoutBuilder\Models\LayoutPreset;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsObject;

final class InsertLinkedLayoutPresetAction
{
    use AsObject;

    public function handle(LayoutBuilderStateData $state, LayoutPreset $preset, string $targetContainerKey, bool $linked = true): LayoutMutationResultData
    {
        throw_unless(isset($state->containers[$targetContainerKey]), InvalidArgumentException::class, 'The target container does not exist.');
        throw_unless($preset->mode === LayoutPresetMode::Linked || ! $linked, InvalidArgumentException::class, 'Only linked presets can be inserted as linked containers.');

        $items = $this->items($preset);
        throw_if($items === [], InvalidArgumentException::class, 'The linked layout preset has no valid items.');

        $containers = $state->containers;
        $assets = $state->assets;
        $originalAssets = $state->originalAssets;
        $selectedRecords = $state->selectedRecords;
        $insertedKeys = [];
        $usedAnchors = $this->usedAnchors($containers);

        foreach ($items as $item) {
            $containerKey = $this->availableContainerKey($containers, $item['source_key']);
            $container = $this->withUniqueAnchors($item['container'], $usedAnchors);

            if ($linked) {
                $container = LinkLayoutPresetContainerAction::run(
                    $container,
                    new LayoutPresetLinkData((int) $preset->getKey(), $item['id'], $preset->key),
                );
            }

            $containers = $this->insertAfter($containers, $targetContainerKey, $containerKey, $container);
            $assets[$containerKey] = [];
            $originalAssets[$containerKey] = [];
            $selectedRecords[$containerKey] = [];
            $insertedKeys[] = $containerKey;
            $targetContainerKey = $containerKey;
        }

        return new LayoutMutationResultData(new LayoutBuilderStateData(
            containers: $containers,
            assets: $assets,
            originalAssets: $originalAssets,
            selectedRecords: $selectedRecords,
        ), changes: array_map(
            static fn (string $containerKey): LayoutChangeData => new LayoutChangeData(
                type: 'linked_preset_inserted',
                label: __('capell-layout-builder::message.layout_preset_linked_inserted', ['container' => $containerKey]),
                containerKey: $containerKey,
                widgetIndex: null,
            ),
            $insertedKeys,
        ));
    }

    /**
     * @return list<array{id: string, source_key: string, container: array<string, mixed>}>
     */
    private function items(LayoutPreset $preset): array
    {
        $snapshot = is_array($preset->snapshot) ? $preset->snapshot : [];
        $items = is_array($snapshot['items'] ?? null) ? $snapshot['items'] : [];

        return array_values(array_filter($items, static fn (mixed $item): bool => is_array($item)
                && is_string($item['id'] ?? null)
                && trim($item['id']) !== ''
                && is_string($item['source_key'] ?? null)
                && trim($item['source_key']) !== ''
                && is_array($item['container'] ?? null)));
    }

    /**
     * @param  array<string, array<string, mixed>>  $containers
     * @param  array<string, mixed>  $container
     * @return array<string, array<string, mixed>>
     */
    private function insertAfter(array $containers, string $targetContainerKey, string $containerKey, array $container): array
    {
        $result = [];

        foreach ($containers as $key => $currentContainer) {
            $result[$key] = $currentContainer;

            if ($key === $targetContainerKey) {
                $result[$containerKey] = $container;
            }
        }

        return $result;
    }

    /**
     * @param  array<string, array<string, mixed>>  $containers
     */
    private function availableContainerKey(array $containers, string $sourceKey): string
    {
        $candidate = $sourceKey;
        $suffix = 2;

        while (array_key_exists($candidate, $containers)) {
            $candidate = $sourceKey . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    /**
     * @param  array<string, array<string, mixed>>  $containers
     * @return array<string, bool>
     */
    private function usedAnchors(array $containers): array
    {
        $anchors = [];

        foreach ($containers as $container) {
            foreach ($this->widgets($container) as $widget) {
                $anchor = data_get($widget, 'meta.widget_settings.anchor_id');
                if (is_string($anchor) && trim($anchor) !== '') {
                    $anchors[Str::slug($anchor)] = true;
                }
            }
        }

        return $anchors;
    }

    /**
     * @param  array<string, mixed>  $container
     * @param  array<string, bool>  $usedAnchors
     * @return array<string, mixed>
     */
    private function withUniqueAnchors(array $container, array &$usedAnchors): array
    {
        $widgets = $this->widgets($container);

        foreach ($widgets as $index => $widget) {
            $anchor = data_get($widget, 'meta.widget_settings.anchor_id');
            if (! is_string($anchor) || trim($anchor) === '') {
                continue;
            }

            $base = Str::slug($anchor);
            $candidate = $base;
            $suffix = 2;

            while (isset($usedAnchors[$candidate])) {
                $candidate = $base . '-' . $suffix;
                $suffix++;
            }

            $widgets[$index]['meta']['widget_settings']['anchor_id'] = $candidate;
            $usedAnchors[$candidate] = true;
        }

        $container['widgets'] = $widgets;

        return $container;
    }

    /**
     * @param  array<string, mixed>  $container
     * @return array<int, array<string, mixed>>
     */
    private function widgets(array $container): array
    {
        $widgets = $container['widgets'] ?? [];

        return is_array($widgets) ? array_values(array_filter($widgets, static fn (mixed $widget): bool => is_array($widget))) : [];
    }
}
