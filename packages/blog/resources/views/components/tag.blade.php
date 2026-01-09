<?php

declare(strict_types=1);

use Capell\Frontend\Facades\Frontend;

$language = Frontend::language();
$site = Frontend::site();
$theme = Frontend::theme();
?>

@props([
    'count' => null,
    'colorScheme' => 'light',
    'size' => 'sm',
    'url',
])
<a
    href="{{ $url }}"
    {{
        $attributes->class([
            'tag-item hover:bg-primary hover:text-primary focus:bg-primary inline-flex items-center gap-1 rounded no-underline hover:text-white focus:text-white',
            'bg-gray-600/75 text-gray-100' => $colorScheme === 'dark',
            'bg-gray-100 text-gray-600' => $colorScheme === 'light',
            'dark:bg-white/10 dark:text-gray-200' => $colorScheme === 'light' && $theme->withDarkMode,
            'px-2 py-1 text-xs' => $size === 'xs',
            'px-2 py-1.5 text-sm' => $size === 'sm',
            'px-3 py-2 text-base' => $size === 'md',
        ])
    }}
>
    <span class="font-medium tracking-tight">
        {{ $slot }}
    </span>
    @if ($count->hasActualContent())
        <span>{{ $count }}</span>
    @endif
</a>

<?php
