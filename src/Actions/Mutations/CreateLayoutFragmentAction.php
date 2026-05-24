<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\Mutations;

use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Data\LayoutFragmentData;
use Lorisleiva\Actions\Concerns\AsAction;

final class CreateLayoutFragmentAction
{
    use AsAction;

    public function handle(LayoutBuilderStateData $state, string $containerKey, ?int $blockIndex): LayoutFragmentData
    {
        $container = $state->containers[$containerKey] ?? null;

        if (! is_array($container)) {
            return new LayoutFragmentData(
                sourceContainerKey: $containerKey,
                sourceBlockIndex: $blockIndex,
                container: null,
                block: null,
            );
        }

        if ($blockIndex === null) {
            return new LayoutFragmentData(
                sourceContainerKey: $containerKey,
                sourceBlockIndex: null,
                container: $container,
                block: null,
                assets: $state->assets[$containerKey] ?? [],
                originalAssets: $state->originalAssets[$containerKey] ?? [],
                selectedRecords: $state->selectedRecords[$containerKey] ?? [],
            );
        }

        $block = $container['widgets'][$blockIndex] ?? null;

        return new LayoutFragmentData(
            sourceContainerKey: $containerKey,
            sourceBlockIndex: $blockIndex,
            container: null,
            block: is_array($block) ? $block : null,
            assets: $state->assets[$containerKey][$blockIndex] ?? [],
            originalAssets: $state->originalAssets[$containerKey][$blockIndex] ?? [],
            selectedRecords: $state->selectedRecords[$containerKey][$blockIndex] ?? [],
        );
    }
}
