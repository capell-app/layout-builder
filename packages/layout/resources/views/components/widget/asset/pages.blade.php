<?php

declare(strict_types=1);

?>

@php
    use Capell\Core\Enums\AssetComponentEnum;
    use Capell\Core\Facades\CapellCore;
    use Capell\Frontend\Facades\Frontend;

    $language = Frontend::language();
    $theme = Frontend::theme();
@endphp

@props([
    'columns' => $container['meta']['override_columns'] ?? $widget->getMeta('columns', 3),
    'componentItem' => $widget->getMeta('component_item', AssetComponentEnum::Card->value),
    'container',
    'containerKey',
    'containerWidth' => null,
    'showPageContent' => $widgetData['meta']['show_page_content'] ?? false,
    'showPageTitle' => $widgetData['meta']['show_page_title'] ?? false,
    'index',
    'loop',
    'size' => $widget->getMeta('size', $containerKey === 'sidebar' ? 'sm' : null),
    'spacing' => $widget->getMeta('spacing', $containerKey === 'sidebar' ? 'md' : 'lg'),
    'widget',
    'withChildCount' => (bool) $widget->getMeta('with_child_count'),
    'withImage' => (bool) $widget->getMeta('with_image'),
    'withParent' => (bool) $widget->getMeta('with_parent'),
    'withDate' => (bool) $widget->getMeta('with_date'),
    'withSummary' => (bool) $widget->getMeta('with_summary'),
])
<x-capell-layout::widget.wrapper
    class="widget-pages"
    container-class="space-y-4"
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :$widget
>
    @php
        $showTitle = $widget->getMeta("container_options.{$containerKey}.hide_title") !== true
            && ($widget->translation?->title || ($showPageTitle && $page->translation->title));
        $showContent = $widget->getMeta("container_options.{$containerKey}.hide_content") !== true
            && ($widget->translation?->content || ($showPageContent && $page->translation->content));
    @endphp

    @if ($showTitle || $showContent)
        <x-capell::content
            class="widget-content"
            :compact="true"
            :content="$showContent ? ($widget->translation->content ?: ($showPageContent ? $page->translation->content : null)) : null"
            :content-type="$widget->translation->content ? $widget->type->content_structure : ($showPageContent ? $page->type->content_structure : null)"
            :divider="$widget->getMeta('content_divider')"
            :muted="in_array($containerKey, $theme->secondary_containers)"
            :text-align="$widget->getMeta('align')"
            :title="$showTitle ? ($widget->translation->title ?: ($showPageTitle ? $page->translation->title : null)) : null"
            :heading-style="$widget->getMeta('heading_style')"
            :heading-tag="$showPageTitle ? 'h1' : null"
        />
    @endif

    @if (! $pages || $pages->isEmpty())
        <x-capell::no-results>
            {!! $widget->translation->getMeta('no_results', __('capell-layout::generic.no_pages_found')) !!}
        </x-capell::no-results>
    @else
        <div
            @class([
                'grid',
                ...$containerKey === 'sidebar' && (! $columns && $columns !== 0)
                ? [
                    'divide-y divide-gray-100 [&>*:not(:first-child)]:pt-4 [&>*:not(:last-child)]:pb-4',
                ]
                : [
                    '[&>*:not(:first-child)]:pt-2 [&>*:not(:last-child)]:pb-2' => $spacing === 'sm' && (! $columns && $columns !== 0),
                    '[&>*:not(:first-child)]:pt-4 [&>*:not(:last-child)]:pb-4' => $spacing === 'lg' && (! $columns && $columns !== 0),
                    '[&>*:not(:first-child)]:pt-6 [&>*:not(:last-child)]:pb-6' => $spacing === 'md' && (! $columns && $columns !== 0),
                    '@lg:gap-x-4 @lg:gap-y-4 gap-2' => $spacing === 'sm' && $columns,
                    '@lg:gap-x-8 @lg:gap-y-8 gap-6' => $spacing === 'md' && $columns,
                    '@lg:gap-x-10 @lg:gap-y-10 gap-8' => $spacing === 'lg' && $columns,
                    '@3xl:grid-cols-2' => $columns > 1 && count($pages) >= 2,
                    '@8xl:grid-cols-3' => $columns > 2 && count($pages) >= 3,
                ],
            ])
        >
            @foreach ($pages as $item)
                <x-dynamic-component
                    :component="$componentItem"
                    :class="$widget->key . '-page-item'"
                    :$container
                    :$containerKey
                    :count="$withChildCount ? $item->children_count : null"
                    :icon="(bool) $widget->getMeta('icon')"
                    :image="$withImage ? $item->image : null"
                    :$loop
                    :parent="$withParent && method_exists($item, 'loadParent') ? $item->loadParent($language) : null"
                    :publish-date="$withDate ? $item->getPublishDate() : null"
                    :$size
                    :summary="$withSummary ? $item->translation->summary : null"
                    :title="$item->translation->title"
                    :url="$item->pageUrl->full_url"
                    :with-summary="$withSummary"
                />
            @endforeach
        </div>

        @if (method_exists($pages, 'total'))
            <x-capell::pagination :$results />
        @endif
    @endif
</x-capell-layout::widget.wrapper>

<?php
