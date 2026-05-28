<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Contracts\Pageable;
use Capell\LayoutBuilder\Data\LayoutBuilderTreeBlockData;
use Capell\LayoutBuilder\Data\LayoutBuilderTreeContainerData;
use Capell\LayoutBuilder\Data\LayoutBuilderTreeData;
use Capell\LayoutBuilder\Models\Widget;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsObject;

final class BuildLayoutBuilderTreeAction
{
    use AsObject;

    /**
     * @param  array<string, array<string, mixed>>  $containers
     * @param  array<string, array<int, Widget>>  $containerBlocks
     * @param  array<string, array<int, array<int, array<string, mixed>>>>  $assets
     */
    public function handle(
        array $containers,
        array $containerBlocks,
        array $assets,
        ?Pageable $page,
        ?string $selectedContainerKey,
        ?int $selectedBlockIndex,
    ): LayoutBuilderTreeData {
        $blockCount = 0;

        $treeContainers = collect($containers)
            ->map(function (array $container, string $containerKey) use ($containerBlocks, $assets, $page, $selectedContainerKey, $selectedBlockIndex, &$blockCount): LayoutBuilderTreeContainerData {
                $widgets = is_array($container['widgets'] ?? null)
                    ? $container['widgets']
                    : (is_array($container['blocks'] ?? null) ? $container['blocks'] : []);

                $blocks = collect($widgets)
                    ->map(function (mixed $containerBlock, int $blockIndex) use ($containerKey, $containerBlocks, $assets, $page, $selectedContainerKey, $selectedBlockIndex): LayoutBuilderTreeBlockData {
                        $block = $containerBlocks[$containerKey][$blockIndex] ?? null;

                        if (! $block instanceof Widget) {
                            return new LayoutBuilderTreeBlockData(
                                nodeId: $this->blockNodeId($containerKey, $blockIndex),
                                containerKey: $containerKey,
                                blockIndex: $blockIndex,
                                label: (string) __('capell-admin::message.unknown_block', ['block' => data_get($containerBlock, 'widget_key', __('capell-admin::generic.unknown'))]),
                                typeLabel: null,
                                icon: 'heroicon-o-question-mark-circle',
                                assetCount: count($assets[$containerKey][$blockIndex] ?? []),
                                usesPageContent: false,
                                isSelected: $selectedContainerKey === $containerKey && $selectedBlockIndex === $blockIndex,
                            );
                        }

                        $previewData = ResolveAdminBlockPreviewDataAction::run(
                            block: $block,
                            containerBlock: is_array($containerBlock) ? $containerBlock : [],
                            page: $page,
                            assetCount: count($assets[$containerKey][$blockIndex] ?? []),
                            hasPageAssets: $this->hasPageAssets($assets[$containerKey][$blockIndex] ?? []),
                        );

                        return new LayoutBuilderTreeBlockData(
                            nodeId: $this->blockNodeId($containerKey, $blockIndex),
                            containerKey: $containerKey,
                            blockIndex: $blockIndex,
                            label: $previewData->title ?: $previewData->label,
                            typeLabel: $previewData->typeLabel,
                            icon: $previewData->icon ?: 'heroicon-o-cube',
                            assetCount: $previewData->assetCount,
                            usesPageContent: $previewData->usesPageContent,
                            isSelected: $selectedContainerKey === $containerKey && $selectedBlockIndex === $blockIndex,
                        );
                    })
                    ->filter()
                    ->values()
                    ->all();

                $blockCount += count($blocks);

                return new LayoutBuilderTreeContainerData(
                    nodeId: $this->containerNodeId($containerKey),
                    key: $containerKey,
                    label: (string) ($container['meta']['name'] ?? Str::of($containerKey)->headline()),
                    areaLabel: is_string($container['meta']['area'] ?? null) ? Str::of($container['meta']['area'])->headline()->toString() : null,
                    blockCount: count($blocks),
                    isSelected: $selectedContainerKey === $containerKey && $selectedBlockIndex === null,
                    blocks: $blocks,
                );
            })
            ->values()
            ->all();

        return new LayoutBuilderTreeData(
            containers: $treeContainers,
            containerCount: count($treeContainers),
            blockCount: $blockCount,
            signature: hash('sha256', json_encode([$containers, array_keys($containerBlocks), $assets, $selectedContainerKey, $selectedBlockIndex], JSON_THROW_ON_ERROR)),
        );
    }

    private function containerNodeId(string $containerKey): string
    {
        return hash('xxh128', 'container:' . $containerKey);
    }

    private function blockNodeId(string $containerKey, int $blockIndex): string
    {
        return hash('xxh128', 'block:' . $containerKey . ':' . $blockIndex);
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
