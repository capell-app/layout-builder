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
            $containers[$containerKey]['blocks'] = array_values($container['blocks'] ?? []);
            $containers[$containerKey]['meta'] = $this->normalizeContainerMeta($container['meta'] ?? []);

            $blockCount = count($containers[$containerKey]['blocks']);
            $assets[$containerKey] = $this->normalizeBlockSlots($assets[$containerKey] ?? [], $blockCount);
            $originalAssets[$containerKey] = $this->normalizeBlockSlots($originalAssets[$containerKey] ?? [], $blockCount);
            $selectedRecords[$containerKey] = $this->normalizeBlockSlots($selectedRecords[$containerKey] ?? [], $blockCount);
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
    private function normalizeBlockSlots(array $slots, int $blockCount): array
    {
        $normalizedSlots = [];

        for ($blockIndex = 0; $blockIndex < $blockCount; $blockIndex++) {
            $normalizedSlots[$blockIndex] = $slots[$blockIndex] ?? [];
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
