<?php

declare(strict_types=1);

return [
    'enabled' => env('CAPELL_FRONTEND_AUTHORING', true),

    'selectors' => [
        'page_title' => '#main h1:first-of-type',
        'page_content' => '#main .content-component:first-of-type',
    ],
];
