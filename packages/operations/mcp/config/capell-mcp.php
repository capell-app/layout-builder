<?php

declare(strict_types=1);

return [
    'routes' => [
        // Set any route to null to stop this package registering it in the host app.
        'home' => '/',
        'knowledge' => 'mcp/capell/knowledge',
        'site' => 'mcp/capell/site',
    ],

    'site_auth_guard' => env('CAPELL_MCP_AUTH_GUARD', 'web'),

    'token_prefix' => env('CAPELL_MCP_TOKEN_PREFIX', 'cmcp_'),

    'confirmation_ttl_minutes' => (int) env('CAPELL_MCP_CONFIRMATION_TTL_MINUTES', 10),

    'public_docs_paths' => [
        base_path('README.md'),
        base_path('docs'),
        base_path('packages/*/*/README.md'),
        base_path('packages/*/*/docs'),
        base_path('packages/*/themes/*/README.md'),
    ],
];
