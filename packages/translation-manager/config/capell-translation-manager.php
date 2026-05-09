<?php

declare(strict_types=1);

return [
    'source_locale' => 'en',

    'locale_pattern' => '/^[a-z]{2,3}(?:[-_][A-Za-z0-9]{2,8})*$/',

    'app_source' => [
        'key' => 'app',
        'label' => 'Application',
        'path' => null,
        'writable' => true,
    ],

    'package_paths' => [
        base_path('packages/*/resources/lang'),
        base_path('../capell-4/packages/*/resources/lang'),
        base_path('../capell-packages-4/packages/*/resources/lang'),
    ],

    'vendor_namespaces' => [
        //
    ],

    'package_source_writes' => false,
];
