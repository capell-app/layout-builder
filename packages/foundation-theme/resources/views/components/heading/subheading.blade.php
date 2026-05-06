<?php
use Capell\Frontend\Facades\Frontend;

$theme = Frontend::theme();
$page = Frontend::page();
$site = Frontend::site();
?>

@props([
    'color' => '',
    'tag' => 'div',
    'size' => null,
])

<{{ $tag }}
    {{
        $attributes->class([
            'subheading font-heading font-medium',
            'text-gray-500' => $color !== 'light',
            'dark:text-gray-200' => $color !== 'light' && $theme?->withDarkMode,
            ...match ($size) {
                'h1' => ['text-2xl lg:text-3xl'],
                'h2' => ['text-xl xl:text-2xl'],
                default => ['lg:text-md text-base'],
            },
        ])
    }}
>
    {{ $slot }}
</{{ $tag }}>
