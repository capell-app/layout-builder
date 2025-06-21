<?php

declare(strict_types=1);

?>

@props([
    'headingSize' => $widget->meta['heading_size'] ?? 'h2',
    'size' => $widget->meta['size'] ?? null,
    'title' => $widget->translation?->title,
    'content' => $widget->translation?->content,
    'container',
    'loop',
    'containerKey',
    'widget',
])

@php
    $hasImage = $widget->meta['image_id'] ?? false && $widget->image;
@endphp

<x-capell::widget.wrapper
    class="widget-default"
    container-class="flex flex-col gap-x-5 gap-y-3 lg:flex-row lg:items-center"
    :$container
    :$containerKey
    :index="$loop->index"
    :$widget
>
    <div class="@container flex-1">
        @if ($content || $title)
            <x-capell::content
                class="mb-2"
                :compact="true"
                :$containerKey
                :content="$content"
                :contents="$content ? null : $widget->translation?->contents"
                :heading-size="$headingSize"
                :title="$title"
                :text-align="$widget->meta['align'] ?? $widget->type->meta['align'] ?? null"
            />
        @endif

        @if (! empty($widget->meta['actions']))
            <x-capell::actions
                class="mt-4"
                :actions="$widget->meta['actions']"
            />
        @endif
    </div>

    @if ($hasImage)
        <div class="flex-1">
            <x-capell::media
                :$containerKey
                :media="$widget->image"
                class="w-full"
            />
        </div>
    @endif
</x-capell::widget.wrapper>

<?php
