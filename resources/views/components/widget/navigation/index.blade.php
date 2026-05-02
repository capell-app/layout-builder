@php
    use Capell\Frontend\Facades\Frontend;
    use Capell\Navigation\Data\NavigationItemData;
    use Capell\Navigation\Models\Navigation;
    use Capell\Navigation\Support\Loader\NavigationItemsLoader;
    use Capell\Navigation\Support\Loader\NavigationLoader;
    use Illuminate\Support\Collection;

    $theme = Frontend::theme();

    if (! isset($menu)) {
        $menu = null;

        if (isset($widget->meta['navigation_id']) && is_numeric($widget->meta['navigation_id'])) {
            $menu = NavigationLoader::getNavigationById($widget->meta['navigation_id']);
        } elseif (isset($widget->meta['navigation']) && is_string($widget->meta['navigation'])) {
            $menu = NavigationLoader::getNavigation(
                $widget->meta['navigation'],
                Frontend::site(),
                Frontend::language(),
            );
        }
    }

    if (! isset($items)) {
        $items = collect();

        if ($menu instanceof Navigation) {
            $navigationLoader = new NavigationItemsLoader(
                navigation: $menu,
                page: Frontend::page(),
                site: Frontend::site(),
                language: Frontend::language(),
                siteDomain: Frontend::site()->siteDomain,
            );

            $items = $navigationLoader->fetchMenuItems();
            $navigationLoader->activeMenuItems($items);
        }
    }
@endphp

@props([
    'columns' => $container['meta']['override_columns'] ?? $widget->getMeta('columns', 3),
    'container',
    'containerKey',
    'containerWidth' => null,
    'groupItems' => $widgetData['meta']['group_items'] ?? false,
    'showPageContent' => $widgetData['meta']['show_page_content'] ?? false,
    'showPageTitle' => $widgetData['meta']['show_page_title'] ?? false,
    'items' => [],
    'loop',
    'widget',
])
@if ($items->isNotEmpty() || ! config('capell-mosaic.widget.skip_render_empty', true))
    <x-capell-mosaic::widget.wrapper
        class="widget-navigation"
        :$container
        :$containerKey
        :$containerWidth
        :index="$loop->index"
        :$widget
    >
        @if (($widget->translation && ($widget->translation->title || $widget->translation->content))
             || ($showPageTitle && $page->translation->title)
             || ($showPageContent && $page->translation->content))
            <x-capell::content
                class="mb-5"
                :compact="true"
                :content="$widget->translation->content ?? ($showPageContent ? $page->translation->content : null)"
                :content-type="$widget->translation->content ? $widget->type->content_structure : ($showPageContent ? $page->type->content_structure : null)"
                :divider="$widget->getMeta('content_divider')"
                :muted="in_array($containerKey, $theme->secondary_containers)"
                :text-align="$widget->getMeta('align')"
                :title="$widget->translation->title ?? ($showPageTitle ? $page->translation->title : null)"
                :heading-style="$widget->getMeta('heading_style')"
                :heading-tag="$showPageTitle ? 'h1' : null"
            />
        @endif

        @if ($groupItems && count($items) > 5)
            <div class="grid md:grid-cols-2">
                @php
                    /**
                     * @var Collection<NavigationItemData> $items
                     */
                    $half = (int) ceil(count($items) / $columns);

                    /**
                     * @var Collection<Collection<NavigationItemData>> $chunks
                     */
                    $chunks = $items->chunk($half);
                @endphp

                @foreach ($chunks as $chunk)
                    <x-dynamic-component
                        :component="$menu->getMeta('component', 'capell::list')"
                        class="widget-navigation-list"
                    >
                        @foreach ($chunk as $item)
                            <x-dynamic-component
                                :component="! empty($item->data['component_item']) ? $item->data['component_item'] : 'capell::list.item'"
                                class="widget-navigation-item"
                                :$item
                            />
                        @endforeach
                    </x-dynamic-component>
                @endforeach
            </div>
        @else
            <x-dynamic-component
                :component="$menu->getMeta('component', 'capell::list')"
                class="widget-navigation-list widget-navigation-lit-children text-sm"
            >
                @foreach ($items as $item)
                    <x-dynamic-component
                        :component="! empty($item->data['component_item']) ? $item->data['component_item'] : 'capell::list.item'"
                        :$item
                        class="widget-navigation-item widget-navigation-child-item"
                    />
                @endforeach
            </x-dynamic-component>
        @endif
    </x-capell-mosaic::widget.wrapper>
@endif
