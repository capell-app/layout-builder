<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Contracts\LayoutContentGroupContributor;
use Capell\LayoutBuilder\Data\LayoutContentGroupData;
use Capell\LayoutBuilder\Data\LayoutContentInventoryContextData;
use Capell\LayoutBuilder\Data\LayoutContentInventoryData;
use Capell\LayoutBuilder\Data\LayoutContentItemData;
use Capell\LayoutBuilder\Models\Block;
use Capell\LayoutBuilder\Models\BlockAsset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildLayoutContentInventoryAction
{
    use AsAction;

    /**
     * @param  array<string, array<string, mixed>>  $containers
     * @param  array<string, array<int, Block>>  $containerBlocks
     * @param  array<string, array<int, array<int, array<string, mixed>>>>  $assets
     * @param  iterable<int, LayoutContentGroupContributor>|null  $contributors
     */
    public function handle(
        Layout $layout,
        ?Pageable $page,
        array $containers,
        array $containerBlocks,
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
            $containerKey = $containerOrder;

            if (is_string($containerOrder)) {
                $containerKey = $containerOrder;
            }

            $containerLabel = $this->containerLabel($containerKey, $container);
            $group = new LayoutContentGroupData(
                key: $containerKey,
                label: $containerLabel,
                summary: null,
                items: [],
                order: is_int($containerOrder) ? $containerOrder : count($groups),
            );

            foreach ($contributors as $contributor) {
                $group = $contributor->group($group, $context);
            }

            foreach (($container['blocks'] ?? []) as $blockIndex => $containerBlock) {
                $block = $containerBlocks[$containerKey][$blockIndex] ?? null;

                if (! $block instanceof Block) {
                    continue;
                }

                $blockLabel = $this->blockLabel($block, $containerBlock);
                $blockAssets = $assets[$containerKey][$blockIndex] ?? [];

                foreach ($blockAssets as $assetIndex => $assetState) {
                    $blockAsset = $this->resolveBlockAsset($block, $assetState, $assetIndex);

                    if (! $blockAsset instanceof BlockAsset) {
                        continue;
                    }

                    $assetKey = $this->assetKey($assetState);
                    $item = new LayoutContentItemData(
                        key: $this->itemKey($containerKey, $blockIndex, $assetIndex, $assetState),
                        label: $this->assetLabel($blockAsset),
                        summary: $this->assetSummary($blockAsset),
                        typeLabel: $this->assetTypeLabel($assetState),
                        placementLabel: $this->placementLabel($containerLabel, $blockLabel, $assetIndex),
                        containerKey: $containerKey,
                        containerLabel: $containerLabel,
                        blockIndex: $blockIndex,
                        blockLabel: $blockLabel,
                        assetIndex: $assetIndex,
                        assetType: (string) ($assetState['asset_type'] ?? ''),
                        assetId: $assetState['asset_id'] ?? null,
                        isReused: isset($reusedAssetKeys[$assetKey]),
                        editActionArguments: [
                            'containerKey' => $containerKey,
                            'blockIndex' => $blockIndex,
                            'index' => $assetIndex,
                            'type' => (string) ($assetState['asset_type'] ?? ''),
                            'contentInventorySignature' => $signature,
                        ],
                        meta: $assetState['meta'] ?? [],
                    );

                    foreach ($contributors as $contributor) {
                        $item = $contributor->item($item, $context);
                    }

                    $group->items[] = $item;
                    $itemCount++;
                }
            }

            if ($group->items !== []) {
                $groups[] = $group;
            }
        }

        return new LayoutContentInventoryData(
            groups: $groups,
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
     * @param  array<string, array<int, array<int, array<string, mixed>>>>  $assets
     * @return array<string, true>
     */
    private function reusedAssetKeys(array $assets): array
    {
        $counts = [];

        foreach ($assets as $containerAssets) {
            foreach ($containerAssets as $blockAssets) {
                foreach ($blockAssets as $assetState) {
                    $key = $this->assetKey($assetState);
                    $counts[$key] = ($counts[$key] ?? 0) + 1;
                }
            }
        }

        return collect($counts)
            ->filter(fn (int $count): bool => $count > 1)
            ->map(fn (): bool => true)
            ->all();
    }

    /**
     * @param  array<string, mixed>  $container
     */
    private function containerLabel(string $containerKey, array $container): string
    {
        $configuredName = Arr::get($container, 'meta.name');

        if (is_string($configuredName) && trim($configuredName) !== '') {
            return trim($configuredName);
        }

        return match (Str::of($containerKey)->lower()->replace(['_', '-'], ' ')->toString()) {
            'header', 'top' => __('capell-layout-builder::generic.header_area'),
            'hero' => __('capell-layout-builder::generic.hero_area'),
            'main', 'content', 'body' => __('capell-layout-builder::generic.main_content_area'),
            'sidebar', 'aside' => __('capell-layout-builder::generic.sidebar_area'),
            'footer', 'bottom' => __('capell-layout-builder::generic.footer_area'),
            default => __('capell-layout-builder::generic.untitled_content_area'),
        };
    }

    /**
     * @param  array<string, mixed>  $containerBlock
     */
    private function blockLabel(Block $block, array $containerBlock): string
    {
        $configuredName = Arr::get($containerBlock, 'meta.name');

        if (is_string($configuredName) && trim($configuredName) !== '') {
            return trim($configuredName);
        }

        return $block->name !== '' ? $block->name : __('capell-layout-builder::generic.untitled_content_block');
    }

    /**
     * @param  array<string, mixed>  $assetState
     */
    private function resolveBlockAsset(Block $block, array $assetState, int $assetIndex): ?BlockAsset
    {
        $blockAsset = $block->assets->get($assetIndex);

        if ($blockAsset instanceof BlockAsset) {
            return $blockAsset;
        }

        $assetId = $assetState['asset_id'] ?? null;
        $assetType = $assetState['asset_type'] ?? null;

        return $block->assets
            ->first(fn (BlockAsset $candidate): bool => $candidate->asset_type === $assetType
                && $candidate->asset_id === $assetId);
    }

    private function assetLabel(BlockAsset $blockAsset): string
    {
        $asset = $blockAsset->asset;

        if ($asset instanceof Model) {
            foreach (['name', 'title'] as $attribute) {
                if ($asset->hasAttribute($attribute) && is_string($asset->getAttribute($attribute)) && $asset->getAttribute($attribute) !== '') {
                    return $asset->getAttribute($attribute);
                }
            }

            $translation = $asset->getRelationValue('translation');

            if ($translation instanceof Model && $translation->hasAttribute('title') && is_string($translation->getAttribute('title')) && $translation->getAttribute('title') !== '') {
                return $translation->getAttribute('title');
            }
        }

        return __('capell-layout-builder::generic.untitled_content_block');
    }

    private function assetSummary(BlockAsset $blockAsset): ?string
    {
        $asset = $blockAsset->asset;

        if (! $asset instanceof Model) {
            return null;
        }

        $translation = $asset->getRelationValue('translation');

        if ($translation instanceof Model && $translation->hasAttribute('title')) {
            $title = $translation->getAttribute('title');

            if (is_string($title) && $title !== '' && $title !== $this->assetLabel($blockAsset)) {
                return $title;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $assetState
     */
    private function assetTypeLabel(array $assetState): string
    {
        $type = (string) ($assetState['asset_type'] ?? '');

        if ($type === '') {
            return __('capell-layout-builder::generic.content');
        }

        return Str::of($type)->replace(['_', '-'], ' ')->headline()->toString();
    }

    private function placementLabel(string $containerLabel, string $blockLabel, int $assetIndex): string
    {
        return __('capell-layout-builder::generic.content_placement', [
            'container' => $containerLabel,
            'block' => $blockLabel,
            'position' => $assetIndex + 1,
        ]);
    }

    /**
     * @param  array<string, mixed>  $assetState
     */
    private function itemKey(string $containerKey, int $blockIndex, int $assetIndex, array $assetState): string
    {
        return implode(':', [
            $containerKey,
            (string) $blockIndex,
            (string) ($assetState['occurrence'] ?? 1),
            (string) ($assetState['asset_type'] ?? ''),
            (string) ($assetState['asset_id'] ?? ''),
            (string) $assetIndex,
        ]);
    }

    /**
     * @param  array<string, mixed>  $assetState
     */
    private function assetKey(array $assetState): string
    {
        return ($assetState['asset_type'] ?? '') . ':' . ($assetState['asset_id'] ?? '');
    }
}
