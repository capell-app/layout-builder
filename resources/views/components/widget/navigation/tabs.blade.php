@php
    use Capell\Frontend\Facades\Frontend;
    use Capell\Navigation\Actions\BuildNavigationRenderModelAction;
    use Capell\Navigation\Data\NavigationRenderContextData;
    use Capell\Navigation\Data\NavigationRenderData;
    use Capell\Navigation\Models\Navigation;
    use Capell\Navigation\Support\Loader\NavigationLoader;

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

    if (! isset($navigationRenderData)) {
        $navigationRenderData = null;
        if ($menu instanceof Navigation) {
            $navigationRenderData = BuildNavigationRenderModelAction::run(new NavigationRenderContextData(
                navigation: $menu,
                page: Frontend::page(),
                site: Frontend::site(),
                language: Frontend::language(),
                siteDomain: Frontend::site()->siteDomain,
            ));
        }
    }

    if (! isset($items)) {
        $items = $navigationRenderData instanceof NavigationRenderData ? $navigationRenderData->items : collect();
    }
@endphp

@props([
    'container' => '',
    'containerKey',
    'containerWidth' => null,
])
@if ($items->isNotEmpty() || ! config('capell-mosaic.widget.skip_render_empty', true))
    <x-capell-mosaic::widget.wrapper
        class="widget-navigation-tabs"
        :$container
        :$containerKey
        :$containerWidth
        :index="$loop->index"
        :$widget
    >
        <ul
            class="tab-items mb-4 mt-10 flex flex-col flex-wrap items-center gap-4 border-b border-gray-100 px-2 text-center text-sm font-medium text-gray-500 md:flex-row"
        >
            @foreach ($items as $item)
                <li class="tab-item -mb-px">
                    <a
                        href="{{ $item->data['url'] }}"
                        @class([
                            'hover:bg-primary inline-block rounded-t border-b-2 border-transparent px-4 py-3 hover:text-white',
                            'border-b-primary' => $item->active,
                        ])
                        wire:navigate
                    >
                        {{ $item->label }}
                    </a>
                </li>
            @endforeach
        </ul>
    </x-capell-mosaic::widget.wrapper>
@endif
