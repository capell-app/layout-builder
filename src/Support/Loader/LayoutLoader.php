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
use Capell\LayoutBuilder\Support\LayoutBlockData;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Throwable;

class LayoutLoader
{
    private const string RETRIEVED_MODEL_STORE_SERVICE = 'capell.frontend.retrieved-model-store';

    /**
     * Preloaded blocks per [layoutId][languageId][pageIdOr0] => [containerKey][widgetKey][occurrence] => Widget
     * Used to avoid N+1 queries when resolving multiple blocks for a layout.
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

            $this->layoutBlocks($layout)->each(function (Widget $block): void {
                $this->trackRetrievedModel($block);
            });
        }

        return $layout;
    }

    /**
     * @param  array<int, string>|null  $containerKeys
     */
    public function preloadLayoutBlocks(Layout $layout, Language $language, ?Pageable $page, ?array $containerKeys = null): void
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
            $layout->setRelation('layoutBlocks', collect());
            $this->preloaded[$cacheKey] = [];

            return;
        }

        $layout->setRelation('layoutBlocks', Widget::query()
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

        $this->layoutBlocks($layout)->each(function (Widget $block): void {
            $this->trackRetrievedModel($block);

            $block->setRelation('image', $block->media->firstWhere('type', MediaCollectionEnum::Image->value));

            $block->setRelation('backgroundImage', $block->media->firstWhere('type', MediaCollectionEnum::BackgroundImage->value));
        });

        $layoutBlocks = $this->layoutBlocks($layout)->whereIn('key', $selectedWidgetKeys)->values();

        // Attach language relation to the loaded translation for consistency
        $layoutBlocks->each(function (Widget $block) use ($language): void {
            $block->translation?->setRelation('language', $language);
        });

        $this->hydrateLayoutBlocks($layoutBlocks);

        // Build a lookup for blocks by id and by key
        $blocksById = [];
        $blocksByKey = [];
        foreach ($layoutBlocks as $block) {
            $blocksById[$block->id] = $block;
            $blocksByKey[$block->key] = $block;
        }

        // Compute morph eager loads, including component-specific additions across all blocks
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

        foreach ($layoutBlocks as $block) {
            $component = $block->getComponent();
            $componentType = $block->getMetaComponentType();
            $livewire = $componentType === 'livewire';

            try {
                $componentClass = GetComponentClassAction::run($component, $livewire);
            } catch (Throwable) {
                continue;
            }

            if (method_exists($componentClass, 'loadBlockAssets')) {
                $componentClass::loadBlockAssets($with, $language);
            }
        }

        // Fetch assets for all blocks in one go (page-specific + defaults), eager loading morph relations
        $blockIds = array_keys($blocksById);
        $assetQuery = WidgetAsset::query()
            ->whereIn('widget_id', $blockIds)
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
        $defaultAssetsByBlockIdOccurrence = [];
        $pageAssetsByBlockIdContainerOcc = [];

        $assets->each(function (WidgetAsset $asset) use (&$defaultAssetsByBlockIdOccurrence, &$pageAssetsByBlockIdContainerOcc): void {
            $this->trackRetrievedModel($asset);

            $blockId = (int) $asset->block_id;
            $occurrence = $asset->occurrence ?? 1;

            if ($asset->pageable_id === null && $asset->pageable_type === null) {
                $defaultAssetsByBlockIdOccurrence[$blockId][$occurrence] ??= [];
                $defaultAssetsByBlockIdOccurrence[$blockId][$occurrence][] = $asset;

                return;
            }

            $container = $asset->container;

            $pageAssetsByBlockIdContainerOcc[$blockId][$container][$occurrence] ??= [];
            $pageAssetsByBlockIdContainerOcc[$blockId][$container][$occurrence][] = $asset;
        });

        // Build the final preloaded map per container/block/occurrence
        $result = [];
        foreach ($containers as $containerKey => $container) {
            foreach (LayoutBlockData::fromContainer($container) as $blockData) {
                $widgetKey = LayoutBlockData::key($blockData);
                if ($widgetKey === null) {
                    continue;
                }

                $occurrence = LayoutBlockData::occurrence($blockData);

                $baseBlock = $blocksByKey[$widgetKey] ?? null;
                if (! $baseBlock instanceof Widget) {
                    continue;
                }

                $clone = clone $baseBlock;
                $clone->translation?->setRelation('language', $language);

                $wid = $baseBlock->id;
                $assetsForPosition = $pageAssetsByBlockIdContainerOcc[$wid][$containerKey][$occurrence] ?? [];
                if ($assetsForPosition === []) {
                    $assetsForPosition = $defaultAssetsByBlockIdOccurrence[$wid][$occurrence] ?? [];
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
    public function getLayoutBlock(
        Layout $layout,
        string $widgetKey,
        Language $language,
        ?Pageable $page,
        string $containerKey,
        int $occurrence,
        ?array $containerKeys = null,
    ): ?Widget {
        $this->preloadLayoutBlocks($layout, $language, $page, $containerKeys);

        return $this->loadBlock($layout, $language, $page, $containerKey, $widgetKey, $occurrence, $containerKeys);
    }

    /**
     * @param  Collection<int, Widget>  $blocks
     */
    private function hydrateLayoutBlocks(Collection $blocks): void
    {
        $blocks
            ->groupBy(fn (Widget $block): string => $block->getComponent() . ':' . $block->getMetaComponentType())
            ->each(function (Collection $componentBlocks): void {
                /** @var Widget|null $firstBlock */
                $firstBlock = $componentBlocks->first();

                if (! $firstBlock instanceof Widget) {
                    return;
                }

                $component = $firstBlock->getComponent();
                $livewire = $firstBlock->getMetaComponentType() === 'livewire';

                try {
                    $componentClass = GetComponentClassAction::run($component, $livewire);
                } catch (Throwable) {
                    return;
                }

                if (! method_exists($componentClass, 'hydrateBlocks')) {
                    return;
                }

                $componentClass::hydrateBlocks($componentBlocks);
            });
    }

    /**
     * @return Collection<int, Widget>
     */
    private function layoutBlocks(Layout $layout): Collection
    {
        $blocks = $layout->getRelationValue('layoutBlocks');

        return $blocks instanceof Collection ? $blocks : collect();
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

    /**
     * @param  array<array-key, mixed>  $containerKeys
     */
    private function loadBlock(
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
            ->flatMap(fn (array $container): array => LayoutBlockData::fromContainer($container))
            ->map(static fn (array $blockData): ?string => LayoutBlockData::key($blockData))
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
            foreach (LayoutBlockData::fromContainer($container) as $blockData) {
                $positions[] = [
                    'container' => $containerKey,
                    'occurrence' => LayoutBlockData::occurrence($blockData),
                ];
            }
        }

        return collect($positions)
            ->unique(fn (array $position): string => $position['container'] . ':' . $position['occurrence'])
            ->values()
            ->all();
    }
}
