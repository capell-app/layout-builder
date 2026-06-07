<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\ContentInventory;

use Capell\LayoutBuilder\Data\LayoutContentItemData;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

final class LayoutContentInventoryItemFactory
{
    /**
     * @param  array<string, mixed>  $container
     */
    public function containerLabel(string $containerKey, array $container): string
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
     * @param  array<string, mixed>  $containerWidget
     */
    public function widgetLabel(Widget $widget, array $containerWidget): string
    {
        $configuredName = Arr::get($containerWidget, 'meta.name');

        if (is_string($configuredName) && trim($configuredName) !== '') {
            return trim($configuredName);
        }

        return $widget->name !== '' ? $widget->name : __('capell-layout-builder::generic.untitled_content_widget');
    }

    /**
     * @param  array<string, mixed>  $containerWidget
     * @param  array{source: string|null, text: string|null}  $widgetCopy
     * @param  array<string, mixed>  $meta
     */
    public function widgetCopyItem(
        string $containerKey,
        int $widgetIndex,
        array $containerWidget,
        Widget $widget,
        string $containerLabel,
        string $widgetLabel,
        array $widgetCopy,
        array $meta,
    ): LayoutContentItemData {
        return new LayoutContentItemData(
            key: $this->widgetCopyItemKey($containerKey, $widgetIndex, $containerWidget, $widget),
            label: $widgetLabel,
            summary: $widgetCopy['text'],
            typeLabel: __('capell-layout-builder::generic.widget_content'),
            ownershipGroupKey: 'widget-content',
            ownershipGroupLabel: __('capell-layout-builder::generic.widget_content_sources'),
            sourceLabel: __('capell-layout-builder::generic.widget_translation_source'),
            sourceDetail: __('capell-layout-builder::generic.content_tab_title_content_fields'),
            renderedText: $widgetCopy['text'],
            renderedTextSourceLabel: $widgetCopy['source'],
            placementLabel: $this->widgetPlacementLabel($containerLabel, $widgetLabel),
            containerKey: $containerKey,
            containerLabel: $containerLabel,
            widgetIndex: $widgetIndex,
            widgetLabel: $widgetLabel,
            assetIndex: -1,
            assetType: 'widget',
            assetId: $widget->getKey(),
            canEditAsset: false,
            isReused: false,
            editActionArguments: [],
            widgetEditActionArguments: [
                'containerKey' => $containerKey,
                'widgetIndex' => $widgetIndex,
            ],
            hasWidgetCopySource: true,
            warnings: [],
            meta: $meta,
        );
    }

    /**
     * @param  array<string, mixed>  $assetState
     * @param  array{source: string|null, text: string|null}  $widgetCopy
     */
    public function assetItem(
        string $containerKey,
        int $widgetIndex,
        int $assetIndex,
        array $assetState,
        WidgetAsset $widgetAsset,
        string $signature,
        string $containerLabel,
        string $widgetLabel,
        bool $isReused,
        array $widgetCopy,
    ): LayoutContentItemData {
        $ownershipGroup = $this->ownershipGroup($assetState);
        $source = $this->source($assetState);

        return new LayoutContentItemData(
            key: $this->itemKey($containerKey, $widgetIndex, $assetIndex, $assetState),
            label: $this->assetLabel($widgetAsset),
            summary: $this->assetSummary($widgetAsset),
            typeLabel: $this->assetTypeLabel($assetState),
            ownershipGroupKey: $ownershipGroup['key'],
            ownershipGroupLabel: $ownershipGroup['label'],
            sourceLabel: $source['label'],
            sourceDetail: $source['detail'],
            renderedText: $widgetCopy['text'],
            renderedTextSourceLabel: $widgetCopy['source'],
            placementLabel: $this->placementLabel($containerLabel, $widgetLabel, $assetIndex),
            containerKey: $containerKey,
            containerLabel: $containerLabel,
            widgetIndex: $widgetIndex,
            widgetLabel: $widgetLabel,
            assetIndex: $assetIndex,
            assetType: (string) ($assetState['asset_type'] ?? ''),
            assetId: $assetState['asset_id'] ?? null,
            canEditAsset: true,
            isReused: $isReused,
            editActionArguments: [
                'containerKey' => $containerKey,
                'widgetIndex' => $widgetIndex,
                'index' => $assetIndex,
                'type' => (string) ($assetState['asset_type'] ?? ''),
                'contentInventorySignature' => $signature,
            ],
            widgetEditActionArguments: [
                'containerKey' => $containerKey,
                'widgetIndex' => $widgetIndex,
            ],
            hasWidgetCopySource: $widgetCopy['text'] !== null,
            warnings: $this->warnings($assetState, $isReused, $widgetCopy),
            meta: $assetState['meta'] ?? [],
        );
    }

