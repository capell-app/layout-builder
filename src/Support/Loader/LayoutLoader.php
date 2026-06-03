<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\Loader;

use Capell\Core\Actions\GetComponentClassAction;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\MediaCollectionEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\LayoutBuilder\Support\LayoutWidgetData;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Throwable;

class LayoutLoader
{
    private const string RETRIEVED_MODEL_STORE_SERVICE = 'capell.frontend.retrieved-model-store';

    /**
     * Preloaded widgets per [layoutId][languageId][pageIdOr0] => [containerKey][widgetKey][occurrence] => Widget
     * Used to avoid N+1 queries when resolving multiple widgets for a layout.
     *
     * @var array<array-key, mixed>
     */
    private array $preloaded = [];

    public function getLayout(int $id): ?Layout
    {
        $key = 'layout-' . $id;

        $fromCache = true;

        $layout = CapellCore::rememberCache($key, function () use ($id, &$fromCache): ?Layout {
            $fromCache = false;

            return Layout::query()->find($id);
        });

        if ($fromCache && $layout instanceof Layout) {
            $this->trackRetrievedModel($layout);

            $this->layoutWidgets($layout)->each(function (Widget $widget): void {
                $this->trackRetrievedModel($widget);
            });
        }

        return $layout;
    }

    /**
     * @param  array<int, string>|null  $containerKeys
     */
    public function preloadLayoutWidgets(Layout $layout, Language $language, ?Pageable $page, ?array $containerKeys = null): void
    {
        $cacheKey = $this->preloadedKey($layout, $language, $page, $containerKeys);
        if (isset($this->preloaded[$cacheKey])) {
            return;
        }

        $containers = $this->selectedLayoutContainers($layout, $containerKeys);
        $selectedWidgetKeys = $this->selectedWidgetKeys($containers);
        $selectedContainerOccurrences = $this->selectedContainerOccurrences($containers);

        if (! $layout->relationLoaded('media')) {
            $layout->load(['media' => fn (BuilderContract $query): BuilderContract => $query->ordered()]);
        }

        if ($selectedWidgetKeys === [] || ! Schema::hasTable('widgets')) {
            $layout->setRelation('layoutWidgets', collect());
            $this->preloaded[$cacheKey] = [];

            return;
        }

        $layout->setRelation('layoutWidgets', Widget::query()
            ->whereIn('key', $selectedWidgetKeys)
            ->whereHas('type', fn (BuilderContract $query): BuilderContract => $query->enabled()->accessible())
            ->with([
                'blueprint',
                'type',
                'media' => fn (BuilderContract $query): BuilderContract => $query->ordered(),
                'translation' => fn (BuilderContract $query): BuilderContract => $query->where('language_id', $language->id),
            ])
            ->enabled()
            ->publishedDate()
            ->get());

        $this->layoutWidgets($layout)->each(function (Widget $widget): void {
            $this->trackRetrievedModel($widget);

            $widget->setRelation('image', $widget->media->firstWhere('type', MediaCollectionEnum::Image->value));

            $widget->setRelation('backgroundImage', $widget->media->firstWhere('type', MediaCollectionEnum::BackgroundImage->value));
        });

        $layoutWidgets = $this->layoutWidgets($layout)->whereIn('key', $selectedWidgetKeys)->values();

        // Attach language relation to the loaded translation for consistency
        $layoutWidgets->each(function (Widget $widget) use ($language): void {
            $widget->translation?->setRelation('language', $language);
        });

        $this->hydrateLayoutWidgets($layoutWidgets);

        // Build a lookup for widgets by id and by key
        $widgetsById = [];
        $widgetsByKey = [];
        foreach ($layoutWidgets as $widget) {
            $widgetsById[$widget->id] = $widget;
            $widgetsByKey[$widget->key] = $widget;
        }

        // Compute morph eager loads, including component-specific additions across all widgets
        $with = [
            Page::class => Page::getMorphRelations($language),
        ];

        CapellCore::getAssets()->each(function (mixed $asset) use (&$with, $language): void {
            $model = $asset->model;

            if ($model === Page::class || ! method_exists($model, 'getMorphRelations')) {
                return;
            }

            $with[$model] = $model::getMorphRelations($language);
        });

        foreach ($layoutWidgets as $widget) {
            $component = $widget->getComponent();
            $componentType = $widget->getMetaComponentType();
            $livewire = $componentType === 'livewire';

            if (! is_string($component)) {
                continue;
            }

            try {
                $componentClass = GetComponentClassAction::run($component, $livewire);
            } catch (Throwable) {
                continue;
            }

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
                'asset' => function (Relation $morphTo) use ($with): void {
                    if ($morphTo instanceof MorphTo) {
                        $morphTo->morphWith($with);
                    }
                },
            ])
            ->ordered()
            ->alphabetical($language);

