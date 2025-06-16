<?php

declare(strict_types=1);

?>

@php
    use Capell\Core\Enums\ResourceComponentEnum;
@endphp

@props([
    'colorScheme' => $widget->meta['color_scheme'] ?? 'dark',
    'container',
    'containerKey',
    'loop',
    'total' => $widget->assets->isNotEmpty() ? $widget->assets->count() : 1,
    'widget',
    'widgetIndex',
    'withChildCount' => $widget->meta['with_child_count'] ?? ($widget->type->meta['with_child_count'] ?? false),
    'withImage' => $widget->meta['with_image'] ?? ($widget->type->meta['with_image'] ?? true),
    'withParent' => $widget->meta['with_parent'] ?? ($widget->type->meta['with_parent'] ?? false),
    'withPublished' => $widget->meta['with_published'] ?? ($widget->type->meta['with_published'] ?? true),
    'withSummary' => $widget->meta['with_summary'] ?? ($widget->type->meta['with_summary'] ?? true),
    'withTags' => $widget->meta['with_tags'] ?? ($widget->type->meta['with_tags'] ?? true),
])
<x-capell::widget.wrapper
    class="widget-assets widget-assets-grid"
    :$container
    :$containerKey
    :index="$loop->index"
    :$widget
>
    @if ($widget->translation)
        <x-capell::content
            :compact="true"
            :$containerKey
            :content="$widget->translation->content"
            :contents="$widget->translation->content ? null : $widget->translation->contents"
            :color-scheme="$colorScheme"
            :title="$widget->translation->title"
            :text-align="$widget->meta['align'] ?? $widget->type->meta['align'] ?? null"
            class="mb-4"
        />
    @endif

    @if ($widget->assets->isNotEmpty())
        <div
            @class([
                'grid gap-x-8 gap-y-6 lg:gap-x-10 lg:gap-y-7',
                'md:grid-cols-2' => $total >= 2,
                'lg:grid-cols-3' => $total >= 3,
                '2xl:grid-cols-4' => $total > 7,
            ])
        >
            @foreach ($widget->assets as $resource)
                <x-dynamic-component
                    :component="ResourceComponentEnum::fromResource($resource['asset_type'])->value"
                    :component-item="$widget->meta['component_item'] ?? ResourceComponentEnum::Card->value"
                    :$container
                    :$containerKey
                    :resource="$resource->asset"
                    :with-child-count="$withChildCount"
                    :with-image="$withImage"
                    :with-parent="$withParent"
                    :with-published="$withPublished"
                    :with-summary="$withSummary"
                    :with-tags="$withTags"
                    :$loop
                />
            @endforeach
        </div>
    @endif
</x-capell::widget.wrapper>

<?php
