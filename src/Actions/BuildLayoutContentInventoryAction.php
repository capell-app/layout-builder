<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Contracts\LayoutContentGroupContributor;
use Capell\LayoutBuilder\Data\LayoutContentInventoryContextData;
use Capell\LayoutBuilder\Data\LayoutContentInventoryData;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\LayoutBuilder\Support\ContentInventory\LayoutContentInventoryGrouper;
use Capell\LayoutBuilder\Support\ContentInventory\LayoutContentInventoryItemFactory;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class BuildLayoutContentInventoryAction
{
    use AsFake;
    use AsObject;

    public function __construct(
        private readonly LayoutContentInventoryItemFactory $itemFactory = new LayoutContentInventoryItemFactory,
        private readonly ?LayoutContentInventoryGrouper $grouper = null,
    ) {}

    /**
     * @param  array<string, array<string, mixed>>  $containers
     * @param  array<string, array<int, Widget>>  $containerWidgets
     * @param  array<string, array<int, array<int, array<string, mixed>>>>  $assets
     * @param  iterable<int, LayoutContentGroupContributor>|null  $contributors
     */
    public function handle(
        Layout $layout,
        ?Pageable $page,
        array $containers,
        array $containerWidgets,
        array $assets,
        string $signature,
        ?iterable $contributors = null,
        ?string $siteName = null,
        ?string $languageName = null,
    ): LayoutContentInventoryData {
        $context = new LayoutContentInventoryContextData(
            layout: $layout,
            page: $page,
            siteName: $siteName,
            languageName: $languageName,
        );

        $contributors = $this->contributors($contributors);
        $reusedAssetKeys = $this->reusedAssetKeys($assets);
        $groups = [];
        $itemCount = 0;

        foreach ($containers as $containerOrder => $container) {
            $containerKey = (string) $containerOrder;
            $containerLabel = $this->itemFactory->containerLabel($containerKey, $container);

            foreach ($this->widgets($container) as $widgetIndex => $containerWidget) {
                $widget = $containerWidgets[$containerKey][$widgetIndex] ?? null;

                if (! $widget instanceof Widget) {
                    continue;
                }

                $widgetLabel = $this->itemFactory->widgetLabel($widget, $containerWidget);
                $widgetCopy = $this->itemFactory->widgetCopy($widget);

                if ($widgetCopy['text'] !== null) {
                    $item = $this->itemFactory->widgetCopyItem(
                        containerKey: $containerKey,
                        widgetIndex: $widgetIndex,
                        containerWidget: $containerWidget,
                        widget: $widget,
                        containerLabel: $containerLabel,
                        widgetLabel: $widgetLabel,
                        widgetCopy: $widgetCopy,
                        meta: $this->arrayValue($containerWidget['meta'] ?? []),
                    );

                    foreach ($contributors as $contributor) {
                        $item = $contributor->item($item, $context);
                    }

                    $groups = $this->grouper()->appendItemToOwnershipGroup($groups, $item, $contributors, $context);
                    $itemCount++;
                }

                foreach (($assets[$containerKey][$widgetIndex] ?? []) as $assetIndex => $assetState) {
                    $assetIndex = $this->integerIndex($assetIndex);

                    if ($assetIndex === null) {
                        continue;
                    }

                    $widgetAsset = $this->itemFactory->resolveWidgetAsset($widget, $assetState, $assetIndex);

                    if (! $widgetAsset instanceof WidgetAsset) {
                        continue;
                    }

                    $assetKey = $this->itemFactory->assetKey($assetState);
                    $item = $this->itemFactory->assetItem(
                        containerKey: $containerKey,
                        widgetIndex: $widgetIndex,
                        assetIndex: $assetIndex,
                        assetState: $assetState,
                        widgetAsset: $widgetAsset,
                        signature: $signature,
                        containerLabel: $containerLabel,
                        widgetLabel: $widgetLabel,
                        isReused: isset($reusedAssetKeys[$assetKey]),
                        widgetCopy: $widgetCopy,
                    );

                    foreach ($contributors as $contributor) {
                        $item = $contributor->item($item, $context);
                    }

                    $groups = $this->grouper()->appendItemToOwnershipGroup($groups, $item, $contributors, $context);
                    $itemCount++;
                }
            }
        }

        return new LayoutContentInventoryData(
            groups: array_values($groups),
            itemCount: $itemCount,
            signature: $signature,
        );
    }

    /**
     * @param  iterable<int, LayoutContentGroupContributor>|null  $contributors
     * @return array<int, LayoutContentGroupContributor>
     */
    private function contributors(?iterable $contributors): array
    {
        $contributors ??= app()->tagged(LayoutContentGroupContributor::TAG);

        return collect($contributors)
            ->filter(fn (mixed $contributor): bool => $contributor instanceof LayoutContentGroupContributor)
            ->sortBy(fn (LayoutContentGroupContributor $contributor): int => $contributor->priority())
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $container
     * @return list<array<string, mixed>>
     */
    private function widgets(array $container): array
    {
        $widgets = $container['widgets'] ?? [];

        if (! is_array($widgets)) {
            return [];
        }

        $normalised = [];

        foreach ($widgets as $widget) {
            if (is_array($widget)) {
                $normalised[] = $widget;
            }
        }

        return $normalised;
    }

    /**
     * @return array<string, mixed>
     */
    private function arrayValue(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    private function integerIndex(int|string $index): ?int
    {
        return is_int($index) || is_numeric($index) ? (int) $index : null;
    }

    /**
     * @param  array<string, array<int, array<int, array<string, mixed>>>>  $assets
     * @return array<string, true>
     */
    private function reusedAssetKeys(array $assets): array
    {
        $counts = [];

        foreach ($assets as $containerAssets) {
            foreach ($containerAssets as $widgetAssets) {
                foreach ($widgetAssets as $assetState) {
                    $key = $this->itemFactory->assetKey($assetState);
                    $counts[$key] = ($counts[$key] ?? 0) + 1;
                }
            }
        }

        $reusedKeys = [];

        foreach ($counts as $key => $count) {
            if ($count > 1) {
                $reusedKeys[$key] = true;
            }
        }

        return $reusedKeys;
    }

    private function grouper(): LayoutContentInventoryGrouper
    {
        return $this->grouper ?? new LayoutContentInventoryGrouper($this->itemFactory);
    }
}
