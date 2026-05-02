<?php

declare(strict_types=1);

return [
    // Asset build tool: 'vite' | 'mix' | 'static'
    'asset_build_tool' => 'vite',

    // NPM dependencies required by the default theme
    'npm_dependencies' => [
        '@tailwindcss/forms' => '^0.5.3',
        '@tailwindcss/typography' => '^0.5.9',
        '@tailwindcss/vite' => '^4.0.13',
        'autoprefixer' => '^10.4.13',
        'swiper' => '^11.1.14',
        'laravel-vite-plugin' => '^1.2.0',
        'npm-run-all' => '^4.1.5',
        'tailwindcss' => '^4.0.14',
        'tippy.js' => '^6.3.7',
        'vanilla-lazyload' => '^19.1.3',
        'vite' => '^6.2.1',
    ],

    // Tailwind CSS generation settings
    'tailwind' => [
        'imports' => [],
        'plugins' => [
            '@tailwindcss/typography',
        ],
        'sources' => [
            'resources/views/**/*.blade.php',
        ],
        'validate_sources' => env('CAPELL_TW_VALIDATE_SOURCES', false),
        'output_css' => 'resources/css/capell/frontend.css',
    ],

    // Media & Storage
    'local_storage_url' => env('CAPELL_LOCAL_STORAGE_URL', ''),
    'use_site_domain_for_media' => env('CAPELL_USE_SITE_DOMAIN_FOR_MEDIA', false),
    'site_base_url' => env('CAPELL_SITE_BASE_URL', ''),
];
