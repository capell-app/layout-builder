<?php

declare(strict_types=1);

?>

@props([
    'tag' => 'div',
    'size' => $tag !== 'div' ? $tag : null,
])
<{{ $tag }}
    {{
        $attributes->class([
            'heading font-heading text-balance font-semibold',
            'text-xl 2xl:text-2xl' => ! $size,
            'prose-h1' => $size === 'h1',
            'prose-h2' => $size === 'h2',
            'prose-h3' => $size === 'h3',
            'prose-h4' => $size === 'h4',
        ])
    }}
>
    {{ $slot }}
</{{ $tag }}>

<?php
