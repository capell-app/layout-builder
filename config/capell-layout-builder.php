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
        'demo_path' => env('CAPELL_LAYOUT_BUILDER_DEMO_PATH'),

        'widget' => [
            'icon' => 'heroicon-o-puzzle-piece',
            'active_icon' => 'heroicon-s-puzzle-piece',
        ],
    ],

    'widget' => [
        'skip_render_empty' => env('CAPELL_LAYOUT_BUILDER_SKIP_RENDER_EMPTY_WIDGETS', true),
    ],

    'public_widget_snapshots' => [
        'ttl_seconds' => (int) env('CAPELL_WIDGET_SNAPSHOT_TTL', 86400),
        'stale_while_revalidate_seconds' => (int) env('CAPELL_WIDGET_SNAPSHOT_STALE_TTL', 3600),
    ],

    'bulk_change_retention_days' => 90,

    'default_widget' => 'capell.widget.default',
];
