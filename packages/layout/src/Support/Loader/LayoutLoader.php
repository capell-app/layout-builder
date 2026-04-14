<?php

declare(strict_types=1);

namespace Capell\Layout\Support\Loader;

use Capell\Core\Actions\GetComponentClassAction;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\MediaCollectionEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Frontend\Support\ModelServing\RetrievedModelStore;
use Capell\Layout\Models\Content;
use Capell\Layout\Models\Widget;
use Capell\Layout\Models\WidgetAsset;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LayoutLoader
{
    /**
     * Preloaded widgets per [layoutId][languageId][pageIdOr0] => [containerKey][widgetKey][occurrence] => Widget
     * Used to avoid N+1 queries when resolving multiple widgets for a layout.
     */
    private array $preloaded = [];

    public function getLayout(int $id): ?Layout
    {
        $key = 'layout-' . $id;

        $fromCache = true;

        $layout = CapellCore::rememberCache($key, function () use ($id, &$fromCache): ?Layout {
            $fromCache = false;

            return Layout::query()->with('layoutWidgets')->find($id);
        });

        if ($fromCache && $layout instanceof Layout) {
            resolve(RetrievedModelStore::class)->track($layout);

            $layout->layoutWidgets->each(function (Widget $widget): void {
                resolve(RetrievedModelStore::class)->track($widget);
            });
        }

        return $layout;
    }

    /**
     * Preload all widgets and their assets for a layout for the given language and page.
     * Results are stored in-memory only for the current request lifecycle.
     */
    public function preloadLayoutWidgets(Layout $layout, Language $language, ?Pageable $page): void
    {
        $cacheKey = $this->preloadedKey($layout, $language, $page);
        if (isset($this->preloaded[$cacheKey])) {
            return;
        }

        $layout->load([
            'layoutWidgets' => fn (BuilderContract $query): BuilderContract => $query->with([
                'type',
                'media' => fn (BuilderContract $query): BuilderContract => $query->ordered(),
                'translation' => fn (BuilderContract $query): BuilderContract => $query->where('language_id', $language->id),
            ]),
        ]);

        $layout->layoutWidgets->each(function (Widget $widget): void {
            resolve(RetrievedModelStore::class)->track($widget);

            if ($widget->media->isEmpty()) {
                return;
            }

            $widget->setRelation('image', $widget->media->firstWhere('type', MediaCollectionEnum::Image->value));

            $widget->setRelation('backgroundImage', $widget->media->firstWhere('type', MediaCollectionEnum::BackgroundImage->value));
        });

        $layoutWidgets = $layout->layoutWidgets;

        // Attach language relation to the loaded translation for consistency
        $layoutWidgets->each(function (Widget $widget) use ($language): void {
            $widget->translation?->setRelation('language', $language);
        });

        // Build a lookup for widgets by id and by key
        $widgetsById = [];
        $widgetsByKey = [];
        foreach ($layoutWidgets as $widget) {
            $widgetsById[$widget->id] = $widget;
            $widgetsByKey[$widget->key] = $widget;
        }

        // Compute morph eager loads, including component-specific additions across all widgets
        $with = [
            Content::class => Content::getMorphRelations($language),
            Page::class => Page::getMorphRelations($language),
        ];

        foreach ($layoutWidgets as $widget) {
            $component = $widget->getComponent();
            $componentType = $widget->getMetaComponentType();
            $livewire = $componentType === 'livewire';

            $componentClass = GetComponentClassAction::run($component, $livewire);
            if (method_exists($componentClass, 'loadWidgetAssets')) {
                $componentClass::loadWidgetAssets($with, $language);
            }
        }

        // Fetch assets for all widgets in one go (page-specific + defaults), eager loading morph relations
        $widgetIds = array_keys($widgetsById);
        $assetQuery = WidgetAsset::query()
            ->whereIn('widget_id', $widgetIds)
            ->whereHas('asset')
            ->with([
                'media',
                'asset' => function (MorphTo $morphTo) use ($with): void {
                    $morphTo->morphWith($with);
                },
            ])
            ->ordered()
            ->alphabetical($language);

        if ($page instanceof Pageable) {
            $assetQuery->where(function (BuilderContract $query) use ($page): void {
                $query->where([
                    'pageable_type' => $page->getMorphClass(),
                    'pageable_id' => $page->getKey(),
                ])
                    ->orWhereNull(['pageable_type', 'pageable_id']);
            });
        } else {
            $assetQuery->whereNull(['pageable_type', 'pageable_id']);
        }

        $assets = $assetQuery->get();

        // Group assets for fast lookups
        $defaultAssetsByWidgetId = [];
        $pageAssetsByWidgetIdContainerOcc = [];

        $assets->each(function (WidgetAsset $asset) use (&$defaultAssetsByWidgetId, &$pageAssetsByWidgetIdContainerOcc): void {
            resolve(RetrievedModelStore::class)->track($asset);

            $widgetId = (int) $asset->widget_id;

            if ($asset->pageable_id === null && $asset->pageable_type === null) {
                $defaultAssetsByWidgetId[$widgetId] ??= [];
                $defaultAssetsByWidgetId[$widgetId][] = $asset;

                return;
            }

            $container = $asset->container;

            $occurrence = (int) $asset->occurrence;

            $pageAssetsByWidgetIdContainerOcc[$widgetId][$container][$occurrence] ??= [];
            $pageAssetsByWidgetIdContainerOcc[$widgetId][$container][$occurrence][] = $asset;
        });

        // Build the final preloaded map per container/widget/occurrence
        $result = [];
        $containers = is_array($layout->containers ?? null) ? $layout->containers : [];
        foreach ($containers as $containerKey => $container) {
            if (! isset($container['widgets'])) {
                continue;
            }

            if (! is_array($container['widgets'])) {
                continue;
            }

            foreach ($container['widgets'] as $widgetData) {
                if (! isset($widgetData['widget_key'])) {
                    continue;
                }

                $widgetKey = (string) $widgetData['widget_key'];
                $occurrence = (int) ($widgetData['occurrence'] ?? 1);

                $baseWidget = $widgetsByKey[$widgetKey] ?? null;
                if (! $baseWidget instanceof Widget) {
                    continue;
                }

                $clone = clone $baseWidget;
                $clone->translation?->setRelation('language', $language);

                $wid = $baseWidget->id;
                $assetsForPosition = $pageAssetsByWidgetIdContainerOcc[$wid][$containerKey][$occurrence] ?? [];
                if ($assetsForPosition === []) {
                    $assetsForPosition = $defaultAssetsByWidgetId[$wid] ?? [];
                }

                $clone->setRelation('assets', collect($assetsForPosition));

                $result[$containerKey][$widgetKey][$occurrence] = $clone;
            }
        }

        $this->preloaded[$cacheKey] = $result;
    }

    public function getLayoutWidget(
        Layout $layout,
        string $widgetKey,
        Language $language,
        ?Pageable $page,
        string $containerKey,
        int $occurrence,
    ): ?Widget {
        $this->preloadLayoutWidgets($layout, $language, $page);

        return $this->loadWidget($layout, $language, $page, $containerKey, $widgetKey, $occurrence);
    }

    private function preloadedKey(Layout $layout, Language $language, ?Pageable $page): string
    {
        return 'layout:' . $layout->id . ':lang:' . $language->id . ':page:' . ($page instanceof Pageable ? $page->id : 0);
    }

    private function loadWidget(
        Layout $layout,
        Language $language,
        ?Pageable $page,
        string $containerKey,
        string $widgetKey,
        int $occurrence,
    ): ?Widget {
        $cacheKey = $this->preloadedKey($layout, $language, $page);
        $map = $this->preloaded[$cacheKey] ?? null;
        if ($map === null) {
            return null;
        }

        return $map[$containerKey][$widgetKey][$occurrence] ?? null;
    }
}
