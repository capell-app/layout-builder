<?php

declare(strict_types=1);

return [
    'enabled' => false,
    'property_id' => null,
    'credentials_path' => null,
    'sync_days' => 30,
    'route_slug' => 'ga4-reports',
    'tables' => [
        'sync_runs' => 'ga4_reports_sync_runs',
        'daily_metrics' => 'ga4_reports_daily_metrics',
        'page_metrics' => 'ga4_reports_page_metrics',
    ],
];
