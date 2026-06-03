<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Contracts\Pageable;
use Capell\LayoutBuilder\Data\LayoutBuilderTreeContainerData;
use Capell\LayoutBuilder\Data\LayoutBuilderTreeData;
use Capell\LayoutBuilder\Data\LayoutBuilderTreeWidgetData;
use Capell\LayoutBuilder\Models\Widget;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsObject;

final class BuildLayoutBuilderTreeAction
{
    use AsObject;

    /**
     * @param  array<string, array<string, mixed>>  $containers
     * @param  array<string, array<int, Widget>>  $containerWidgets
     * @param  array<string, array<int, array<int, array<string, mixed>>>>  $assets
     */
    public function handle(
        array $containers,
        array $containerWidgets,
        array $assets,
        ?Pageable $page,
        ?string $selectedContainerKey,
        ?int $selectedWidgetIndex,
    ): LayoutBuilderTreeData {
        $widgetCount = 0;

        $treeContainers = collect($containers)
            ->map(function (array $container, string $containerKey) use ($containerWidgets, $assets, $page, $selectedContainerKey, $selectedWidgetIndex, &$widgetCount): LayoutBuilderTreeContainerData {
                $widgets = is_array($container['widgets'] ?? null) ? $container['widgets'] : [];

                $treeWidgets = collect($widgets)
                    ->map(function (mixed $containerWidget, int $widgetIndex) use ($containerKey, $containerWidgets, $assets, $page, $selectedContainerKey, $selectedWidgetIndex): LayoutBuilderTreeWidgetData {
                        $widget = $containerWidgets[$containerKey][$widgetIndex] ?? null;

                        if (! $widget instanceof Widget) {
                            return new LayoutBuilderTreeWidgetData(
                                nodeId: $this->widgetNodeId($containerKey, $widgetIndex),
                                containerKey: $containerKey,
                                widgetIndex: $widgetIndex,
                                label: (string) __('capell-admin::message.unknown_widget', ['widget' => data_get($containerWidget, 'widget_key', __('capell-admin::generic.unknown'))]),
                                typeLabel: null,
                                icon: 'heroicon-o-question-mark-circle',
                                assetCount: count($assets[$containerKey][$widgetIndex] ?? []),
                                usesPageContent: false,
                                isSelected: $selectedContainerKey === $containerKey && $selectedWidgetIndex === $widgetIndex,
                            );
                        }

                        $previewData = ResolveAdminWidgetPreviewDataAction::run(
                            widget: $widget,
                            containerWidget: is_array($containerWidget) ? $containerWidget : [],
                            page: $page,
                            assetCount: count($assets[$containerKey][$widgetIndex] ?? []),
                            hasPageAssets: $this->hasPageAssets($assets[$containerKey][$widgetIndex] ?? []),
                        );

                        return new LayoutBuilderTreeWidgetData(
                            nodeId: $this->widgetNodeId($containerKey, $widgetIndex),
                            containerKey: $containerKey,
                            widgetIndex: $widgetIndex,
                            label: $previewData->title ?: $previewData->label,
                            typeLabel: $previewData->typeLabel,
                            icon: $previewData->icon ?: 'heroicon-o-cube',
                            assetCount: $previewData->assetCount,
                            usesPageContent: $previewData->usesPageContent,
                            isSelected: $selectedContainerKey === $containerKey && $selectedWidgetIndex === $widgetIndex,
                        );
                    })
                    ->filter()
                    ->values()
                    ->all();

                $widgetCount += count($treeWidgets);

                return new LayoutBuilderTreeContainerData(
                    nodeId: $this->containerNodeId($containerKey),
                    key: $containerKey,
                    label: (string) ($container['meta']['name'] ?? Str::of($containerKey)->headline()),
                    areaLabel: is_string($container['meta']['area'] ?? null) ? Str::of($container['meta']['area'])->headline()->toString() : null,
                    widgetCount: count($treeWidgets),
                    isSelected: $selectedContainerKey === $containerKey && $selectedWidgetIndex === null,
                    widgets: $treeWidgets,
                );
            })
            ->values()
            ->all();

        return new LayoutBuilderTreeData(
            containers: $treeContainers,
            containerCount: count($treeContainers),
            widgetCount: $widgetCount,
            signature: hash('sha256', json_encode([$containers, array_keys($containerWidgets), $assets, $selectedContainerKey, $selectedWidgetIndex], JSON_THROW_ON_ERROR)),
        );
    }

    private function containerNodeId(string $containerKey): string
    {
        return hash('xxh128', 'container:' . $containerKey);
    }

    private function widgetNodeId(string $containerKey, int $widgetIndex): string
    {
        return hash('xxh128', 'widget:' . $containerKey . ':' . $widgetIndex);
    }

    /**
     * @param  array<int, array<string, mixed>>  $assets
     */
    private function hasPageAssets(array $assets): bool
    {
        foreach ($assets as $asset) {
            if (isset($asset['pageable_type'], $asset['pageable_id'])) {
                return true;
            }
        }

        return false;
    }
}
