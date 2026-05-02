<?php

declare(strict_types=1);

return [
    'queue' => [
        'connection' => env('BACKUP_QUEUE_CONNECTION'),
        'name' => env('BACKUP_QUEUE', 'backup'),
    ],

    'disk' => env('BACKUP_DISK', 'local'),

    'paths' => [
        'imports' => 'backup/imports',
        'exports' => 'backup/exports',
        'working' => 'backup/working',
    ],

    'limits' => [
        'max_metadata_json_bytes' => 1024 * 1024,
        'max_payload_json_bytes' => 5 * 1024 * 1024,
        'max_media_bytes' => 50 * 1024 * 1024,
        'max_package_uncompressed_bytes' => 250 * 1024 * 1024,
    ],

    'notifications' => [
        'enabled' => env('CAPELL_BACKUP_NOTIFICATIONS', true),
        'channels' => ['mail', 'database'],
        'recipients' => [
            'completed' => [],
            'failed' => [],
        ],
    ],
];
