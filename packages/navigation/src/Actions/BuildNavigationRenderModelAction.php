<?php

declare(strict_types=1);

namespace Capell\Navigation\Actions;

use Capell\Navigation\Data\NavigationItemData;
use Capell\Navigation\Data\NavigationItemRenderData;
use Capell\Navigation\Data\NavigationRenderContextData;
use Capell\Navigation\Data\NavigationRenderData;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Support\Loader\NavigationItemsLoader;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static NavigationRenderData run(NavigationRenderContextData $context)
 */
class BuildNavigationRenderModelAction
{
    use AsObject;

    private const REQUEST_CACHE_KEY = 'capell.navigation.render_models';

    public static function flushPageCache(): void
    {
        NavigationItemsLoader::flushPageCache();

        if (app()->bound('request')) {
            request()->attributes->remove(self::REQUEST_CACHE_KEY);
        }
    }

    public function handle(NavigationRenderContextData $context): NavigationRenderData
    {
        $cacheKey = $this->cacheKey($context);
        $request = app()->bound('request') ? request() : null;
        $cache = $this->requestCache($request);

        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        $loader = new NavigationItemsLoader(
            navigation: $context->navigation,
            page: $context->page,
            site: $context->site,
            language: $context->language,
            siteDomain: $context->siteDomain,
        );

        $items = $loader->load();

        $renderModel = new NavigationRenderData(
            navigationId: $context->navigation->exists ? (int) $context->navigation->getKey() : null,
            navigationKey: $context->navigation->key,
            navigationName: $context->navigation->name,
            listComponent: $this->listComponent($context->navigation),
            items: $this->mapItems($items),
        );

        $cache[$cacheKey] = $renderModel;
        $request?->attributes->set(self::REQUEST_CACHE_KEY, $cache);

        return $renderModel;
    }

    /**
     * @param  Collection<int, NavigationItemData>  $items
     * @return Collection<int, NavigationItemRenderData>
     */
    private function mapItems(Collection $items): Collection
    {
        return $items
            ->map(fn (NavigationItemData $item): NavigationItemRenderData => $this->mapItem($item))
            ->values();
    }

    private function mapItem(NavigationItemData $item): NavigationItemRenderData
    {
        $data = $item->data;

        return new NavigationItemRenderData(
            label: $item->label,
            type: $item->type,
            url: isset($data['url']) && is_string($data['url']) ? $data['url'] : null,
            active: $item->active === true,
            children: $this->mapItems(collect($item->children->all())),
            data: $this->viewData($data),
            target: isset($data['target']) && is_string($data['target']) ? $data['target'] : null,
            icon: isset($data['icon']) && is_string($data['icon']) ? $data['icon'] : null,
            activeIcon: isset($data['active_icon']) && is_string($data['active_icon']) ? $data['active_icon'] : null,
            class: isset($data['class']) && is_string($data['class']) ? $data['class'] : null,
            component: isset($data['component']) && is_string($data['component']) ? $data['component'] : null,
            componentItem: isset($data['component_item']) && is_string($data['component_item']) ? $data['component_item'] : null,
            hideLabel: ($data['hide_label'] ?? false) === true,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function viewData(array $data): array
    {
        $viewData = [];

        foreach (['url', 'target', 'icon', 'active_icon', 'class', 'component', 'component_item', 'hide_label'] as $key) {
            if (array_key_exists($key, $data)) {
                $viewData[$key] = $data[$key];
            }
        }

        return $viewData;
    }

    private function listComponent(Navigation $navigation): string
    {
        $component = $navigation->getMeta('component', 'capell::list');

        return is_string($component) && $component !== '' ? $component : 'capell::list';
    }

    /**
     * @return array<string, NavigationRenderData>
     */
    private function requestCache(?Request $request): array
    {
        if (! $request instanceof Request) {
            return [];
        }

        $cache = $request->attributes->get(self::REQUEST_CACHE_KEY, []);

        return is_array($cache) ? $cache : [];
    }

    private function cacheKey(NavigationRenderContextData $context): string
    {
        return implode('|', [
            $context->navigation->exists ? (string) $context->navigation->getKey() : 'new:' . spl_object_id($context->navigation),
            $context->navigation->key,
            (string) $context->navigation->updated_at?->getTimestamp(),
            (string) $context->page->getMorphClass(),
            (string) $context->page->getKey(),
            (string) $context->site->getKey(),
            (string) $context->language->getKey(),
            (string) $context->siteDomain->getKey(),
        ]);
    }
}
