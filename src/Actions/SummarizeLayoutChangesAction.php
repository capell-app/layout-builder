<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Data\LayoutChangeData;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class SummarizeLayoutChangesAction
{
    use AsFake;
    use AsObject;

    /**
     * @return array<int, LayoutChangeData>
     */
    public function handle(LayoutBuilderStateData $baseline, LayoutBuilderStateData $current): array
    {
        $changes = [];

        foreach ($current->containers as $containerKey => $container) {
            $baselineContainer = $baseline->containers[$containerKey] ?? null;

            if ($baselineContainer === null) {
                $changes[] = new LayoutChangeData(
                    type: 'container_added',
                    label: __('capell-layout-builder::message.container_added', ['container' => $containerKey]),
                    containerKey: $containerKey,
                    widgetIndex: null,
                );

                continue;
            }

            if (($baselineContainer['meta']['colspan'] ?? 12) !== ($container['meta']['colspan'] ?? 12)) {
                $changes[] = new LayoutChangeData(
                    type: 'container_resized',
                    label: __('capell-layout-builder::message.container_resized', ['container' => $containerKey]),
                    containerKey: $containerKey,
                    widgetIndex: null,
                );
            }

            if (($baselineContainer['meta']['responsive'] ?? []) !== ($container['meta']['responsive'] ?? [])) {
                $changes[] = new LayoutChangeData(
                    type: 'responsive_override_changed',
                    label: __('capell-layout-builder::message.responsive_override_changed', ['container' => $containerKey]),
                    containerKey: $containerKey,
                    widgetIndex: null,
                );
            }
        }

        foreach (array_keys($baseline->containers) as $containerKey) {
            if (! array_key_exists($containerKey, $current->containers)) {
                $changes[] = new LayoutChangeData(
                    type: 'container_removed',
                    label: __('capell-layout-builder::message.container_removed', ['container' => $containerKey]),
                    containerKey: $containerKey,
                    widgetIndex: null,
                );
            }
        }

        return $changes;
    }
}
