<?php

declare(strict_types=1);

return [
    'widget' => [
        // Whether to hide widgets that have no content to display
        // 'skip_render_empty' => false,
    ],
    'layout_builder' => [
        'lazy' => env('CAPELL_LAYOUT_BUILDER_LAZY', true),
    ],
];
