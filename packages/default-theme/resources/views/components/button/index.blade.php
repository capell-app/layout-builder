<?php
use Capell\Frontend\Facades\Frontend;

$site = Frontend::site();

?>

@props([
    'buttonIcon' => '',
    'class' => '',
    'color' => 'default',
    'color' => '',
    'icon' => '',
    'icon_color' => '',
    'icon_position' => '',
    'outline' => false,
    'size' => 'md',
    'target' => '',
    'title' => '',
    'url' => '',
    'weight' => '',
    'wireNavigation' => true,
])
@if (! $buttonIcon && $icon)
    @capellBuffer($buttonIcon)
        <x-dynamic-component
            :component="$icon"
            @class([
                'h-5 w-5' => $size === 'lg',
                'h-4 w-4' => $size !== 'sm' && $size !== 'lg',
                'h-3 w-3' => $size === 'sm',
                'stroke-current' => ! $icon_color,
                'stroke-secondary' => $icon_color === 'secondary',
                'stroke-primary' => $icon_color === 'primary',
            ])
        />
    @endcapellBuffer
@endif

@if ($url)
    <a
        href="{{ $url }}"
        title="{{ strip_tags($title ?? '') }}"
        {{
            $attributes->class([
                'capell-button inline-flex items-center justify-center rounded-md px-4 py-2 text-center font-semibold leading-tight !no-underline transition duration-300 focus:ring-2 lg:px-8 lg:py-3',
                'font-semibold' => ! $weight,
                'font-light' => $weight === 'light',
                'font-bold' => $weight === 'bold',
                $outline
                ? 'text-link hover:text-primary focus:text-primary border-2 border-white bg-transparent hover:bg-white focus:bg-white focus:outline-none dark:border-gray-600 dark:bg-transparent dark:text-gray-200 dark:hover:bg-gray-800'
                : 'text-link hover:text-primary focus:text-primary border-2 border-white bg-white hover:bg-gray-200 focus:bg-gray-200 focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800' => $color === 'default',
                $outline
                ? 'border-primary text-primary hover:bg-primary focus:bg-primary border-2 bg-transparent hover:text-white focus:text-white focus:outline-none'
                : 'hover:text-primary focus:text-primary focus:ring-primary border-primary bg-primary border-2 text-white hover:bg-white focus:bg-white focus:outline-none' => $color === 'primary',
                $outline
                ? 'border-secondary text-secondary hover:bg-secondary focus:bg-secondary border-2 bg-transparent hover:text-white focus:text-white focus:outline-none'
                : 'hover:text-secondary focus:text-secondary focus:ring-secondary border-secondary bg-secondary border-2 text-white hover:bg-white focus:bg-white focus:outline-none' => $color === 'secondary',
                $outline
                ? 'border-2 border-white bg-transparent text-white hover:bg-transparent focus:bg-transparent focus:outline-none focus:ring-transparent'
                : 'border-2 border-white bg-transparent text-white hover:bg-transparent focus:bg-transparent focus:outline-none focus:ring-transparent' => ! $color,
                'text-white' => ! $color && $color === 'light',
                'text-primary' => ! $color && $color !== 'light',
                $class,
            ])
        }}
        @if ($target) target="{{ $target }}" @endif
        @if ($wireNavigation) wire:navigate @endif
    >
        @if ($buttonIcon && $icon_position !== 'after')
            {{ $buttonIcon() }}
        @endif

        <span>{{ $slot }}</span>
        @if ($buttonIcon && $icon_position === 'after')
            {{ $buttonIcon() }}
        @endif
    </a>
@else
    <button
        title="{{ strip_tags($title ?? '') }}"
        {{
            $attributes->class([
                'capell-button inline-flex items-center justify-center rounded-md px-8 py-3 text-center font-semibold leading-tight transition duration-300',
                'font-semibold' => ! $weight,
                'font-light' => $weight === 'light',
                'font-bold' => $weight === 'bold',
                $outline
                ? 'text-link hover:text-primary focus:text-primary border-2 border-white bg-transparent hover:bg-white focus:bg-white focus:outline-none'
                : 'hover:text-primary focus:text-primary border-2 border-white bg-white hover:bg-gray-200 focus:bg-gray-200' => $color === 'default',
                $outline
                ? 'border-primary text-primary hover:bg-primary focus:bg-primary border-2 bg-transparent hover:text-white focus:text-white focus:outline-none'
                : 'hover:bg-primary focus:bg-primary border-primary bg-primary border-2 text-white' => $color === 'primary',
                $outline
                ? 'border-secondary text-secondary hover:bg-secondary focus:bg-secondary border-2 bg-transparent hover:text-white focus:text-white focus:outline-none'
                : 'hover:bg-secondary focus:bg-secondary border-secondary bg-secondary border-2 text-white' => $color === 'secondary',
                $class,
            ])
        }}
    >
        @if ($buttonIcon && $icon_position !== 'after')
            {{ $buttonIcon() }}
        @endif

        <span>{{ $slot }}</span>
        @if ($buttonIcon && $icon_position === 'after')
            {{ $buttonIcon() }}
        @endif
    </button>
@endif
