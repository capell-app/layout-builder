<?php

declare(strict_types=1);

use Capell\Frontend\Facades\Frontend;

$language = Frontend::language();
$site = Frontend::site();
$theme = Frontend::theme();
?>

@props([
    'count' => null,
    'color' => 'light',
    'size' => 'sm',
    'url',
])
<a
    href="{{ $url }}"
    {{
        $attributes->class([
            'tag-item hover:bg-primary hover:text-primary focus:bg-primary text-balance rounded no-underline hover:text-white focus:text-white',
            'bg-gray-600/75 text-gray-100' => $color === 'dark',
            'bg-gray-100 text-gray-600' => $color === 'light',
            'dark:bg-white/10 dark:text-gray-200' => $color === 'light' && $theme->withDarkMode,
            'px-2 py-1 text-xs font-medium' => $size === 'xs',
            'px-2 py-1.5 text-sm' => $size === 'sm',
            'px-3 py-2 text-base' => $size === 'md',
        ])
    }}
>
    <span class="font-medium tracking-tight">
        {{ $slot }}
    </span>
    @if ($count?->hasActualContent())
        <span class="ml-1">{{ $count }}</span>
    @endif
</a>

<?php
