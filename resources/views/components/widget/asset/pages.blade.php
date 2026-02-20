<?php

declare(strict_types=1);

use Capell\Frontend\Facades\Frontend;

$language = Frontend::language();
$theme = Frontend::theme();
?>

@props([
    'columns' => $container['meta']['override_columns'] ?? ($widget->meta['columns'] ?? 3),
    'componentItem' => ($widget->meta['component_item'] ?? \Capell\Core\Enums\AssetComponentEnum::Card->value),
    'container',
    'containerKey',
    'containerWidth' => null,
    'showPageContent' => $widgetData['meta']['show_page_content'] ?? false,
    'showPageTitle' => $widgetData['meta']['show_page_title'] ?? false,
    'index',
    'loop',
    'size' => $widget->meta['size'] ?? ($containerKey === 'sidebar' ? 'sm' : null),
    'spacing' => $widget->meta['spacing'] ?? ($containerKey === 'sidebar' ? 'md' : 'lg'),
    'widget',
    'withChildCount' => $widget->meta['with_child_count'] ?? ($widget->type->meta['with_child_count'] ?? false),
    'withImage' => $widget->meta['with_image'] ?? ($widget->type->meta['with_image'] ?? false),
    'withParent' => $widget->meta['with_parent'] ?? ($widget->type->meta['with_parent'] ?? false),
    'withDate' => $widget->meta['with_date'] ?? ($widget->type->meta['with_date'] ?? false),
    'withSummary' => $widget->meta['with_summary'] ?? ($widget->type->meta['with_summary'] ?? false),
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
        $showTitle = empty($widget->meta['container_options'][$containerKey]['hide_title'])
            && ($widget->translation?->title || ($showPageTitle && $page->translation->title));
        $showContent = empty($widget->meta['container_options'][$containerKey]['hide_content'])
            && ($widget->translation?->content || ($showPageContent && $page->translation->content));
    @endphp

    @if ($showTitle || $showContent)
        <x-capell::content
            :compact="true"
            :content="$showContent ? ($widget->translation->content ?: ($showPageContent ? $page->translation->content : null)) : null"
            :content-type="$widget->translation->content ? $widget->type->content_structure : ($showPageContent ? $page->type->content_structure : null)"
            :muted="in_array($containerKey, $theme->secondary_containers)"
            :text-align="$widget->meta['align'] ?? $widget->type->meta['align'] ?? null"
            :title="$showTitle ? ($widget->translation->title ?: ($showPageTitle ? $page->translation->title : null)) : null"
            :heading-style="($widget->meta['heading_style'] ?? null) ?: ($widget->type->meta['heading_style'] ?? null)"
            :heading-tag="$showPageTitle ? 'h1' : null"
        />
    @endif

    @if (! $pages || $pages->isEmpty())
        <x-capell::no-results>
            {!! isset($widget->translation->meta['no_results']) && $widget->translation->meta['no_results'] !== '' ? $widget->translation->meta['no_results'] : __('capell-layout::messages.no_pages_found') !!}
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
                    :$container
                    :$containerKey
                    :$loop
                    :count="$withChildCount ? $item->children_count : null"
                    :icon="$widget->meta['icon'] ?? false"
                    :image="$withImage ? $item->image : null"
                    :parent="$withParent ? $item->loadParent($language) : null"
                    :publish-date="$withDate ? $item->getPublishDate() : null"
                    :$size
                    :summary="$withSummary ? $item->translation->summary : null"
                    :title="$item->translation->title"
                    :url="$item->pageUrl->full_url"
                    :with-summary="$withSummary"
                    :class="$widget->key . '-page-item'"
                />
            @endforeach
        </div>

        @if (method_exists($pages, 'total'))
            <x-capell::pagination :$results />
        @endif
    @endif
</x-capell-layout::widget.wrapper>

<?php
