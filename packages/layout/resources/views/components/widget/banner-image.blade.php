<?php

declare(strict_types=1);

?>

@props([
    'backgroundColor' => $widget->getMeta('background_color'),
    'container',
    'containerKey',
    'containerWidth' => null,
    'content' => $widget->translation?->content,
    'headingSize' => $widget->getMeta('heading_size', 'h2'),
    'loop',
    'reverseOrder' => $widget->getMeta('reverse_order'),
    'rounded' => (bool) $widget->getMeta('rounded_images'),
    'size' => $widget->getMeta('size'),
    'title' => $widget->translation?->title,
    'widget',
])
{{-- format-ignore-start --}}
@php
    use Capell\Core\Enums\MediaCollectionEnum;
    use Capell\Frontend\Facades\Frontend;use Capell\Layout\Enums\ContainerWidthEnum;

    $theme = Frontend::theme();

    /**
    * @var \Capell\Layout\Models\Widget $widget
    */
    $backgroundImage = $widget->getMedia(MediaCollectionEnum::BackgroundImage->value)->first()
      ?? $widget->getMedia(MediaCollectionEnum::Image->value)->first()
      ?? $widget->assets->first()?->media?->first();

    $actions = $widget->getMeta('actions');

    $hasContent = $content || $title || $actions;

    if ($rounded) {
        $imgRounded = $hasContent
              ? ($reverseOrder ? ' rounded-r-lg' : ' rounded-l-lg')
              : ' rounded-lg';
    } else {
        $imgRounded = '';
    }
@endphp
{{-- format-ignore-end --}}

<x-capell-layout::widget.wrapper
    class="widget-banner-image relative"
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :background-color="$backgroundColor"
    :$widget
    :container-width="ContainerWidthEnum::Full"
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
                            :content-type="$widget->type->content_structure"
                            :divider="$widget->getMeta('content_divider')"
                            :heading-size="$headingSize"
                            :title="$title"
                            :text-align="$widget->getMeta('align')"
                            :heading-style="$widget->getMeta('heading_style')"
                        />
                    @endif

                    @if ($actions)
                        <x-capell-layout::actions
                            class="mt-4"
                            :$actions
                        />
                    @endif
                </div>
            </div>
        </div>
    @endif
</x-capell-layout::widget.wrapper>