        if ($page instanceof Pageable) {
            $assetQuery->where(function (BuilderContract $query) use ($page, $selectedContainerOccurrences): void {
                $query->where([
                    'pageable_type' => $page->getMorphClass(),
                    'pageable_id' => $page->getKey(),
                ])
                    ->when($selectedContainerOccurrences !== [], function (BuilderContract $query) use ($selectedContainerOccurrences): BuilderContract {
                        return $query->where(function (BuilderContract $query) use ($selectedContainerOccurrences): void {
                            foreach ($selectedContainerOccurrences as $position) {
                                $query->orWhere(function (BuilderContract $query) use ($position): void {
                                    $query
                                        ->where('container', $position['container'])
                                        ->where('occurrence', $position['occurrence']);
                                });
                            }
                        });
                    })
                    ->orWhereNull(['pageable_type', 'pageable_id']);
            });
        } else {
            $assetQuery->whereNull(['pageable_type', 'pageable_id']);
        }

        $assets = $assetQuery->get();

        // Group assets for fast lookups
        $defaultAssetsByWidgetIdOccurrence = [];
        $pageAssetsByWidgetIdContainerOcc = [];

        $assets->each(function (WidgetAsset $asset) use (&$defaultAssetsByWidgetIdOccurrence, &$pageAssetsByWidgetIdContainerOcc): void {
            $this->trackRetrievedModel($asset);

            $widgetId = (int) $asset->widget_id;
            $occurrence = $asset->occurrence ?? 1;

            if ($asset->pageable_id === null && $asset->pageable_type === null) {
                $defaultAssetsByWidgetIdOccurrence[$widgetId][$occurrence] ??= [];
                $defaultAssetsByWidgetIdOccurrence[$widgetId][$occurrence][] = $asset;

                return;
            }

            $container = $asset->container;

            $pageAssetsByWidgetIdContainerOcc[$widgetId][$container][$occurrence] ??= [];
            $pageAssetsByWidgetIdContainerOcc[$widgetId][$container][$occurrence][] = $asset;
        });

        // Build the final preloaded map per container/widget/occurrence
        $result = [];
        foreach ($containers as $containerKey => $container) {
            foreach (LayoutWidgetData::fromContainer($container) as $widgetData) {
                $widgetKey = LayoutWidgetData::key($widgetData);
                if ($widgetKey === null) {
                    continue;
                }

                $occurrence = LayoutWidgetData::occurrence($widgetData);

                $baseWidget = $widgetsByKey[$widgetKey] ?? null;
                if (! $baseWidget instanceof Widget) {
                    continue;
                }

                $clone = clone $baseWidget;
                $clone->translation?->setRelation('language', $language);

                $wid = $baseWidget->id;
                $assetsForPosition = $pageAssetsByWidgetIdContainerOcc[$wid][$containerKey][$occurrence] ?? [];
                if ($assetsForPosition === []) {
                    $assetsForPosition = $defaultAssetsByWidgetIdOccurrence[$wid][$occurrence] ?? [];
                }

                $clone->setRelation('assets', collect($assetsForPosition));

                $result[$containerKey][$widgetKey][$occurrence] = $clone;
            }
        }

