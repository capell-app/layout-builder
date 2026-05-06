<?php

declare(strict_types=1);

return [
    'enabled' => true,
    'route_prefix' => 'capell/insights',
    'track_page_views' => true,
    'track_clicks' => true,
    'track_form-builder' => false,
    'automatic_click_tracking' => true,
    'require_consent_for_all_regions' => false,
    'default_consent_region' => null,
    'policy_version' => '1.0',
    'retention_days' => 365,
    'hash_visitor_data' => true,
    'hash_salt' => 'capell-insights',
    'ignored_paths' => [
        '/admin*',
        '/livewire*',
        '/capell/insights*',
        '/_debugbar*',
        '/_clockwork*',
        '/storage*',
    ],
    'ignored_selectors' => [
        '[data-capell-insights-ignore]',
        '[wire\\:click]',
    ],
    'tables' => [
        'visits' => 'insights_visits',
        'consents' => 'insights_consents',
        'events' => 'insights_events',
    ],
];
