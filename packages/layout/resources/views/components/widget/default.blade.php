<?php

declare(strict_types=1);

?>

@props([
'align' => $widget->meta['align'] ?? $widget->type->meta['align'] ?? null,
'headingSize' => $widget->meta['heading_size'] ?? 'h2',
'size' => $widget->meta['size'] ?? null,
'style' => $widget->meta['style'] ?? 'row',
'reverseOrder' => $widget->meta['reverse_order'] ?? null,
'title' => $widget->translation?->title,
'content' => $widget->translation?->content,
'container',
'loop',
'containerKey',
'containerWidth' => null,
'widget',
])

<x-capell-layout::widget.wrapper
    class="widget-default"
    :container-class="
        'flex flex-col gap-x-5 gap-y-3 lg:gap-x-10 '
        . (match ($style) {
            'row' => ($reverseOrder ? 'md:flex-row-reverse' : 'md:flex-row'),
            default => null,
        })
    "
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :$widget
>
    <div
        @class([
        '@container flex-1',
        'my-auto py-4' => $widget->image,
        ])
    >
        @if ($content || $title)
            <x-capell::content
                class="mb-2"
                :compact="true"
                :content="$content"
                :heading-size="$headingSize"
                :title="$title"
                :text-align="$align"
            />
        @endif

        @if (! empty($widget->meta['actions']))
            <x-capell::actions
                class="mt-4"
                :actions="$widget->meta['actions']"
                :align="$align"
            />
        @endif
    </div>

    @if ($widget->image)
        <div
            @class([
            match ($style) {
            'row' => 'flex-1 lg:max-w-[40%]',
            default => null,
            },
            ])
        >
            <x-capell::media
                :media="$widget->image"
                class="h-full w-full object-cover"
            />
        </div>
    @endif
</x-capell-layout::widget.wrapper>

<?php
