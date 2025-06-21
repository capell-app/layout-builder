<?php

declare(strict_types=1);

?>

@php
    use Capell\Core\Enums\AssetComponentEnum;
    use Capell\Frontend\Facades\Frontend;
@endphp

@props([
    'columns' => $container['meta']['override_columns'] ?? ($widget->meta['columns'] ?? 3),
    'componentItem' => ($widget->meta['component_item'] ?? AssetComponentEnum::Card->value),
    'container',
    'containerKey',
    'hideContent' => $widgetData['meta']['hide_content'] ?? false,
    'index',
    'language' => Frontend::getLanguage(),
    'loop',
    'size' => $widget->meta['size'] ?? null,
    'spacing' => $widget->meta['spacing'] ?? 'lg',
    'widget',
    'withChildCount' => $widget->meta['with_child_count'] ?? ($widget->type->meta['with_child_count'] ?? false),
    'withImage' => $widget->meta['with_image'] ?? ($widget->type->meta['with_image'] ?? false),
    'withParent' => $widget->meta['with_parent'] ?? ($widget->type->meta['with_parent'] ?? false),
    'withPublished' => $widget->meta['with_published'] ?? ($widget->type->meta['with_published'] ?? false),
    'withSummary' => $widget->meta['with_summary'] ?? ($widget->type->meta['with_summary'] ?? false),
    'withTags' => $widget->meta['with_tags'] ?? ($widget->type->meta['with_tags'] ?? false),
])
<x-capell::widget.wrapper
    class="widget-pages"
    :$containerKey
    :$container
    :index="$loop->index"
    :$widget
>
    @if ($widget->translation && ! $hideContent)
        <x-capell::content
            class="mb-4"
            :compact="true"
            :$containerKey
            :content="$widget->translation->content"
            :contents="$widget->translation->content ? null : $widget->translation->contents"
            :text-align="$widget->meta['align'] ?? $widget->type->meta['align'] ?? null"
            :title="$widget->translation->title"
        />
    @endif

    @if (! $pages || $pages->isEmpty())
        <x-capell::no-results />
    @else
        <div
            @class([
                'grid',
                ...$containerKey === 'sidebar' && ! $columns
                ? [
                    'divide-y divide-gray-100 [&>*:not(:first-child)]:pt-4 [&>*:not(:last-child)]:pb-4',
                ]
                : [
                    '[&>*:not(:first-child)]:pt-2 [&>*:not(:last-child)]:pb-2' => $spacing === 'sm' && ! $columns,
                    '[&>*:not(:first-child)]:pt-4 [&>*:not(:last-child)]:pb-4' => $spacing === 'lg' && ! $columns,
                    '[&>*:not(:first-child)]:pt-6 [&>*:not(:last-child)]:pb-6' => $spacing === 'md' && ! $columns,
                    'gap-x-6 gap-y-10 lg:gap-x-10' => $columns,
                    '@3xl:grid-cols-2' => $columns > 1 && count($pages) >= 2,
                    '@8xl/wrap:grid-cols-3' => $columns > 2 && count($pages) >= 3,
                ],
            ])
        >
            @foreach ($pages as $item)
                <x-dynamic-component
                    :component="$componentItem"
                    :icon="$widget->meta['icon'] ?? false"
                    :$loop
                    :$container
                    :$containerKey
                    :image="$withImage ? $item->image : null"
                    :title="$item->translation->title"
                    :summary="$withSummary ? $item->translation->summary : null"
                    :tags="$withTags ? $item->tags : null"
                    :count="$withChildCount ? $item->children_count : null"
                    :parent="$withParent ? $item->loadParent($language) : null"
                    :publish-date="$withPublished ? $item->getPublishDate() : null"
                    :url="$item->pageUrl->full_url"
                    :size="$size"
                />
            @endforeach
        </div>

        @if (method_exists($pages, 'total'))
            <x-capell::pagination :$results />
        @endif
    @endif
</x-capell::widget.wrapper>

<?php
