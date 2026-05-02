<?php

declare(strict_types=1);

return [
    'fieldset' => 'Site search',
    'enabled' => 'Enable site search',
    'enabled_helper' => 'Allow visitors to search published site content.',
    'show_header_search' => 'Show search in the header',
    'show_header_search_helper' => 'Inject the compact search form into the frontend header render hook.',
    'results_per_page' => 'Results per page',
    'driver' => 'Search driver',
    'driver_options' => [
        'database' => 'Database',
        'scout' => 'Scout',
    ],
    'record_search_logs' => 'Record search logs',
    'record_search_logs_helper' => 'Store search terms and result counts for analytics widgets.',
    'log_retention_days' => 'Log retention',
    'hash_visitor_data' => 'Hash visitor data',
    'hash_visitor_data_helper' => 'Store hashes for IP address and user agent instead of raw visitor data.',
    'minimum_query_length' => 'Minimum query length',
];
