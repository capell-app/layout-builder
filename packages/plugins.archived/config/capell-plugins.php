<?php

declare(strict_types=1);

return [
    'enabled' => env('CAPELL_PLUGINS_ENABLED', false),

    'anystack' => [
        'base_url' => env('CAPELL_ANYSTACK_BASE_URL', 'https://api.anystack.sh'),
        'api_key' => env('CAPELL_ANYSTACK_API_KEY'),
        'composer_contact_email' => env('CAPELL_ANYSTACK_CONTACT_EMAIL', 'unlock'),
        'timeout_seconds' => env('CAPELL_ANYSTACK_TIMEOUT', 10),
    ],

    'composer' => [
        'binary' => env('CAPELL_COMPOSER_BINARY', 'composer'),
        'timeout_seconds' => env('CAPELL_COMPOSER_TIMEOUT', 600),
    ],

    'license_heartbeat' => [
        'cache_ttl_hours' => env('CAPELL_PLUGINS_HEARTBEAT_TTL', 24),
        'offline_grace_days' => env('CAPELL_PLUGINS_OFFLINE_GRACE_DAYS', 14),
    ],
];
