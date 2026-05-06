<?php

declare(strict_types=1);

return [
    'routes' => [
        // Set any route to null to stop this package registering it in the host app.
        'home' => null,
        'knowledge' => null,
        'site' => 'agent-bridge/capell',
    ],

    'site_auth_guard' => env('CAPELL_Agent Bridge_AUTH_GUARD', 'web'),

    'token_prefix' => env('CAPELL_Agent Bridge_TOKEN_PREFIX', 'cagent-bridge_'),

    'confirmation_ttl_minutes' => env('CAPELL_Agent Bridge_CONFIRMATION_TTL_MINUTES', 10),

    'public_docs_paths' => [
        base_path('README.md'),
        base_path('docs'),
        base_path('packages/*/README.md'),
        base_path('packages/*/docs'),
    ],
];
