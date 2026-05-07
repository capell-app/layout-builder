<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Core\Adapters;

use Capell\Core\Actions\Content\ExtractTextContentAction;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Translation;
use Capell\Frontend\Facades\Frontend;
use Capell\LayoutBuilder\Support\CapellLayoutManager;
use Capell\LayoutBuilder\Support\Loader\LayoutLoader;
use Capell\ThemeStudio\Core\Contracts\ThemePageAdapter;
use Capell\ThemeStudio\Core\Contracts\ThemeRuntimeSettings;
use Capell\ThemeStudio\Core\Contracts\ThemeSection;
use Capell\ThemeStudio\Core\Data\ContentListingSectionData;
use Capell\ThemeStudio\Core\Data\CtaSectionData;
use Capell\ThemeStudio\Core\Data\FeatureSectionData;
use Capell\ThemeStudio\Core\Data\FooterData;
use Capell\ThemeStudio\Core\Data\HeroSectionData;
use Capell\ThemeStudio\Core\Data\NavigationData;
use Capell\ThemeStudio\Core\Data\ProofSectionData;
use Capell\ThemeStudio\Core\Data\ThemePageData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CapellFrontendThemePageAdapter implements ThemePageAdapter
{
    public function currentPage(): ThemePageData
    {
        $page = Frontend::page();
        $translation = $page?->translation;
        $brand = resolve(ThemeRuntimeSettings::class)->brandProfile();
        $title = $this->titleFrom($translation, $page?->name ?? 'Untitled page');
        $layoutBuilderContent = $page instanceof Pageable
            ? $this->layoutBuilderContent($page, $translation)
            : ['sections' => [], 'navigation' => null, 'footer' => null];

        return new ThemePageData(
            title: $title,
            brand: $brand,
            sections: $layoutBuilderContent['sections'] !== []
                ? $layoutBuilderContent['sections']
                : [$this->fallbackHero($title, $translation)],
            navigation: $layoutBuilderContent['navigation'],
            footer: $layoutBuilderContent['footer'],
        );
    }

    private function fallbackHero(string $title, ?Translation $translation): HeroSectionData
    {
        return HeroSectionData::from([
            'heading' => $title,
            'summary' => $this->summaryFrom($translation?->content),
        ]);
    }

    /**
     * @return array{sections: array<int, ThemeSection>, navigation: NavigationData|null, footer: FooterData|null}
     */
    private function layoutBuilderContent(Pageable $page, ?Translation $translation): array
    {
        $layout = Frontend::layout();
        $language = Frontend::language();

        if (! $layout instanceof Layout || ! $language instanceof Language) {
            return ['sections' => [], 'navigation' => null, 'footer' => null];
        }

        $sections = [];
        $navigation = null;
        $footer = null;
        $containers = is_array($layout->containers) ? $layout->containers : [];

        foreach ($containers as $containerKey => $container) {
            if (! is_array($container)) {
                continue;
            }

            $containerKeyString = $containerKey;

            if (! is_array($container['widgets'] ?? null)) {
                continue;
            }

            foreach ($container['widgets'] as $widgetData) {
                if (! is_array($widgetData)) {
                    continue;
                }

                if (! isset($widgetData['widget_key'])) {
                    continue;
                }

                $widget = $this->resolveLayoutBuilderWidget(
                    $layout,
                    (string) $widgetData['widget_key'],
                    $language,
                    $page,
                    $containerKeyString,
                    (int) ($widgetData['occurrence'] ?? 1),
                );

                if (! $widget instanceof Model) {
                    continue;
                }

                $section = $this->sectionFromWidget($widget, $containerKeyString, $translation);

                if ($section instanceof NavigationData) {
                    $navigation = $section;

                    continue;
                }

                if ($section instanceof FooterData) {
                    $footer = $section;

                    continue;
                }

                $sections[] = $section;
            }
        }

        return ['sections' => $sections, 'navigation' => $navigation, 'footer' => $footer];
    }

    private function resolveLayoutBuilderWidget(
        Layout $layout,
        string $widgetKey,
        Language $language,
        Pageable $page,
        string $containerKey,
        int $occurrence,
    ): ?Model {
        $layoutManagerClass = CapellLayoutManager::class;

        if (class_exists($layoutManagerClass)) {
            $managedWidget = $layoutManagerClass::getContainerWidget($containerKey, $widgetKey, $occurrence);

            if ($managedWidget instanceof Model) {
                return $managedWidget;
            }
        }

        $layoutLoaderClass = LayoutLoader::class;

        if (! class_exists($layoutLoaderClass)) {
            return null;
        }

        $loader = resolve($layoutLoaderClass);
        $widget = $loader->getLayoutWidget($layout, $widgetKey, $language, $page, $containerKey, $occurrence);

        return $widget instanceof Model ? $widget : null;
    }

    private function sectionFromWidget(Model $widget, string $containerKey, ?Translation $pageTranslation): ThemeSection
    {
        $component = method_exists($widget, 'getComponent') ? (string) $widget->getComponent() : '';
        $widgetKey = (string) ($widget->getAttribute('key') ?? '');
        $type = $widget->getRelationValue('type');
        $typeKey = $type instanceof Model ? (string) ($type->getAttribute('key') ?? '') : '';
        $signature = implode(' ', [$component, $widgetKey, $typeKey, $containerKey]);

        if (str_contains($signature, 'navigation')) {
            return $this->navigationFromWidget($widget);
        }

        if (str_contains($containerKey, 'footer') && str_contains($signature, 'signup')) {
            return $this->footerFromWidget($widget);
        }

        if (str_contains($signature, 'hero') || str_contains($signature, 'banner-image')) {
            return $this->heroFromWidget($widget, $pageTranslation);
        }

        if (str_contains($signature, 'cta')) {
            return $this->ctaFromWidget($widget);
        }

        if (str_contains($signature, 'feature') || str_contains($signature, 'card-grid')) {
            return $this->featuresFromWidget($widget);
        }

        if (str_contains($signature, 'testimonial') || str_contains($signature, 'proof')) {
            return $this->proofFromWidget($widget);
        }

        return $this->listingFromWidget($widget);
    }

    private function heroFromWidget(Model $widget, ?Translation $pageTranslation): HeroSectionData
    {
        $translation = $this->translationFor($widget);
        $pageHero = is_array($pageTranslation?->meta ?? null) ? ($pageTranslation->meta['hero'] ?? null) : null;

        return HeroSectionData::from([
            'eyebrow' => $this->metaString($widget, 'eyebrow'),
            'heading' => $this->titleFrom($translation, $this->titleFrom($pageTranslation, (string) $widget->getAttribute('name'))),
            'summary' => $this->summaryFrom($translation?->content ?? $pageHero ?? $pageTranslation?->content),
            'actions' => $this->actionsFromWidget($widget),
            'mediaUrl' => $this->mediaUrl($widget) ?? $this->firstAssetMediaUrl($widget),
            'mediaAlt' => $this->titleFrom($translation, (string) $widget->getAttribute('name')),
        ]);
    }

    private function ctaFromWidget(Model $widget): CtaSectionData
    {
        return CtaSectionData::from([
            'heading' => $this->titleFrom($this->translationFor($widget), (string) $widget->getAttribute('name')),
            'summary' => $this->summaryFrom($this->translationFor($widget)?->content),
            'actions' => $this->actionsFromWidget($widget),
        ]);
    }

    private function featuresFromWidget(Model $widget): FeatureSectionData
    {
        return FeatureSectionData::from([
            'heading' => $this->titleFrom($this->translationFor($widget), (string) $widget->getAttribute('name')),
            'summary' => $this->summaryFrom($this->translationFor($widget)?->content),
            'features' => $this->assetItems($widget, 'description'),
        ]);
    }

    private function proofFromWidget(Model $widget): ProofSectionData
    {
        return ProofSectionData::from([
            'heading' => $this->titleFrom($this->translationFor($widget), (string) $widget->getAttribute('name')),
            'summary' => $this->summaryFrom($this->translationFor($widget)?->content),
            'items' => $this->assetItems($widget, 'quote'),
        ]);
    }

    private function listingFromWidget(Model $widget): ContentListingSectionData
    {
        return ContentListingSectionData::from([
            'heading' => $this->titleFrom($this->translationFor($widget), (string) $widget->getAttribute('name')),
            'summary' => $this->summaryFrom($this->translationFor($widget)?->content),
            'items' => $this->assetItems($widget, 'summary'),
        ]);
    }

    private function navigationFromWidget(Model $widget): NavigationData
    {
        $site = Frontend::site();

        return NavigationData::from([
            'brandName' => $site?->title ?? $site?->name ?? 'Capell',
            'items' => $this->linkItemsFromWidget($widget),
            'ctaLabel' => $this->metaString($widget, 'primary_button_text'),
            'ctaUrl' => $this->metaString($widget, 'primary_button_url'),
        ]);
    }

    private function footerFromWidget(Model $widget): FooterData
    {
        $site = Frontend::site();

        return FooterData::from([
            'brandName' => $site?->title ?? $site?->name ?? 'Capell',
            'summary' => $this->summaryFrom($this->translationFor($widget)?->content),
            'columns' => [
                [
                    'heading' => $this->titleFrom($this->translationFor($widget), 'Links'),
                    'links' => $this->linkItemsFromWidget($widget),
                ],
            ],
        ]);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function assetItems(Model $widget, string $summaryKey): array
    {
        return $this->assetsFor($widget)
            ->map(function (Model $widgetAsset) use ($summaryKey): ?array {
                $asset = $this->assetFor($widgetAsset);

                if (! $asset instanceof Model) {
                    return null;
                }

                $translation = $this->translationFor($asset);
                $title = $this->titleFrom($translation, (string) ($asset->getAttribute('name') ?? ''));

                if ($title === '') {
                    return null;
                }

                return array_filter([
                    'title' => $title,
                    $summaryKey => $this->summaryFrom($translation?->content),
                    'summary' => $this->summaryFrom($translation?->content),
                    'url' => $this->pageUrl($asset),
                    'image' => $this->mediaUrl($asset) ?? $this->mediaUrl($widgetAsset),
                ], fn (mixed $value): bool => $value !== null && $value !== '');
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{label: string, url: string, style?: string}>
     */
    private function actionsFromWidget(Model $widget): array
    {
        $actions = [];
        $meta = $widget->getAttribute('meta');
        $configuredActions = is_array($meta) ? ($meta['actions'] ?? null) : null;

        if (is_array($configuredActions)) {
            foreach ($configuredActions as $configuredAction) {
                if (! is_array($configuredAction)) {
                    continue;
                }

                $label = $configuredAction['label'] ?? $configuredAction['text'] ?? null;
                $url = $configuredAction['url'] ?? $configuredAction['href'] ?? null;

                if (is_string($label) && $label !== '' && is_string($url) && $url !== '') {
                    $actions[] = ['label' => $label, 'url' => $url, 'style' => (string) ($configuredAction['style'] ?? 'primary')];
                }
            }
        }

        foreach (['primary' => 'primary', 'secondary' => 'secondary'] as $prefix => $style) {
            $label = $this->metaString($widget, $prefix . '_button_text');
            $url = $this->metaString($widget, $prefix . '_button_url');

            if ($label !== null && $url !== null) {
                $actions[] = ['label' => $label, 'url' => $url, 'style' => $style];
            }
        }

        return $actions;
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    private function linkItemsFromWidget(Model $widget): array
    {
        return collect($this->assetItems($widget, 'summary'))
            ->map(function (array $item): ?array {
                $label = $item['title'] ?? null;
                $url = $item['url'] ?? null;

                if (! is_string($label) || $label === '' || ! is_string($url) || $url === '') {
                    return null;
                }

                return ['label' => $label, 'url' => $url];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function titleFrom(?Translation $translation, string $fallback): string
    {
        $title = $translation?->title;

        return is_string($title) && $title !== '' ? $title : $fallback;
    }

    private function summaryFrom(mixed $content): ?string
    {
        $summary = ExtractTextContentAction::run($content, 40);

        return $summary === '' ? null : $summary;
    }

    private function metaString(Model $model, string $key): ?string
    {
        $value = method_exists($model, 'getMeta')
            ? $model->getMeta($key)
            : data_get($model->getAttribute('meta'), $key);

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function mediaUrl(Model $model): ?string
    {
        $image = $this->loadedRelationValue($model, 'image')
            ?? $this->loadedRelationValue($model, 'backgroundImage')
            ?? $this->loadedRelationValue($model, 'media')?->first();

        return is_object($image) && method_exists($image, 'getUrl') ? (string) $image->getUrl() : null;
    }

    private function firstAssetMediaUrl(Model $widget): ?string
    {
        return $this->assetsFor($widget)
            ->map(fn (Model $widgetAsset): ?string => $this->mediaUrl($widgetAsset) ?? ($this->assetFor($widgetAsset) instanceof Model ? $this->mediaUrl($this->assetFor($widgetAsset)) : null))
            ->filter()
            ->first();
    }

    private function translationFor(Model $model): ?Translation
    {
        $translation = $model->getRelationValue('translation');

        return $translation instanceof Translation ? $translation : null;
    }

    private function assetFor(Model $model): ?Model
    {
        $asset = $model->getRelationValue('asset');

        return $asset instanceof Model ? $asset : null;
    }

    private function loadedRelationValue(Model $model, string $relation): mixed
    {
        return $model->relationLoaded($relation)
            ? $model->getRelationValue($relation)
            : null;
    }

    private function pageUrl(Model $model): ?string
    {
        $pageUrl = $model->getRelationValue('pageUrl');

        if ($pageUrl instanceof Model) {
            $url = $pageUrl->getAttribute('url');

            return is_string($url) && $url !== '' ? $url : null;
        }

        return null;
    }

    /**
     * @return Collection<int, Model>
     */
    private function assetsFor(Model $widget): Collection
    {
        $assets = $widget->getRelationValue('assets');

        if ($assets instanceof Collection) {
            return $assets->filter(fn (mixed $asset): bool => $asset instanceof Model)->values();
        }

        return collect();
    }
}
