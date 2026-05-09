<?php

declare(strict_types=1);

return [
    'routes' => [
        // Set any route to null to stop this package registering it in the host app.
        'home' => null,
        'knowledge' => null,
        'site' => 'agent-bridge/capell',
    ],

    'site_auth_guard' => env('CAPELL_AGENT_BRIDGE_AUTH_GUARD', 'web'),

    'token_prefix' => env('CAPELL_AGENT_BRIDGE_TOKEN_PREFIX', 'cagent-bridge_'),

    'confirmation_ttl_minutes' => env('CAPELL_AGENT_BRIDGE_CONFIRMATION_TTL_MINUTES', 10),

    'enable_user_resource_bridge' => true,

    'public_docs_paths' => [
        base_path('README.md'),
        base_path('docs'),
        base_path('packages/*/README.md'),
        base_path('packages/*/docs'),
    ],
];
