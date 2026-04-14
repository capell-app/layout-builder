<?php

declare(strict_types=1);

use Capell\Frontend\Facades\Frontend;

$theme = Frontend::theme();

?>

@props([
    'align' => $widget->getMeta('align'),
    'headingSize' => $widget->getMeta('heading_size', 'h2'),
    'size' => $widget->getMeta('size'),
    'style' => $widget->getMeta('style', 'row'),
    'reverseOrder' => $widget->getMeta('reverse_order'),
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
                class="widget-content mb-2"
                :compact="true"
                :content="$content"
                :content-type="$widget->type->content_structure"
                :divider="$widget->getMeta('content_divider')"
                :heading-size="$headingSize"
                :muted="in_array($containerKey, $theme->secondary_containers)"
                :heading-style="$widget->getMeta('heading_style')"
                :title="$title"
                :text-align="$align"
            />
        @endif

        @if ($widget->getMeta('actions'))
            <x-capell-layout::actions
                class="mt-4"
                :actions="$widget->getMeta('actions')"
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
