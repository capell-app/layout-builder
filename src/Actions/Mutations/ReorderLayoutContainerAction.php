<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\Mutations;

use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Data\LayoutChangeData;
use Capell\LayoutBuilder\Data\LayoutMutationResultData;
use Lorisleiva\Actions\Concerns\AsAction;

final class ReorderLayoutContainerAction
{
    use AsAction;

    public function handle(LayoutBuilderStateData $state, string $containerKey, int $position): LayoutMutationResultData
    {
        if (! array_key_exists($containerKey, $state->containers)) {
            return new LayoutMutationResultData($state);
        }

        $containers = $state->containers;
        $container = $containers[$containerKey];
        unset($containers[$containerKey]);

        $position = min(count($containers), max(0, $position));
        $containers = array_slice($containers, 0, $position, true)
            + [$containerKey => $container]
            + array_slice($containers, $position, null, true);

        return NormalizeLayoutBuilderStateAction::run(new LayoutBuilderStateData(
            containers: $containers,
            assets: $state->assets,
            originalAssets: $state->originalAssets,
            selectedRecords: $state->selectedRecords,
        ))->withChanges([
            new LayoutChangeData(
                type: 'container_reordered',
                label: __('capell-layout-builder::message.container_reordered', ['container' => $containerKey]),
                containerKey: $containerKey,
                widgetIndex: null,
            ),
        ]);
    }
}