        $this->preloaded[$cacheKey] = $result;
    }

    /**
     * @param  array<array-key, mixed>  $containerKeys
     */
    public function getLayoutWidget(
        Layout $layout,
        string $widgetKey,
        Language $language,
        ?Pageable $page,
        string $containerKey,
        int $occurrence,
        ?array $containerKeys = null,
    ): ?Widget {
        $this->preloadLayoutWidgets($layout, $language, $page, $containerKeys);

        return $this->loadWidget($layout, $language, $page, $containerKey, $widgetKey, $occurrence, $containerKeys);
    }

    /**
     * @param  Collection<int, Widget>  $widgets
     */
    private function hydrateLayoutWidgets(Collection $widgets): void
    {
        $widgets
            ->groupBy(fn (Widget $widget): string => $widget->getComponent() . ':' . $widget->getMetaComponentType())
            ->each(function (Collection $componentWidgets): void {
                /** @var Widget|null $firstWidget */
                $firstWidget = $componentWidgets->first();

                if (! $firstWidget instanceof Widget) {
                    return;
                }

                $component = $firstWidget->getComponent();
                $livewire = $firstWidget->getMetaComponentType() === 'livewire';

                if (! is_string($component)) {
                    return;
                }

                try {
                    $componentClass = GetComponentClassAction::run($component, $livewire);
                } catch (Throwable) {
                    return;
                }

                if (! method_exists($componentClass, 'hydrateWidgets')) {
                    return;
                }

                $componentClass::hydrateWidgets($componentWidgets);
            });
    }

    /**
     * @return Collection<int, Widget>
     */
    private function layoutWidgets(Layout $layout): Collection
    {
        $widgets = $layout->getRelationValue('layoutWidgets');

        return $widgets instanceof Collection ? $widgets : collect();
    }

    private function trackRetrievedModel(object $model): void
    {
        if (! $model instanceof Model) {
            return;
        }

        if (! app()->bound(self::RETRIEVED_MODEL_STORE_SERVICE)) {
            return;
        }

        $store = resolve(self::RETRIEVED_MODEL_STORE_SERVICE);

        if (is_object($store) && method_exists($store, 'track')) {
            $store->track($model);
        }
    }

    /**
     * @param  array<int, string>|null  $containerKeys
     */
    private function preloadedKey(Layout $layout, Language $language, ?Pageable $page, ?array $containerKeys = null): string
    {
        $containers = $containerKeys === null ? '*' : implode(',', array_values(array_unique($containerKeys)));

        return 'layout:' . $layout->id . ':lang:' . $language->id . ':page:' . ($page instanceof Pageable ? $page->id : 0) . ':containers:' . $containers;
    }

    /**
     * @param  array<array-key, mixed>  $containerKeys
     */
    private function loadWidget(
        Layout $layout,
        Language $language,
        ?Pageable $page,
        string $containerKey,
        string $widgetKey,
        int $occurrence,
        ?array $containerKeys = null,
    ): ?Widget {
        $cacheKey = $this->preloadedKey($layout, $language, $page, $containerKeys);
        $map = $this->preloaded[$cacheKey] ?? null;
        if ($map === null) {
            return null;
        }

        return $map[$containerKey][$widgetKey][$occurrence] ?? null;
    }

    /**
     * @param  array<int, string>|null  $containerKeys
     * @return array<string, array<array-key, mixed>>
     */
    private function selectedLayoutContainers(Layout $layout, ?array $containerKeys): array
    {
        $containers = $layout->getAttribute('containers');
        $containers = is_array($containers) ? $containers : [];

        if ($containerKeys === null) {
            return $containers;
        }

        return collect($containers)
            ->filter(fn (mixed $container, string|int $containerKey): bool => in_array((string) $containerKey, $containerKeys, true))
            ->map(fn (mixed $container): array => is_array($container) ? $container : [])
            ->all();
    }

    /**
     * @param  array<string, array<array-key, mixed>>  $containers
     * @return array<int, string>
     */
    private function selectedWidgetKeys(array $containers): array
    {
        return collect($containers)
            ->flatMap(fn (array $container): array => LayoutWidgetData::fromContainer($container))
            ->map(static fn (array $widgetData): ?string => LayoutWidgetData::key($widgetData))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, array<array-key, mixed>>  $containers
     * @return array<int, array{container: string, occurrence: int}>
     */
    private function selectedContainerOccurrences(array $containers): array
    {
        $positions = [];

        foreach ($containers as $containerKey => $container) {
            foreach (LayoutWidgetData::fromContainer($container) as $widgetData) {
                $positions[] = [
                    'container' => $containerKey,
                    'occurrence' => LayoutWidgetData::occurrence($widgetData),
                ];
            }
        }

        return collect($positions)
            ->unique(fn (array $position): string => $position['container'] . ':' . $position['occurrence'])
            ->values()
            ->all();
    }
}