    /**
     * @param  array<string, mixed>  $assetState
     */
    public function resolveWidgetAsset(Widget $widget, array $assetState, int $assetIndex): ?WidgetAsset
    {
        $widgetAsset = $widget->assets->get($assetIndex);

        if ($widgetAsset instanceof WidgetAsset) {
            return $widgetAsset;
        }

        $assetId = $assetState['asset_id'] ?? null;
        $assetType = $assetState['asset_type'] ?? null;

        return $widget->assets
            ->first(fn (WidgetAsset $candidate): bool => $candidate->asset_type === $assetType
                && $candidate->asset_id === $assetId);
    }

    /**
     * @return array{source: string|null, text: string|null}
     */
    public function widgetCopy(Widget $widget): array
    {
        $translation = $widget->getRelationValue('translation');

        if (! $translation instanceof Model) {
            return ['source' => null, 'text' => null];
        }

        $parts = [];

        foreach (['title', 'content'] as $attribute) {
            if (! $translation->hasAttribute($attribute)) {
                continue;
            }

            $value = $translation->getAttribute($attribute);
            if (! is_string($value) || trim(strip_tags($value)) === '') {
                continue;
            }

            $parts[] = trim(strip_tags($value));
        }

        if ($parts === []) {
            return ['source' => null, 'text' => null];
        }

        return [
            'source' => __('capell-layout-builder::generic.widget_translation_source'),
            'text' => Str::limit(implode(' ', $parts), 180),
        ];
    }

    /**
     * @param  array<string, mixed>  $assetState
     */
    public function assetKey(array $assetState): string
    {
        return ($assetState['asset_type'] ?? '') . ':' . ($assetState['asset_id'] ?? '');
    }

    public function ownershipGroupSummary(string $groupKey): ?string
    {
        return match ($groupKey) {
            'widget-content' => $this->translation('capell-layout-builder::message.widget_content_sources_summary'),
            'page-content' => $this->translation('capell-layout-builder::message.page_content_sources_summary'),
            'section-assets' => $this->translation('capell-layout-builder::message.section_content_sources_summary'),
            'media-assets' => $this->translation('capell-layout-builder::message.media_content_sources_summary'),
            'other-assets' => $this->translation('capell-layout-builder::message.other_content_sources_summary'),
            default => null,
        };
    }

    private function assetLabel(WidgetAsset $widgetAsset): string
    {
        $asset = $widgetAsset->asset;

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

        return __('capell-layout-builder::generic.untitled_content_widget');
    }

    private function assetSummary(WidgetAsset $widgetAsset): ?string
    {
        $asset = $widgetAsset->asset;

        if (! $asset instanceof Model) {
            return null;
        }

        $translation = $asset->getRelationValue('translation');

        if ($translation instanceof Model && $translation->hasAttribute('title')) {
            $title = $translation->getAttribute('title');

            if (is_string($title) && $title !== '' && $title !== $this->assetLabel($widgetAsset)) {
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
     * @param  array<string, mixed>  $assetState
     * @param  array{source: string|null, text: string|null}  $widgetCopy
     * @return array<int, string>
     */
    private function warnings(array $assetState, bool $isReused, array $widgetCopy): array
    {
        $warnings = [];

        if ((string) ($assetState['asset_type'] ?? '') === '') {
            $warnings[] = __('capell-layout-builder::message.content_source_unknown_warning');
        }

        if ($isReused) {
            $warnings[] = __('capell-layout-builder::message.content_reused_warning');
        }

        if ($widgetCopy['text'] !== null) {
            $warnings[] = __('capell-layout-builder::message.widget_copy_source_warning');
        }

        return $warnings;
    }

    private function placementLabel(string $containerLabel, string $widgetLabel, int $assetIndex): string
    {
        return __('capell-layout-builder::generic.content_placement', [
            'container' => $containerLabel,
            'widget' => $widgetLabel,
            'position' => $assetIndex + 1,
        ]);
    }

    private function widgetPlacementLabel(string $containerLabel, string $widgetLabel): string
    {
        return __('capell-layout-builder::generic.widget_content_placement', [
            'container' => $containerLabel,
            'widget' => $widgetLabel,
        ]);
    }

    /**
     * @param  array<string, mixed>  $assetState
     */
    private function itemKey(string $containerKey, int $widgetIndex, int $assetIndex, array $assetState): string
    {
        return implode(':', [
            $containerKey,
            (string) $widgetIndex,
            (string) ($assetState['occurrence'] ?? 1),
            (string) ($assetState['asset_type'] ?? ''),
            (string) ($assetState['asset_id'] ?? ''),
            (string) $assetIndex,
        ]);
    }

    /**
     * @param  array<string, mixed>  $containerWidget
     */
    private function widgetCopyItemKey(string $containerKey, int $widgetIndex, array $containerWidget, Widget $widget): string
    {
        return implode(':', [
            $containerKey,
            (string) $widgetIndex,
            (string) ($containerWidget['occurrence'] ?? 1),
            'widget',
            (string) $widget->getKey(),
            'copy',
        ]);
    }
}
