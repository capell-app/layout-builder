<?php

declare(strict_types=1);

use Illuminate\Support\Env;

return [
    'queue' => [
        'connection' => Env::get('MIGRATOR_QUEUE_CONNECTION'),
        'name' => Env::get('MIGRATOR_QUEUE', 'migrator'),
    ],

    'disk' => Env::get('MIGRATOR_DISK', 'local'),

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
        'enabled' => Env::get('CAPELL_MIGRATOR_NOTIFICATIONS', true),
        'channels' => ['mail', 'database'],
        'recipients' => [
            'completed' => [],
            'failed' => [],
        ],
    ],
];
