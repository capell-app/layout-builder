<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\Mutations;

use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Data\LayoutFragmentData;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class CreateLayoutFragmentAction
{
    use AsFake;
    use AsObject;

    public function handle(LayoutBuilderStateData $state, string $containerKey, ?int $widgetIndex): LayoutFragmentData
    {
        $container = $state->containers[$containerKey] ?? null;

        if (! is_array($container)) {
            return new LayoutFragmentData(
                sourceContainerKey: $containerKey,
                sourceWidgetIndex: $widgetIndex,
                container: null,
                widget: null,
            );
        }

        if ($widgetIndex === null) {
            return new LayoutFragmentData(
                sourceContainerKey: $containerKey,
                sourceWidgetIndex: null,
                container: $container,
                widget: null,
                assets: $state->assets[$containerKey] ?? [],
                originalAssets: $state->originalAssets[$containerKey] ?? [],
                selectedRecords: $state->selectedRecords[$containerKey] ?? [],
            );
        }

        $widget = $container['widgets'][$widgetIndex] ?? null;

        return new LayoutFragmentData(
            sourceContainerKey: $containerKey,
            sourceWidgetIndex: $widgetIndex,
            container: null,
            widget: is_array($widget) ? $widget : null,
            assets: $state->assets[$containerKey][$widgetIndex] ?? [],
            originalAssets: $state->originalAssets[$containerKey][$widgetIndex] ?? [],
            selectedRecords: $state->selectedRecords[$containerKey][$widgetIndex] ?? [],
        );
    }
}
