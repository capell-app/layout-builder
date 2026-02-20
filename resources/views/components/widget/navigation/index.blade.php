<?php

declare(strict_types=1);

use Capell\Frontend\Facades\Frontend;

$theme = Frontend::theme();
?>

@props([
    'columns' => $container['meta']['override_columns'] ?? ($widget->meta['columns'] ?? 3),
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
<x-capell-layout::widget.wrapper
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
            :muted="in_array($containerKey, $theme->secondary_containers)"
            :text-align="$widget->meta['align'] ?? $widget->type->meta['align'] ?? null"
            :title="$widget->translation->title ?? ($showPageTitle ? $page->translation->title : null)"
            :heading-style="($widget->meta['heading_style'] ?? null) ?: ($widget->type->meta['heading_style'] ?? null)"
            :heading-tag="$showPageTitle ? 'h1' : null"
        />
    @endif

    @if ($groupItems && count($items) > 5)
        <div class="grid md:grid-cols-2">
            @php
                $chunkedItems = collect($items)->chunk(ceil(count($items) / $columns));
            @endphp

            @foreach ($chunkedItems as $chunked)
                <x-dynamic-component
                    :component="! empty($menu->meta['component']) ? $menu->meta['component'] : 'capell::list'"
                    class="widget-navigation-list"
                >
                    @foreach ($chunked as $item)
                        <x-dynamic-component
                            :component="
                                ! empty($item['data']['component'])
                                ? $item['data']['component']
                                : 'capell::list.item'
                            "
                            class="widget-navigation-item"
                            :$item
                        />
                    @endforeach
                </x-dynamic-component>
            @endforeach
        </div>
    @else
        <x-dynamic-component
            :component="! empty($menu->meta['component']) ? $menu->meta['component'] : 'capell::list'"
            class="widget-navigation-list widget-navigation-lit-children"
        >
            @foreach ($items as $item)
                <x-dynamic-component
                    :component="
                        ! empty($item['data']['component'])
                        ? $item['data']['component']
                        : 'capell::list.item'
                    "
                    class="widget-navigation-item widget-navigation-child-item"
                    :$item
                />
            @endforeach
        </x-dynamic-component>
    @endif
</x-capell-layout::widget.wrapper>

<?php
