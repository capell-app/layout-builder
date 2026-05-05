<?php

declare(strict_types=1);

return [
    'enabled' => false,
    'property_id' => null,
    'credentials_path' => null,
    'sync_days' => 30,
    'route_slug' => 'google-analytics',
    'tables' => [
        'sync_runs' => 'google_analytics_sync_runs',
        'daily_metrics' => 'google_analytics_daily_metrics',
        'page_metrics' => 'google_analytics_page_metrics',
    ],
];
