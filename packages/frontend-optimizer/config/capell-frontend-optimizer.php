<?php

declare(strict_types=1);

use Capell\FrontendOptimizer\Enums\OptimizationScope;
use Illuminate\Support\Env;

return [
    'enabled' => true,

    'scope' => OptimizationScope::Layout->value,

    'paths' => [
        'manifests' => 'capell/frontend-optimizer/manifests',
        'critical_css' => 'capell/frontend-optimizer/critical-css',
    ],

    'playwright' => [
        'node_binary' => Env::get('CAPELL_FRONTEND_OPTIMIZER_NODE', 'node'),
        'script' => __DIR__ . '/../resources/js/generate-critical-css.mjs',
        'timeout' => 120,
        'viewports' => [
            ['width' => 390, 'height' => 844],
            ['width' => 1440, 'height' => 900],
        ],
    ],
];
