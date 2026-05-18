<?php

declare(strict_types=1);

return [
    'editor_mode' => [
        'default' => env('CAPELL_LAYOUT_BUILDER_DEFAULT_EDITOR_MODE', 'content_first'),
        'allowed' => ['content_first', 'layout_first'],
    ],

    'layout_builder' => [
        'lazy' => env('CAPELL_LAYOUT_BUILDER_LAZY', true),
    ],

    'preview' => [
        'match_frontend_container_layout' => env('CAPELL_LAYOUT_BUILDER_PREVIEW_MATCH_FRONTEND_CONTAINER_LAYOUT', true),
    ],

    'resources' => [
        'block' => [
            'icon' => 'heroicon-o-squares-2x2',
            'active_icon' => 'heroicon-s-squares-2x2',
        ],
    ],

    'block' => [
        'skip_render_empty' => env('CAPELL_LAYOUT_BUILDER_SKIP_RENDER_EMPTY_WIDGETS', true),
    ],

    'default_block' => 'capell.block.default',
];
