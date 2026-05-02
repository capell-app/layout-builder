<?php

declare(strict_types=1);

return [
    'enabled' => true,
    'show_header_search' => true,
    'driver' => env('CAPELL_SITE_SEARCH_DRIVER', 'database'),
    'route_path' => 'search',
    'results_per_page' => 10,
    'excerpt_length' => 200,
    'minimum_query_length' => 2,
    'record_search_logs' => true,
    'hash_visitor_data' => true,
    'database' => [
        'table' => 'pages',
        'columns' => ['title', 'excerpt', 'body'],
        'title_column' => 'title',
        'url_column' => 'slug',
        'excerpt_column' => 'excerpt',
        'body_column' => 'body',
        'type_column' => 'type',
    ],
    'scout' => [
        'model' => null,
        'url_column' => 'slug',
        'type_column' => 'type',
    ],
    'logs' => [
        'table_name' => 'site_search_logs',
        'retention_days' => 180,
    ],
    'dashboard' => [
        'default_days' => 30,
    ],
];
