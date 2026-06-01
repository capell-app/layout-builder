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
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildLayoutContentInventoryAction
{
    use AsAction;

    /**
     * @param  array<string, array<string, mixed>>  $containers
     * @param  array<string, array<int, Widget>>  $containerBlocks
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
            $containerKey = (string) $containerOrder;

            $containerLabel = $this->containerLabel($containerKey, $container);

            foreach (($container['widgets'] ?? []) as $blockIndex => $containerBlock) {
                $block = $containerBlocks[$containerKey][$blockIndex] ?? null;

                if (! $block instanceof Widget) {
                    continue;
                }

                $blockLabel = $this->blockLabel($block, $containerBlock);
                $blockCopy = $this->blockCopy($block);

                if ($blockCopy['text'] !== null) {
                    $item = new LayoutContentItemData(
                        key: $this->blockCopyItemKey($containerKey, $blockIndex, $containerBlock, $block),
                        label: $blockLabel,
                        summary: $blockCopy['text'],
                        typeLabel: __('capell-layout-builder::generic.block_content'),
                        ownershipGroupKey: 'block-content',
                        ownershipGroupLabel: __('capell-layout-builder::generic.block_content_sources'),
                        sourceLabel: __('capell-layout-builder::generic.block_translation_source'),
                        sourceDetail: __('capell-layout-builder::generic.content_tab_title_content_fields'),
                        renderedText: $blockCopy['text'],
                        renderedTextSourceLabel: $blockCopy['source'],
                        placementLabel: $this->blockPlacementLabel($containerLabel, $blockLabel),
                        containerKey: $containerKey,
                        containerLabel: $containerLabel,
                        blockIndex: $blockIndex,
                        blockLabel: $blockLabel,
                        assetIndex: -1,
                        assetType: 'block',
                        assetId: $block->getKey(),
                        canEditAsset: false,
                        isReused: false,
                        editActionArguments: [],
                        blockEditActionArguments: [
                            'containerKey' => $containerKey,
                            'blockIndex' => $blockIndex,
                        ],
                        hasBlockCopySource: true,
                        warnings: [],
                        meta: $containerBlock['meta'] ?? [],
                    );

                    foreach ($contributors as $contributor) {
                        $item = $contributor->item($item, $context);
                    }

                    $groups = $this->appendItemToOwnershipGroup($groups, $item, $contributors, $context);
                    $itemCount++;
                }

                $blockAssets = $assets[$containerKey][$blockIndex] ?? [];

                foreach ($blockAssets as $assetIndex => $assetState) {
                    $blockAsset = $this->resolveBlockAsset($block, $assetState, $assetIndex);

                    if (! $blockAsset instanceof WidgetAsset) {
                        continue;
                    }

                    $assetKey = $this->assetKey($assetState);
                    $ownershipGroup = $this->ownershipGroup($assetState);
                    $source = $this->source($assetState);
                    $warnings = $this->warnings($assetState, isset($reusedAssetKeys[$assetKey]), $blockCopy);
                    $item = new LayoutContentItemData(
                        key: $this->itemKey($containerKey, $blockIndex, $assetIndex, $assetState),
                        label: $this->assetLabel($blockAsset),
                        summary: $this->assetSummary($blockAsset),
                        typeLabel: $this->assetTypeLabel($assetState),
                        ownershipGroupKey: $ownershipGroup['key'],
                        ownershipGroupLabel: $ownershipGroup['label'],
                        sourceLabel: $source['label'],
                        sourceDetail: $source['detail'],
                        renderedText: $blockCopy['text'],
                        renderedTextSourceLabel: $blockCopy['source'],
                        placementLabel: $this->placementLabel($containerLabel, $blockLabel, $assetIndex),
                        containerKey: $containerKey,
                        containerLabel: $containerLabel,
                        blockIndex: $blockIndex,
                        blockLabel: $blockLabel,
                        assetIndex: $assetIndex,
                        assetType: (string) ($assetState['asset_type'] ?? ''),
                        assetId: $assetState['asset_id'] ?? null,
                        canEditAsset: true,
                        isReused: isset($reusedAssetKeys[$assetKey]),
                        editActionArguments: [
                            'containerKey' => $containerKey,
                            'blockIndex' => $blockIndex,
                            'index' => $assetIndex,
                            'type' => (string) ($assetState['asset_type'] ?? ''),
                            'contentInventorySignature' => $signature,
                        ],
                        blockEditActionArguments: [
                            'containerKey' => $containerKey,
                            'blockIndex' => $blockIndex,
                        ],
                        hasBlockCopySource: $blockCopy['text'] !== null,
                        warnings: $warnings,
                        meta: $assetState['meta'] ?? [],
                    );

                    foreach ($contributors as $contributor) {
                        $item = $contributor->item($item, $context);
                    }

                    $groups = $this->appendItemToOwnershipGroup($groups, $item, $contributors, $context);
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

        $reusedKeys = [];

        foreach ($counts as $key => $count) {
            if ($count > 1) {
                $reusedKeys[$key] = true;
            }
        }

        return $reusedKeys;
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
    private function blockLabel(Widget $block, array $containerBlock): string
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
    private function resolveBlockAsset(Widget $block, array $assetState, int $assetIndex): ?WidgetAsset
    {
        $blockAsset = $block->assets->get($assetIndex);

        if ($blockAsset instanceof WidgetAsset) {
            return $blockAsset;
        }

        $assetId = $assetState['asset_id'] ?? null;
        $assetType = $assetState['asset_type'] ?? null;

        return $block->assets
            ->first(fn (WidgetAsset $candidate): bool => $candidate->asset_type === $assetType
                && $candidate->asset_id === $assetId);
    }

    private function assetLabel(WidgetAsset $blockAsset): string
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

    private function assetSummary(WidgetAsset $blockAsset): ?string
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
            return $this->translation('capell-layout-builder::generic.content');
        }

        if ($type === 'section') {
            return $this->translation('capell-layout-builder::generic.reusable_section');
        }

        return Str::of($type)->replace(['_', '-'], ' ')->headline()->toString();
    }

    /**
     * @param  array<string, mixed>  $assetState
     * @return array{key: string, label: string}
     */
    private function ownershipGroup(array $assetState): array
    {
        return match ((string) ($assetState['asset_type'] ?? '')) {
            'page' => [
                'key' => 'page-content',
                'label' => $this->translation('capell-layout-builder::generic.page_content_sources'),
            ],
            'section' => [
                'key' => 'section-assets',
                'label' => $this->translation('capell-layout-builder::generic.section_content_sources'),
            ],
            'media' => [
                'key' => 'media-assets',
                'label' => $this->translation('capell-layout-builder::generic.media_content_sources'),
            ],
            default => [
                'key' => 'other-assets',
                'label' => $this->translation('capell-layout-builder::generic.other_content_sources'),
            ],
        };
    }

    private function ownershipGroupSummary(string $groupKey): ?string
    {
        return match ($groupKey) {
            'block-content' => $this->translation('capell-layout-builder::message.block_content_sources_summary'),
            'page-content' => $this->translation('capell-layout-builder::message.page_content_sources_summary'),
            'section-assets' => $this->translation('capell-layout-builder::message.section_content_sources_summary'),
            'media-assets' => $this->translation('capell-layout-builder::message.media_content_sources_summary'),
            'other-assets' => $this->translation('capell-layout-builder::message.other_content_sources_summary'),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $assetState
     * @return array{label: string, detail: string|null}
     */
    private function source(array $assetState): array
    {
        return match ((string) ($assetState['asset_type'] ?? '')) {
            'page' => [
                'label' => $this->translation('capell-layout-builder::generic.page_translation_source'),
                'detail' => $this->translation('capell-layout-builder::generic.content_tab_title_content_fields'),
            ],
            'section' => [
                'label' => $this->translation('capell-layout-builder::generic.section_translation_source'),
                'detail' => $this->translation('capell-layout-builder::generic.content_tab_title_content_fields'),
            ],
            'media' => [
                'label' => $this->translation('capell-layout-builder::generic.media_library_source'),
                'detail' => $this->translation('capell-layout-builder::generic.media_library_fields'),
            ],
            default => [
                'label' => $this->translation('capell-layout-builder::generic.registered_asset_source'),
                'detail' => null,
            ],
        };
    }

    private function translation(string $key): string
    {
        $value = __($key);

        return is_string($value) ? $value : $key;
    }

    /**
     * @return array{source: string|null, text: string|null}
     */
    private function blockCopy(Widget $block): array
    {
        $translation = $block->getRelationValue('translation');

        if (! $translation instanceof Model) {
            return ['source' => null, 'text' => null];
        }

        $parts = [];

        foreach (['title', 'content'] as $attribute) {
            if (! $translation->hasAttribute($attribute)) {
                continue;
            }

            $value = $translation->getAttribute($attribute);
            if (! is_string($value)) {
                continue;
            }

            if (trim(strip_tags($value)) === '') {
                continue;
            }

            $parts[] = trim(strip_tags($value));
        }

        if ($parts === []) {
            return ['source' => null, 'text' => null];
        }

        return [
            'source' => __('capell-layout-builder::generic.block_translation_source'),
            'text' => Str::limit(implode(' ', $parts), 180),
        ];
    }

    /**
     * @param  array<string, mixed>  $assetState
     * @param  array{source: string|null, text: string|null}  $blockCopy
     * @return array<int, string>
     */
    private function warnings(array $assetState, bool $isReused, array $blockCopy): array
    {
        $warnings = [];

        if ((string) ($assetState['asset_type'] ?? '') === '') {
            $warnings[] = __('capell-layout-builder::message.content_source_unknown_warning');
        }

        if ($isReused) {
            $warnings[] = __('capell-layout-builder::message.content_reused_warning');
        }

        if ($blockCopy['text'] !== null) {
            $warnings[] = __('capell-layout-builder::message.block_copy_source_warning');
        }

        return $warnings;
    }

    private function placementLabel(string $containerLabel, string $blockLabel, int $assetIndex): string
    {
        return __('capell-layout-builder::generic.content_placement', [
            'container' => $containerLabel,
            'block' => $blockLabel,
            'position' => $assetIndex + 1,
        ]);
    }

    private function blockPlacementLabel(string $containerLabel, string $blockLabel): string
    {
        return __('capell-layout-builder::generic.block_content_placement', [
            'container' => $containerLabel,
            'block' => $blockLabel,
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
     * @param  array<string, mixed>  $containerBlock
     */
    private function blockCopyItemKey(string $containerKey, int $blockIndex, array $containerBlock, Widget $block): string
    {
        return implode(':', [
            $containerKey,
            (string) $blockIndex,
            (string) ($containerBlock['occurrence'] ?? 1),
            'block',
            (string) $block->getKey(),
            'copy',
        ]);
    }

    /**
     * @param  array<string, mixed>  $assetState
     */
    private function assetKey(array $assetState): string
    {
        return ($assetState['asset_type'] ?? '') . ':' . ($assetState['asset_id'] ?? '');
    }

    /**
     * @param  array<string, LayoutContentGroupData>  $groups
     * @param  array<int, LayoutContentGroupContributor>  $contributors
     * @return array<string, LayoutContentGroupData>
     */
    private function appendItemToOwnershipGroup(
        array $groups,
        LayoutContentItemData $item,
        array $contributors,
        LayoutContentInventoryContextData $context,
    ): array {
        if (! isset($groups[$item->ownershipGroupKey])) {
            $group = new LayoutContentGroupData(
                key: $item->ownershipGroupKey,
                label: $item->ownershipGroupLabel,
                summary: $this->ownershipGroupSummary($item->ownershipGroupKey),
                items: [],
                order: count($groups),
            );

            foreach ($contributors as $contributor) {
                $group = $contributor->group($group, $context);
            }

            $groups[$item->ownershipGroupKey] = $group;
        }

        $groups[$item->ownershipGroupKey]->items[] = $item;

        return $groups;
    }
}
