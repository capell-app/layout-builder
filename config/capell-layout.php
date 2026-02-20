<?php

declare(strict_types=1);

use Capell\Layout\Models\Content;
use Filament\Support\Icons\Heroicon;

return [
    'assets' => [
        'content' => [
            'icon' => Heroicon::OutlinedRectangleStack,
            'model' => Content::class,
        ],
    ],
    'widget' => [
        // Whether to hide widgets that have no content to display
        // 'skip_render_empty' => false,
    ],
    'layout_builder' => [
        'lazy' => env('CAPELL_LAYOUT_BUILDER_LAZY', true),
    ],
];
