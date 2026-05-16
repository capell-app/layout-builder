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
use Capell\LayoutBuilder\Models\Element;
use Capell\LayoutBuilder\Models\ElementAsset;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Throwable;

class LayoutLoader
{
    private const RETRIEVED_MODEL_STORE_SERVICE = 'capell.frontend.retrieved-model-store';

    /**
     * Preloaded elements per [layoutId][languageId][pageIdOr0] => [containerKey][elementKey][occurrence] => Element
     * Used to avoid N+1 queries when resolving multiple elements for a layout.
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

            $this->layoutElements($layout)->each(function (Element $element): void {
                $this->trackRetrievedModel($element);
            });
        }

        return $layout;
    }

    /**
     * @param  array<int, string>|null  $containerKeys
     */
    public function preloadLayoutElements(Layout $layout, Language $language, ?Pageable $page, ?array $containerKeys = null): void
    {
        $cacheKey = $this->preloadedKey($layout, $language, $page, $containerKeys);
        if (isset($this->preloaded[$cacheKey])) {
            return;
        }

        $containers = $this->selectedLayoutContainers($layout, $containerKeys);
        $selectedElementKeys = $this->selectedElementKeys($containers);
        $selectedContainerOccurrences = $this->selectedContainerOccurrences($containers);

        if ($selectedElementKeys === [] || ! Schema::hasTable('elements')) {
            $layout->setRelation('layoutElements', collect());
            $this->preloaded[$cacheKey] = [];

            return;
        }

        $layout->setRelation('layoutElements', Element::query()
            ->whereIn('key', $selectedElementKeys)
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

        $this->layoutElements($layout)->each(function (Element $element): void {
            $this->trackRetrievedModel($element);

            $element->setRelation('image', $element->media->firstWhere('type', MediaCollectionEnum::Image->value));

            $element->setRelation('backgroundImage', $element->media->firstWhere('type', MediaCollectionEnum::BackgroundImage->value));
        });

        $layoutElements = $selectedElementKeys === []
            ? collect()
            : $this->layoutElements($layout)->whereIn('key', $selectedElementKeys)->values();

        // Attach language relation to the loaded translation for consistency
        $layoutElements->each(function (Element $element) use ($language): void {
            $element->translation?->setRelation('language', $language);
        });

        $this->hydrateLayoutElements($layoutElements);

        // Build a lookup for elements by id and by key
        $elementsById = [];
        $elementsByKey = [];
        foreach ($layoutElements as $element) {
            $elementsById[$element->id] = $element;
            $elementsByKey[$element->key] = $element;
        }

        // Compute morph eager loads, including component-specific additions across all elements
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

        foreach ($layoutElements as $element) {
            $component = $element->getComponent();
            $componentType = $element->getMetaComponentType();
            $livewire = $componentType === 'livewire';

            try {
                $componentClass = GetComponentClassAction::run($component, $livewire);
            } catch (Throwable) {
                continue;
            }

            if (method_exists($componentClass, 'loadElementAssets')) {
                $componentClass::loadElementAssets($with, $language);
            }
        }

        // Fetch assets for all elements in one go (page-specific + defaults), eager loading morph relations
        $elementIds = array_keys($elementsById);
        $assetQuery = ElementAsset::query()
            ->whereIn('layout_element_id', $elementIds)
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
        $defaultAssetsByElementIdOccurrence = [];
        $pageAssetsByElementIdContainerOcc = [];

        $assets->each(function (ElementAsset $asset) use (&$defaultAssetsByElementIdOccurrence, &$pageAssetsByElementIdContainerOcc): void {
            $this->trackRetrievedModel($asset);

            $elementId = (int) $asset->layout_element_id;
            $occurrence = $asset->occurrence ?? 1;

            if ($asset->pageable_id === null && $asset->pageable_type === null) {
                $defaultAssetsByElementIdOccurrence[$elementId][$occurrence] ??= [];
                $defaultAssetsByElementIdOccurrence[$elementId][$occurrence][] = $asset;

                return;
            }

            $container = $asset->container;

            $pageAssetsByElementIdContainerOcc[$elementId][$container][$occurrence] ??= [];
            $pageAssetsByElementIdContainerOcc[$elementId][$container][$occurrence][] = $asset;
        });

        // Build the final preloaded map per container/element/occurrence
        $result = [];
        foreach ($containers as $containerKey => $container) {
            if (! isset($container['elements'])) {
                continue;
            }

            if (! is_array($container['elements'])) {
                continue;
            }

            foreach ($container['elements'] as $elementData) {
                if (! isset($elementData['element_key'])) {
                    continue;
                }

                $elementKey = (string) $elementData['element_key'];
                $occurrence = (int) ($elementData['occurrence'] ?? 1);

                $baseElement = $elementsByKey[$elementKey] ?? null;
                if (! $baseElement instanceof Element) {
                    continue;
                }

                $clone = clone $baseElement;
                $clone->translation?->setRelation('language', $language);

                $wid = $baseElement->id;
                $assetsForPosition = $pageAssetsByElementIdContainerOcc[$wid][$containerKey][$occurrence] ?? [];
                if ($assetsForPosition === []) {
                    $assetsForPosition = $defaultAssetsByElementIdOccurrence[$wid][$occurrence] ?? [];
                }

                $clone->setRelation('assets', collect($assetsForPosition));

                $result[$containerKey][$elementKey][$occurrence] = $clone;
            }
        }

        $this->preloaded[$cacheKey] = $result;
    }

    public function getLayoutElement(
        Layout $layout,
        string $elementKey,
        Language $language,
        ?Pageable $page,
        string $containerKey,
        int $occurrence,
        ?array $containerKeys = null,
    ): ?Element {
        $this->preloadLayoutElements($layout, $language, $page, $containerKeys);

        return $this->loadElement($layout, $language, $page, $containerKey, $elementKey, $occurrence, $containerKeys);
    }

    /**
     * @param  Collection<int, Element>  $elements
     */
    private function hydrateLayoutElements(Collection $elements): void
    {
        $elements
            ->groupBy(fn (Element $element): string => $element->getComponent() . ':' . $element->getMetaComponentType())
            ->each(function (Collection $componentElements): void {
                /** @var Element|null $firstElement */
                $firstElement = $componentElements->first();

                if (! $firstElement instanceof Element) {
                    return;
                }

                $component = $firstElement->getComponent();
                $livewire = $firstElement->getMetaComponentType() === 'livewire';

                try {
                    $componentClass = GetComponentClassAction::run($component, $livewire);
                } catch (Throwable) {
                    return;
                }

                if (! method_exists($componentClass, 'hydrateElements')) {
                    return;
                }

                $componentClass::hydrateElements($componentElements);
            });
    }

    /**
     * @return Collection<int, Element>
     */
    private function layoutElements(Layout $layout): Collection
    {
        $elements = $layout->getRelationValue('layoutElements');

        return $elements instanceof Collection ? $elements : collect();
    }

    private function trackRetrievedModel(object $model): void
    {
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

    private function loadElement(
        Layout $layout,
        Language $language,
        ?Pageable $page,
        string $containerKey,
        string $elementKey,
        int $occurrence,
        ?array $containerKeys = null,
    ): ?Element {
        $cacheKey = $this->preloadedKey($layout, $language, $page, $containerKeys);
        $map = $this->preloaded[$cacheKey] ?? null;
        if ($map === null) {
            return null;
        }

        return $map[$containerKey][$elementKey][$occurrence] ?? null;
    }

    /**
     * @param  array<int, string>|null  $containerKeys
     * @return array<string, array<string, mixed>>
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
     * @param  array<string, array<string, mixed>>  $containers
     * @return array<int, string>
     */
    private function selectedElementKeys(array $containers): array
    {
        return collect($containers)
            ->flatMap(fn (array $container): array => is_array($container['elements'] ?? null) ? $container['elements'] : [])
            ->map(fn (mixed $elementData): ?string => is_array($elementData) && is_string($elementData['element_key'] ?? null) ? $elementData['element_key'] : null)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, array<string, mixed>>  $containers
     * @return array<int, array{container: string, occurrence: int}>
     */
    private function selectedContainerOccurrences(array $containers): array
    {
        $positions = [];

        foreach ($containers as $containerKey => $container) {
            $elements = $container['elements'] ?? [];

            if (! is_array($elements)) {
                continue;
            }

            foreach ($elements as $elementData) {
                if (! is_array($elementData)) {
                    continue;
                }

                $positions[] = [
                    'container' => $containerKey,
                    'occurrence' => (int) ($elementData['occurrence'] ?? 1),
                ];
            }
        }

        return collect($positions)
            ->unique(fn (array $position): string => $position['container'] . ':' . $position['occurrence'])
            ->values()
            ->all();
    }
}
