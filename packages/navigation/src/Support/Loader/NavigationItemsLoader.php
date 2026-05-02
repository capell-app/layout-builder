<?php

declare(strict_types=1);

namespace Capell\Navigation\Support\Loader;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\PageOrderEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Frontend\Support\Loader\PageLoader;
use Capell\Navigation\Data\NavigationItemData;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Spatie\LaravelData\DataCollection;

class NavigationItemsLoader
{
    /** @var array<string, array<string, Pageable>> */
    protected static array $pagesByMorphKeyCache = [];

    public function __construct(
        public Navigation $navigation,
        public Pageable $page,
        public Site $site,
        public Language $language,
        public SiteDomain $siteDomain,
    ) {}

    public static function flushPageCache(): void
    {
        self::$pagesByMorphKeyCache = [];
    }

    /**
     * @return Collection<int, NavigationItemData>
     */
    public function load(): Collection
    {
        $items = $this->fetchMenuItems();

        if ($items->isNotEmpty()) {
            $this->activeMenuItems($items);
        }

        return $items;
    }

    /**
     * @param  Collection<int, NavigationItemData>  $items
     */
    public function activeMenuItems(Collection $items): bool
    {
        $hasActive = false;

        $currentUrl = $this->page->pageUrl->full_url;

        foreach ($items as $item) {
            $active = false;

            switch ($item->type) {
                case NavigationItemType::Link:
                    if (! isset($item->data['url'])) {
                        continue 2;
                    }

                    if ($item->data['url'] === '') {
                        continue 2;
                    }

                    if ($item->data['url'][0] !== '/') {
                        continue 2;
                    }

                    $url = $this->siteDomain->url . $item->data['url'];

                    if ($url !== '/') {
                        $url = mb_ltrim($url, '/');
                    }

                    $active = $this->urlMatches($currentUrl, $url);
                    break;

                case NavigationItemType::Page:
                    $pageableReference = $this->extractPageableReference([
                        'data' => $item->data,
                        'type' => $item->type,
                    ]);

                    $active = $pageableReference !== null
                        && $pageableReference['pageable_id'] === (int) $this->page->getKey()
                        && $pageableReference['pageable_type'] === $this->page->getMorphClass();
                    break;
            }

            if ($active) {
                $hasActive = true;
                $item->active = true;
            }

            $children = collect($item->children->all());

            if ($children->isNotEmpty()) {
                $activeChild = $this->activeMenuItems($children);

                if ($activeChild) {
                    $hasActive = true;
                    $item->active = true;
                }
            }
        }

        return $hasActive;
    }

    /**
     * @return Collection<int, NavigationItemData>
     */
    public function fetchMenuItems(): Collection
    {
        $items = collect($this->navigation->items->all());

        if ($items->isEmpty()) {
            return collect();
        }

        // Cast to Illuminate\Support\Collection explicitly
        $pageableIdsByType = $this->extractMenuItemsPagesByType(new Collection($items->all()));

        $pagesByMorphKey = $this->getPagesByMorphKey($pageableIdsByType);

        // Cast to Illuminate\Support\Collection explicitly
        $result = $this->menuItemPageSetup($this->navigation, new Collection($items->all()), $pagesByMorphKey);

        $this->navigation->items = $result;

        return collect($result->all());
    }

    /**
     * @param  Collection<int, NavigationItemData>  $items
     * @param  array<string, Pageable>  $pagesByMorphKey
     * @return DataCollection<int, NavigationItemData>
     */
    protected function menuItemPageSetup(Navigation $navigation, Collection $items, array $pagesByMorphKey): DataCollection
    {
        $result = [];

        $items->each(function (NavigationItemData $item) use (&$result, $navigation, $pagesByMorphKey): void {
            $menuItem = [
                'label' => $item->label,
                'type' => $item->type,
                'data' => $item->data,
                'children' => $item->children,
            ];

            $children = collect($item->children->all());

            // Cast to Illuminate\Support\Collection explicitly
            if ($children->isNotEmpty()) {
                $menuItem['children'] = $this->menuItemPageSetup($navigation, new Collection($children->all()), $pagesByMorphKey);
            } elseif (isset($item->data['auto_children']) && $item->data['auto_children'] === true) {
                $pageableReference = $this->extractPageableReference([
                    'data' => $item->data,
                    'type' => $item->type,
                ]);

                if ($pageableReference === null || $pageableReference['pageable_type'] !== $this->page->getMorphClass()) {
                    $result[] = new NavigationItemData(...$menuItem);

                    return;
                }

                $children = PageLoader::getPages(
                    language: $this->language,
                    site: $this->site,
                    ordering: PageOrderEnum::Default,
                    optionalLanguage: true,
                    withChildren: true,
                    cacheKeyPrepend: 'parent-' . $pageableReference['pageable_id'],
                    modifyQuery: fn (BuilderContract $query): BuilderContract => $query->where(
                        'parent_id',
                        $pageableReference['pageable_id'],
                    ),
                );

                if ($children->isNotEmpty()) {
                    $menuItem['children'] = NavigationItemData::fromPages($children);
                }
            }

            if ($item->type === NavigationItemType::Page) {
                $pageableReference = $this->extractPageableReference([
                    'data' => $item->data,
                    'type' => $item->type,
                ]);

                if ($pageableReference === null) {
                    return;
                }

                $lookupKey = $this->buildMorphLookupKey(
                    $pageableReference['pageable_type'],
                    $pageableReference['pageable_id'],
                );

                /** @var ?Pageable $page */
                $page = $pagesByMorphKey[$lookupKey] ?? null;

                if (! $page instanceof Pageable) {
                    return;
                }

                if (blank($item->label)) {
                    $menuItem['label'] = $page->translation->label;
                }

                $menuItem['data']['url'] = $page->pageUrl->full_url;
            }

            $result[] = new NavigationItemData(...$menuItem);
        });

        return NavigationItemData::collect($result, DataCollection::class);
    }

