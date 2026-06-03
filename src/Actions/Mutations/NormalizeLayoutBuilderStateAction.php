<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\Mutations;

use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Data\LayoutMutationResultData;
use Capell\LayoutBuilder\Enums\LayoutBreakpoint;
use Lorisleiva\Actions\Concerns\AsAction;

final class NormalizeLayoutBuilderStateAction
{
    use AsAction;

    public function handle(LayoutBuilderStateData $state): LayoutMutationResultData
    {
        $containers = $state->containers;
        $assets = $state->assets;
        $originalAssets = $state->originalAssets;
        $selectedRecords = $state->selectedRecords;

        foreach ($containers as $containerKey => $container) {
            $containers[$containerKey]['widgets'] = array_values($container['widgets'] ?? []);
            $containers[$containerKey]['meta'] = $this->normalizeContainerMeta($container['meta'] ?? []);

            $widgetCount = count($containers[$containerKey]['widgets']);
            $assets[$containerKey] = $this->normalizeWidgetSlots($assets[$containerKey] ?? [], $widgetCount);
            $originalAssets[$containerKey] = $this->normalizeWidgetSlots($originalAssets[$containerKey] ?? [], $widgetCount);
            $selectedRecords[$containerKey] = $this->normalizeWidgetSlots($selectedRecords[$containerKey] ?? [], $widgetCount);
        }

        return new LayoutMutationResultData(
            state: new LayoutBuilderStateData(
                containers: $containers,
                assets: $assets,
                originalAssets: $originalAssets,
                selectedRecords: $selectedRecords,
            ),
        );
    }

    /**
     * @param  array<int, mixed>  $slots
     * @return array<int, mixed>
     */
    private function normalizeWidgetSlots(array $slots, int $widgetCount): array
    {
        $normalizedSlots = [];

        for ($widgetIndex = 0; $widgetIndex < $widgetCount; $widgetIndex++) {
            $normalizedSlots[$widgetIndex] = $slots[$widgetIndex] ?? [];
        }

        return $normalizedSlots;
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    private function normalizeContainerMeta(array $meta): array
    {
        $baseColspan = array_key_exists('colspan', $meta)
            ? $this->clampColspan((int) $meta['colspan'])
            : 12;

        if (array_key_exists('colspan', $meta)) {
            $meta['colspan'] = $baseColspan;
        }

        $responsive = [];
        foreach (($meta['responsive'] ?? []) as $breakpoint => $settings) {
            if (LayoutBreakpoint::tryFrom((string) $breakpoint) === null) {
                continue;
            }

            $responsive[(string) $breakpoint] = [
                'colspan' => $this->clampColspan((int) ($settings['colspan'] ?? $baseColspan)),
            ];
        }

        if ($responsive === []) {
            unset($meta['responsive']);

            return $meta;
        }

        $meta['responsive'] = $responsive;

        return $meta;
    }

    private function clampColspan(int $colspan): int
    {
        return min(12, max(1, $colspan));
    }
}
