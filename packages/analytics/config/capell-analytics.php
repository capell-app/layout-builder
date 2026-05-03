<?php

declare(strict_types=1);

return [
    'enabled' => true,
    'route_prefix' => 'capell/analytics',
    'track_page_views' => true,
    'track_clicks' => true,
    'track_forms' => false,
    'automatic_click_tracking' => true,
    'require_consent_for_all_regions' => false,
    'default_consent_region' => null,
    'policy_version' => '1.0',
    'retention_days' => 365,
    'hash_visitor_data' => true,
    'hash_salt' => 'capell-analytics',
    'ignored_paths' => [
        '/admin*',
        '/livewire*',
        '/capell/analytics*',
        '/_debugbar*',
        '/_clockwork*',
        '/storage*',
    ],
    'ignored_selectors' => [
        '[data-capell-analytics-ignore]',
        '[wire\\:click]',
    ],
    'tables' => [
        'visits' => 'analytics_visits',
        'consents' => 'analytics_consents',
        'events' => 'analytics_events',
    ],
];
