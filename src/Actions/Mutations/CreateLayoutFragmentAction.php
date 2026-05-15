<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\Mutations;

use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Data\LayoutFragmentData;
use Lorisleiva\Actions\Concerns\AsAction;

final class CreateLayoutFragmentAction
{
    use AsAction;

    public function handle(LayoutBuilderStateData $state, string $containerKey, ?int $elementIndex): LayoutFragmentData
    {
        $container = $state->containers[$containerKey] ?? null;

        if (! is_array($container)) {
            return new LayoutFragmentData(
                sourceContainerKey: $containerKey,
                sourceElementIndex: $elementIndex,
                container: null,
                element: null,
            );
        }

        if ($elementIndex === null) {
            return new LayoutFragmentData(
                sourceContainerKey: $containerKey,
                sourceElementIndex: null,
                container: $container,
                element: null,
                assets: $state->assets[$containerKey] ?? [],
                originalAssets: $state->originalAssets[$containerKey] ?? [],
                selectedRecords: $state->selectedRecords[$containerKey] ?? [],
            );
        }

        $element = $container['elements'][$elementIndex] ?? null;

        return new LayoutFragmentData(
            sourceContainerKey: $containerKey,
            sourceElementIndex: $elementIndex,
            container: null,
            element: is_array($element) ? $element : null,
            assets: $state->assets[$containerKey][$elementIndex] ?? [],
            originalAssets: $state->originalAssets[$containerKey][$elementIndex] ?? [],
            selectedRecords: $state->selectedRecords[$containerKey][$elementIndex] ?? [],
        );
    }
}
