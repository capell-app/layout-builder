<?php

declare(strict_types=1);

use Capell\Frontend\Facades\Frontend;

$theme = Frontend::theme();

?>

@props([
'backgroundColor' => $widget->meta['background_color'] ?? null,
'container',
'containerKey',
'containerWidth' => null,
'content' => $widget->translation?->content,
'headingSize' => $widget->meta['heading_size'] ?? 'h2',
'loop',
'reverseOrder' => $widget->meta['reverse_order'] ?? null,
'rounded' => $theme->meta['rounded_images'] ?? false,
'size' => $widget->meta['size'] ?? null,
'title' => $widget->translation?->title,
'widget',
])

@php
    $backgroundImage = $widget->backgroundImage ?? $widget->image ?? $widget->assets->first()?->asset?->image;

            $hasContent = $content || $title || ! empty($widget->meta['actions']);

            if ($rounded) {
                $imgRounded = $hasContent
                      ? ($reverseOrder ? ' rounded-r-lg' : ' rounded-l-lg')
                      : ' rounded-lg';
            } else {
                $imgRounded = '';
            }
@endphp

<x-capell-layout::widget.wrapper
    class="widget-banner-full-width relative"
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :background-color="$backgroundColor"
    :$widget
    container-width="full"
>
    @if ($backgroundImage)
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
                :media="$backgroundImage"
                size="xxl"
                :rounded="false"
                :class="'h-auto w-full object-cover md:h-full' . $imgRounded"
            />
        </div>
    @endif

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
                            :content="$content"
                            :contents="$content ? null : $widget->translation?->content"
                            :heading-size="$headingSize"
                            :presenter="$widget->type->meta['content_presenter'] ?? null"
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
