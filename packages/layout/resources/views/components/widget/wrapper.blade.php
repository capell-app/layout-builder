<?php

declare(strict_types=1);

?>

@php
    use Capell\Frontend\Facades\Frontend;

    $theme = Frontend::getTheme();
@endphp

@props([
    'backgroundAttachment' => $widget->meta['background_attachment'] ?? '',
    'backgroundColor' => $widget->meta['background_color'] ?? '',
    'backgroundImage' => ! empty($widget->meta['background_image_id']) ? Capell\Core\Models\Media::find($widget->meta['background_image_id']) : null,
    'backgroundRepeat' => $widget->meta['background_repeat'] ?? 'no-repeat',
    'backgroundSize' => $widget->meta['background_size'] ?? '',
    'class' => '',
    'container',
    'containerKey',
    'containerClass' => '',
    'containerWidth' => $container['meta']['container'] ?? null,
    'defaultColors' => ['secondary', 'primary', 'gray', 'light-gray'],
    'index',
    'margin' => ! empty($widget->meta['margin']) ? (array) $widget->meta['margin'] : [],
    'padding' => ! empty($widget->meta['padding']) ? (array) $widget->meta['padding'] : [],
    'pageContainer' => $widget->meta['container'] ?? $theme->meta['container'] ?? null,
    'widget' => '',
])
@aware([
    'containerColspan' => null,
])
<div
    id="{{ $containerKey.'-'.$widget->key."-$index" }}"
    {{
        $attributes->class([
            '@container/wrap widget widget-'.$widget->key,
            $class => $class !== 'widget-'.$widget->key,
            $containerClass => $containerWidth === 'full',
            'w-full' => $containerColspan === 12,
            'py-4' => in_array('sm', $padding, true),
            'pt-4' => in_array('t-sm', $padding, true),
            'pb-4' => in_array('b-sm', $padding, true),
            'py-8' => in_array('md', $padding, true),
            'pt-8' => in_array('t-md', $padding, true),
            'pb-8' => in_array('b-md', $padding, true),
            'py-10 lg:py-16' => in_array('lg', $padding, true),
            'pt-10 lg:pt-16' => in_array('t-lg', $padding, true),
            'pb-10 lg:pb-16' => in_array('b-lg', $padding, true),
            'pt-20' => in_array('t-xl', $padding, true),
            'pb-20' => in_array('b-xl', $padding, true),
            'my-4' => in_array('sm', $margin, true),
            'mt-4' => in_array('t-sm', $margin, true),
            'mb-4' => in_array('b-sm', $margin, true),
            'my-6 lg:my-10' => in_array('md', $margin, true),
            'mt-6' => in_array('t-md', $margin, true),
            'mb-6' => in_array('b-md', $margin, true),
            'my-10' => in_array('lg', $margin, true),
            'mt-10' => in_array('t-lg', $margin, true),
            'mb-10' => in_array('b-lg', $margin, true),
            'm-20' => in_array('xl', $margin, true),
            'mt-20' => in_array('t-xl', $margin, true),
            'mb-20' => in_array('b-xl', $margin, true),
            'bg-primary' => $backgroundColor === 'primary',
            'bg-secondary' => $backgroundColor === 'secondary',
            'bg-gray-600' => $backgroundColor === 'gray',
            'bg-gray-100' => $backgroundColor === 'light-gray',
            'dark:bg-gray-600' => $backgroundColor === 'light-gray' && $theme->withDarkMode,
            'bg-cover' => $backgroundSize === 'cover' && $backgroundImage,
            'bg-contain' => $backgroundSize === 'contain' && $backgroundImage,
            'bg-repeat' => $backgroundRepeat === 'repeat' && $backgroundImage,
            'bg-repeat-x' => $backgroundRepeat === 'repeat-x' && $backgroundImage,
            'bg-repeat-y' => $backgroundRepeat === 'repeat-y' && $backgroundImage,
            'bg-no-repeat' => $backgroundRepeat === 'no-repeat' && $backgroundImage,
            'bg-fixed' => $backgroundAttachment === 'fixed' && $backgroundImage,
            'bg-scroll' => $backgroundAttachment === 'scroll' && $backgroundImage,
        ])
    }}
    @if ($backgroundColor && ! in_array($backgroundColor, $defaultColors, true) || $backgroundImage)
        style="{{ $backgroundColor && ! in_array($backgroundColor, $defaultColors, true) ? 'background-color:'.$backgroundColor.';' : '' }}{{ $backgroundImage ? 'background-image:url('.$backgroundImage->url.');' : '' }}"
    @endif
>
    @if ($containerWidth !== 'full')
        <div
            @class([
                match ($pageContainer) {
                    'sm' => 'sm:container',
                    'md' => 'md:container',
                    'lg' => 'lg:container',
                    'full' => '',
                    default => 'container',
                },
                $containerClass ?: '' => $containerClass,
            ])
        >
            {{ $slot }}
        </div>
    @else
        {{ $slot }}
    @endif
</div>

<?php
