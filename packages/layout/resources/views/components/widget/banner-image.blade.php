<?php

declare(strict_types=1);

?>

@props([
    'headingSize' => $widget->meta['heading_size'] ?? 'h2',
    'backgroundColor' => $widget->meta['background_color'] ?? null,
    'size' => $widget->meta['size'] ?? null,
    'reverseOrder' => $widget->meta['reverse_order'] ?? null,
    'title' => $widget->translation?->title,
    'content' => $widget->translation?->content,
    'container',
    'loop',
    'containerKey',
    'widget',
])

@php
    $hasImage = $widget->meta['image_id'] ?? false && $widget->image;

    if (! $hasImage) {
        throw new Exception('Widget image is required for banner full width widget.');
    }

    $hasContent = $content || $title || ! empty($widget->meta['actions']);
@endphp

<x-capell-layout::widget.wrapper
    class="widget-banner-full-width relative"
    :$container
    :$containerKey
    :index="$loop->index"
    :background-color="$backgroundColor"
    :$widget
    container-width="full"
>
    <div
        @class([
            'w-full',
            'md:w-1/2' => $hasContent,
            'md:absolute' => $hasContent,
            'md:inset-y-0' => $hasContent,
            'md:left-0' => $hasContent && $reverseOrder,
            'md:right-0' => $hasContent && ! $reverseOrder,
        ])
    >
        <x-capell::media
            :$containerKey
            :media="$widget->image"
            size="xxl"
            class="h-auto w-full object-cover md:h-full"
        />
    </div>

    @if ($hasContent)
        <div
            @class([
                'container',
                'absolute inset-0 flex items-end', // Overlay on mobile, align to bottom
                'z-10',
                'md:relative md:flex md:flex-col md:items-center',
                'gap-y-6',
                'gap-x-6',
                'py-10',
                'md:flex-row-reverse' => $reverseOrder,
                'md:flex-row' => ! $reverseOrder,
            ])
        >
            <div
                @class([
                    'w-full md:w-1/2',
                    'md:pl-10' => $reverseOrder,
                    'md:pr-10' => ! $reverseOrder,
                ])
            >
                <div
                    @class(['rounded bg-white/80 p-6 backdrop-blur' => $backgroundColor])
                >
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
            </div>
        </div>
    @endif
</x-capell-layout::widget.wrapper>