    protected function urlMatches(string $currentUrl, string $menuUrl): bool
    {
        // Normalize both URLs for comparison
        $normalizedCurrent = trim($currentUrl, '/');
        $normalizedMenu = trim($menuUrl, '/');

        return $normalizedCurrent === $normalizedMenu;
    }

    /**
     * @param  Collection<int, NavigationItemData>  $items
     * @return array<string, array<int, int>>
     */
    protected function extractMenuItemsPagesByType(Collection $items): array
    {
        $pageableIdsByType = [];

        foreach ($items as $item) {
            $pageableReference = $this->extractPageableReference([
                'data' => $item->data,
                'type' => $item->type,
            ]);

            if ($item->type === NavigationItemType::Page && $pageableReference !== null) {
                $pageableType = $pageableReference['pageable_type'];
                $pageableId = $pageableReference['pageable_id'];

                if (! isset($pageableIdsByType[$pageableType])) {
                    $pageableIdsByType[$pageableType] = [];
                }

                if (! in_array($pageableId, $pageableIdsByType[$pageableType], true)) {
                    $pageableIdsByType[$pageableType][] = $pageableId;
                }
            }

            $children = collect($item->children->all());

            if ($children->isNotEmpty()) {
                $nestedPageableIdsByType = $this->extractMenuItemsPagesByType($children);

                foreach ($nestedPageableIdsByType as $pageableType => $nestedPageableIds) {
                    if (! isset($pageableIdsByType[$pageableType])) {
                        $pageableIdsByType[$pageableType] = [];
                    }

                    foreach ($nestedPageableIds as $nestedPageableId) {
                        if (! in_array($nestedPageableId, $pageableIdsByType[$pageableType], true)) {
                            $pageableIdsByType[$pageableType][] = $nestedPageableId;
                        }
                    }
                }
            }
        }

        return $pageableIdsByType;
    }

    /**
     * @param  array<string, array<int, int>>  $pageableIdsByType
     * @return array<string, Pageable>
     */
    protected function getPagesByMorphKey(array $pageableIdsByType): array
    {
        $currentPageType = $this->page->getMorphClass();
        $currentPageId = (int) $this->page->getKey();
        $currentPageLookupKey = $this->buildMorphLookupKey($currentPageType, $currentPageId);

        $pagesByMorphKey = [
            $currentPageLookupKey => $this->page,
        ];

        foreach ($pageableIdsByType as $pageableType => $pageableIds) {
            if ($pageableIds === []) {
                continue;
            }

            if ($pageableType === $currentPageType) {
                $pageableIds = array_values(array_filter(
                    $pageableIds,
                    static fn (int $pageableId): bool => $pageableId !== $currentPageId,
                ));
            }

            if ($pageableIds === []) {
                continue;
            }

            $cacheKey = implode(':', [
                $pageableType,
                $this->language->getKey(),
                $this->siteDomain->getKey(),
                implode(',', $pageableIds),
            ]);

            if (isset(self::$pagesByMorphKeyCache[$cacheKey])) {
                $pagesByMorphKey = [
                    ...$pagesByMorphKey,
                    ...self::$pagesByMorphKeyCache[$cacheKey],
                ];

                continue;
            }

            $modelClass = Relation::getMorphedModel($pageableType) ?? $pageableType;
            if (! is_string($modelClass)) {
                continue;
            }

            if (! is_subclass_of($modelClass, Model::class)) {
                continue;
            }

            /** @var class-string<Model&Pageable> $modelClass */
            $model = new $modelClass;

            $query = $modelClass::query()
                ->with(['translation', 'pageUrl'])
                ->whereIn($model->getKeyName(), $pageableIds)
                ->orderBy($model->getKeyName());

            if (method_exists($model, 'site')) {
                $query->where('site_id', $this->site->getKey());
            }

            $pages = $query->get();

            /** @var Model&Pageable $page */
            $cachedPages = [];

            foreach ($pages as $page) {
                if (
                    $page->pageUrl !== null
                    && $page->pageUrl->site_id === $this->siteDomain->site_id
                    && $page->pageUrl->language_id === $this->siteDomain->language_id
                ) {
                    $page->pageUrl->setRelation('siteDomain', $this->siteDomain);
                } elseif ($page->pageUrl !== null) {
                    $page->pageUrl->setRelation(
                        'siteDomain',
                        SiteDomain::query()
                            ->where('site_id', $page->pageUrl->site_id)
                            ->where('language_id', $page->pageUrl->language_id)
                            ->first(),
                    );
                }

                $lookupKey = $this->buildMorphLookupKey($pageableType, (int) $page->getKey());

                $pagesByMorphKey[$lookupKey] = $page;
                $cachedPages[$lookupKey] = $page;
            }

            self::$pagesByMorphKeyCache[$cacheKey] = $cachedPages;
        }

        return $pagesByMorphKey;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{pageable_id:int, pageable_type:string}|null
     */
    protected function extractPageableReference(array $item): ?array
    {
        $pageableId = $item['data']['pageable_id'] ?? null;
        $pageableType = $item['data']['pageable_type'] ?? null;

        if (! is_numeric($pageableId) || ! is_string($pageableType) || $pageableType === '') {
            return null;
        }

        return [
            'pageable_id' => (int) $pageableId,
            'pageable_type' => $pageableType,
        ];
    }

    protected function buildMorphLookupKey(string $pageableType, int $pageableId): string
    {
        return $pageableType . ':' . $pageableId;
    }
}
