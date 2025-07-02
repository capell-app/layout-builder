<?php

declare(strict_types=1);

?>

@php
    use Capell\Core\Enums\AssetComponentEnum;
    use Capell\Core\Facades\CapellCore;
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
<x-capell-layout::widget.wrapper
    class="widget-assets widget-assets-features spacing-y-6"
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

    features
</x-capell-layout::widget.wrapper>

<?php
