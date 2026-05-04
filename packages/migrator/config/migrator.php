<?php

declare(strict_types=1);

return [
    'queue' => [
        'connection' => env('MIGRATOR_QUEUE_CONNECTION'),
        'name' => env('MIGRATOR_QUEUE', 'migrator'),
    ],

    'disk' => env('MIGRATOR_DISK', 'local'),

    'paths' => [
        'imports' => 'migrator/imports',
        'exports' => 'migrator/exports',
        'working' => 'migrator/working',
    ],

    'limits' => [
        'max_metadata_json_bytes' => 1024 * 1024,
        'max_payload_json_bytes' => 5 * 1024 * 1024,
        'max_media_bytes' => 50 * 1024 * 1024,
        'max_package_uncompressed_bytes' => 250 * 1024 * 1024,
    ],

    'notifications' => [
        'enabled' => env('CAPELL_MIGRATOR_NOTIFICATIONS', true),
        'channels' => ['mail', 'database'],
        'recipients' => [
            'completed' => [],
            'failed' => [],
        ],
    ],
];
