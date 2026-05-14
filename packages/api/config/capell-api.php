<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware values wrap the public read-only Capell API routes. Keep
    | the defaults public; consuming apps can add throttle or auth middleware
    | without replacing package routes.
    |
    */
    'middleware' => ['api'],

    'public_pages' => [
        'auth_middleware' => null,
        'rate_limit_middleware' => null,
        'rate_limit_per_minute' => 60,
        'max_candidate_sites' => 50,
        'middleware' => [],
    ],
];
