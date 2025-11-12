<?php

declare(strict_types=1);

?>

@props([
    'columns' => $container['meta']['override_columns'] ?? ($widget->meta['columns'] ?? 3),
    'container',
    'containerKey',
    'containerWidth' => null,
    'showPageContent' => $widgetData['meta']['show_page_content'] ?? false,
    'showPageTitle' => $widgetData['meta']['show_page_title'] ?? false,
    'items' => [],
    'loop',
    'widget',
])
<x-capell-layout::widget.wrapper
    class="widget-navigation-bar"
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :$widget
>
    @if (($widget->translation && ($widget->translation->title || $widget->translation->content))
         || ($showPageContent && $page->translation->title)
         || ($showPageTitle && $page->translation->content))
        <x-capell::content
            class="mb-5"
            :compact="true"
            :content="$widget->translation->content ?? ($showPageContent ? $page->translation->content : null)"
            :presenter="$widget->type->meta['content_presenter'] ?? null"
            :text-align="$widget->meta['align'] ?? $widget->type->meta['align'] ?? null"
            :title="$widget->translation->title ?? ($showPageTitle ? $page->translation->title : null)"
        />
    @endif

    @if (count($items) > 5)
        <div class="grid md:grid-cols-2">
            @foreach (collect($items)->chunk(round(count($items) / 2)) as $chunked)
                <x-dynamic-component
                    :component="! empty($menu->meta['component']) ? $menu->meta['component'] : 'capell::list'"
                >
                    @foreach ($chunked as $item)
                        <x-dynamic-component
                            :component="
                                ! empty($item['data']['component'])
                                ? $item['data']['component']
                                : 'capell::list.item'
                            "
                            :$item
                        />
                    @endforeach
                </x-dynamic-component>
            @endforeach
        </div>
    @else
        <x-dynamic-component
            :component="! empty($menu->meta['component']) ? $menu->meta['component'] : 'capell::list'"
        >
            @foreach ($items as $item)
                <x-dynamic-component
                    :component="
                        ! empty($item['data']['component'])
                        ? $item['data']['component']
                        : 'capell::list.item'
                    "
                    :$item
                />
            @endforeach
        </x-dynamic-component>
    @endif
</x-capell-layout::widget.wrapper>

<?php
