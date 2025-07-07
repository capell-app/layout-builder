<?php

declare(strict_types=1);

?>

@php
    use Capell\Core\Enums\DefaultColorEnum;
    use Capell\Frontend\Facades\Frontend;

    $theme = Frontend::getTheme();
@endphp

@props([
    'backgroundAttachment' => $widget->meta['background_attachment'] ?? '',
    'backgroundColor' => $widget->meta['background_color'] ?? '',
    'backgroundImage' => ! empty($widget->meta['background_image_id']) ? Capell\Core\Models\Media::find($widget->meta['background_image_id']) : null,
    'backgroundRepeat' => $widget->meta['background_repeat'] ?? 'no-repeat',
    'backgroundOverlay' => $widget->meta['background_overlay'] ?? false,
    'backgroundPosition' => $widget->meta['background_position'] ?? 'center',
    'backgroundSize' => $widget->meta['background_size'] ?? 'cover',
    'class' => '',
    'container',
    'containerKey',
    'containerClass' => '',
    'containerWidth' => $container['meta']['container'] ?? null,
    'index',
    'margin' => ! empty($widget->meta['margin']) ? (array) $widget->meta['margin'] : [],
    'padding' => ! empty($widget->meta['padding']) ? (array) $widget->meta['padding'] : [],
    'pageContainer' => $widget->meta['container'] ?? $theme->meta['container'] ?? null,
    'widget',
])
@php
    $isDefaultColor = in_array($backgroundColor, DefaultColorEnum::cases(), true);
@endphp

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
            'bg-base' => $backgroundColor === DefaultColorEnum::Base->value,
            'bg-black' => $backgroundColor === DefaultColorEnum::Black->value,
            'bg-dark-gray' => $backgroundColor === DefaultColorEnum::DarkGray->value,
            'bg-danger' => $backgroundColor === DefaultColorEnum::Danger->value,
            'bg-info' => $backgroundColor === DefaultColorEnum::Info->value,
            'bg-light-gray' => $backgroundColor === DefaultColorEnum::LightGray->value,
            'bg-primary' => $backgroundColor === DefaultColorEnum::Primary->value,
            'bg-secondary' => $backgroundColor === DefaultColorEnum::Secondary->value,
            'bg-success' => $backgroundColor === DefaultColorEnum::Success->value,
            'bg-warning' => $backgroundColor === DefaultColorEnum::Warning->value,
            'bg-white' => $backgroundColor === DefaultColorEnum::White->value,
            'dark:bg-gray-600' => $backgroundColor === DefaultColorEnum::LightGray->value && $theme->withDarkMode,
            'bg-center' => $backgroundPosition === 'center' && $backgroundImage,
            'bg-top' => $backgroundPosition === 'top' && $backgroundImage,
            'bg-bottom' => $backgroundPosition === 'bottom' && $backgroundImage,
            'bg-left' => $backgroundPosition === 'left' && $backgroundImage,
            'bg-right' => $backgroundPosition === 'right' && $backgroundImage,
            'bg-cover' => $backgroundSize === 'cover' && $backgroundImage,
            'bg-contain' => $backgroundSize === 'contain' && $backgroundImage,
            'bg-repeat' => $backgroundRepeat === 'repeat' && $backgroundImage,
            'bg-repeat-x' => $backgroundRepeat === 'repeat-x' && $backgroundImage,
            'bg-repeat-y' => $backgroundRepeat === 'repeat-y' && $backgroundImage,
            'bg-no-repeat' => $backgroundRepeat === 'no-repeat' && $backgroundImage,
            'bg-fixed' => $backgroundAttachment === 'fixed' && $backgroundImage,
            'bg-scroll' => $backgroundAttachment === 'scroll' && $backgroundImage,
            'relative overflow-hidden' => $backgroundOverlay,
        ])
    }}
    @if ($backgroundColor && ! $isDefaultColor || $backgroundImage)
        style="{{ $backgroundColor && ! $isDefaultColor ? 'background-color:'.$backgroundColor.';' : '' }}{{ $backgroundImage ? 'background-image:url('.$backgroundImage->url.');' : '' }}"
    @endif
>
    @if ($backgroundOverlay)
        <div
            class="absolute inset-0 z-0 bg-black/40 shadow-[inset_0_0_8rem_4rem_rgba(0,0,0,0.7)]"
        ></div>
    @endif

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
