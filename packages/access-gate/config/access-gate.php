<?php

declare(strict_types=1);

return [
    'connection' => env('ACCESS_GATE_DB_CONNECTION'),

    'route_prefix' => env('ACCESS_GATE_ROUTE_PREFIX', 'access'),

    'cookies' => [
        'browser_token' => [
            'name' => env('ACCESS_GATE_BROWSER_TOKEN_COOKIE', 'capell_access_gate_browser_token'),
            'ttl_minutes' => (int) env('ACCESS_GATE_BROWSER_TOKEN_TTL', 60 * 24 * 180),
            'path' => '/',
            'domain' => env('ACCESS_GATE_COOKIE_DOMAIN'),
            'secure' => env('ACCESS_GATE_COOKIE_SECURE'),
            'http_only' => true,
            'same_site' => env('ACCESS_GATE_COOKIE_SAME_SITE', 'lax'),
        ],
    ],

    'middleware' => [
        'aliases' => [
            'access_gate.area' => null,
            'access_gate.browser_token' => null,
        ],
        'default' => [
            'web',
        ],
    ],

    'registration' => [
        'fields' => [
            //
        ],
    ],

    'install' => [
        'default_area' => [
            'key' => env('ACCESS_GATE_DEFAULT_AREA_KEY', 'capell-preview'),
            'name' => env('ACCESS_GATE_DEFAULT_AREA_NAME', 'Capell Preview'),
            'status' => env('ACCESS_GATE_DEFAULT_AREA_STATUS', 'active'),
            'identity_mode' => env('ACCESS_GATE_DEFAULT_IDENTITY_MODE', 'hybrid'),
            'approval_strategy' => env('ACCESS_GATE_DEFAULT_APPROVAL_STRATEGY', 'first_n_auto_approve'),
            'approval_limit' => env('ACCESS_GATE_DEFAULT_APPROVAL_LIMIT'),
            'grant_duration_days' => env('ACCESS_GATE_DEFAULT_GRANT_DURATION_DAYS'),
            'registration_policy' => env('ACCESS_GATE_DEFAULT_REGISTRATION_POLICY', 'single_per_email'),
            'token_policy' => env('ACCESS_GATE_DEFAULT_TOKEN_POLICY', 'single_active_browser_token'),
        ],
    ],
];
