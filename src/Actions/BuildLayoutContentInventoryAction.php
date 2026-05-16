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
use Capell\LayoutBuilder\Models\Element;
use Capell\LayoutBuilder\Models\ElementAsset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildLayoutContentInventoryAction
{
    use AsAction;

    /**
     * @param  array<string, array<string, mixed>>  $containers
     * @param  array<string, array<int, Element>>  $containerElements
     * @param  array<string, array<int, array<int, array<string, mixed>>>>  $assets
     * @param  iterable<int, LayoutContentGroupContributor>|null  $contributors
     */
    public function handle(
        Layout $layout,
        ?Pageable $page,
        array $containers,
        array $containerElements,
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

            foreach (($container['elements'] ?? []) as $elementIndex => $containerElement) {
                $element = $containerElements[$containerKey][$elementIndex] ?? null;

                if (! $element instanceof Element) {
                    continue;
                }

                $elementLabel = $this->elementLabel($element, $containerElement);
                $elementAssets = $assets[$containerKey][$elementIndex] ?? [];

                foreach ($elementAssets as $assetIndex => $assetState) {
                    $elementAsset = $this->resolveElementAsset($element, $assetState, $assetIndex);

                    if (! $elementAsset instanceof ElementAsset) {
                        continue;
                    }

                    $assetKey = $this->assetKey($assetState);
                    $item = new LayoutContentItemData(
                        key: $this->itemKey($containerKey, $elementIndex, $assetIndex, $assetState),
                        label: $this->assetLabel($elementAsset),
                        summary: $this->assetSummary($elementAsset),
                        typeLabel: $this->assetTypeLabel($assetState),
                        placementLabel: $this->placementLabel($containerLabel, $elementLabel, $assetIndex),
                        containerKey: $containerKey,
                        containerLabel: $containerLabel,
                        elementIndex: $elementIndex,
                        elementLabel: $elementLabel,
                        assetIndex: $assetIndex,
                        assetType: (string) ($assetState['asset_type'] ?? ''),
                        assetId: $assetState['asset_id'] ?? null,
                        isReused: isset($reusedAssetKeys[$assetKey]),
                        editActionArguments: [
                            'containerKey' => $containerKey,
                            'elementIndex' => $elementIndex,
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
            foreach ($containerAssets as $elementAssets) {
                foreach ($elementAssets as $assetState) {
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
     * @param  array<string, mixed>  $containerElement
     */
    private function elementLabel(Element $element, array $containerElement): string
    {
        $configuredName = Arr::get($containerElement, 'meta.name');

        if (is_string($configuredName) && trim($configuredName) !== '') {
            return trim($configuredName);
        }

        return $element->name !== '' ? $element->name : __('capell-layout-builder::generic.untitled_content_block');
    }

    /**
     * @param  array<string, mixed>  $assetState
     */
    private function resolveElementAsset(Element $element, array $assetState, int $assetIndex): ?ElementAsset
    {
        $elementAsset = $element->assets->get($assetIndex);

        if ($elementAsset instanceof ElementAsset) {
            return $elementAsset;
        }

        $assetId = $assetState['asset_id'] ?? null;
        $assetType = $assetState['asset_type'] ?? null;

        return $element->assets
            ->first(fn (ElementAsset $candidate): bool => $candidate->asset_type === $assetType
                && $candidate->asset_id === $assetId);
    }

    private function assetLabel(ElementAsset $elementAsset): string
    {
        $asset = $elementAsset->asset;

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

    private function assetSummary(ElementAsset $elementAsset): ?string
    {
        $asset = $elementAsset->asset;

        if (! $asset instanceof Model) {
            return null;
        }

        $translation = $asset->getRelationValue('translation');

        if ($translation instanceof Model && $translation->hasAttribute('title')) {
            $title = $translation->getAttribute('title');

            if (is_string($title) && $title !== '' && $title !== $this->assetLabel($elementAsset)) {
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

    private function placementLabel(string $containerLabel, string $elementLabel, int $assetIndex): string
    {
        return __('capell-layout-builder::generic.content_placement', [
            'container' => $containerLabel,
            'element' => $elementLabel,
            'position' => $assetIndex + 1,
        ]);
    }

    /**
     * @param  array<string, mixed>  $assetState
     */
    private function itemKey(string $containerKey, int $elementIndex, int $assetIndex, array $assetState): string
    {
        return implode(':', [
            $containerKey,
            (string) $elementIndex,
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
