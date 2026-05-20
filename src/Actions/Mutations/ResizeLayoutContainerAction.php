<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\Mutations;

use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Data\LayoutChangeData;
use Capell\LayoutBuilder\Data\LayoutMutationResultData;
use Capell\LayoutBuilder\Enums\LayoutBreakpoint;
use Lorisleiva\Actions\Concerns\AsAction;

final class ResizeLayoutContainerAction
{
    use AsAction;

    public function handle(
        LayoutBuilderStateData $state,
        string $containerKey,
        int $colspan,
        ?LayoutBreakpoint $breakpoint,
    ): LayoutMutationResultData {
        if (! isset($state->containers[$containerKey])) {
            return new LayoutMutationResultData($state);
        }

        $containers = $state->containers;
        $colspan = min(12, max(1, $colspan));

        if (! $breakpoint instanceof LayoutBreakpoint) {
            $containers[$containerKey]['meta']['colspan'] = $colspan;
        } else {
            $containers[$containerKey]['meta']['responsive'][$breakpoint->value]['colspan'] = $colspan;
        }

        return NormalizeLayoutBuilderStateAction::run(new LayoutBuilderStateData(
            containers: $containers,
            assets: $state->assets,
            originalAssets: $state->originalAssets,
            selectedRecords: $state->selectedRecords,
        ))->withChanges([
            new LayoutChangeData(
                type: 'container_resized',
                label: __('capell-layout-builder::message.container_resized', ['container' => $containerKey]),
                containerKey: $containerKey,
                blockIndex: null,
            ),
        ]);
    }
}
