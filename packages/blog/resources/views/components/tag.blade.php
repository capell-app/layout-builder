<?php

declare(strict_types=1);

use Capell\Frontend\Facades\Frontend;

$language = Frontend::language();
$site = Frontend::site();
$theme = Frontend::theme();
?>

@props([
'colorScheme' => 'light',
'size' => 'sm',
'url',
])
<a
    href="{{ $url }}"
    {{
        $attributes->class([
        'tag-item hover:bg-primary hover:text-primary focus:bg-primary inline-flex items-center rounded font-medium tracking-tight no-underline hover:text-white focus:text-white',
        'bg-gray-600/75 text-gray-100' => $colorScheme === 'dark',
        'bg-gray-100 text-gray-600' => $colorScheme === 'light',
        'dark:bg-white/10 dark:text-gray-200' => $colorScheme === 'light' && $theme->withDarkMode,
        'px-2 py-1 text-xs' => $size === 'sm',
        'px-3 py-1.5 text-base' => $size === 'md',
        ])
    }}
>
    {{ $slot }}
</a>

<?php
