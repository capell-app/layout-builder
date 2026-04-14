<?php

declare(strict_types=1);

use Capell\Assistant\Actions\GeneratorPageContentAction;

return [
    'openai' => [
        'max_retries' => 3,
        'retry_delay_ms' => 500,
        'default_model' => 'gpt-4',
        'max_tokens' => 512,
    ],
    'rate_limiting' => [
        'enabled' => true,
        'requests_per_minute' => 60,
    ],
    'features' => [
        'title_generation' => [
            'enabled' => true,
            'model' => 'gpt-4-turbo',
            'handler' => 'Capell\\Admin\\Actions\\AI\\GeneratePageTitleAction',
        ],
        'meta_description' => [
            'enabled' => true,
            'model' => 'gpt-4-turbo',
            'handler' => 'Capell\\Admin\\Actions\\AI\\GenerateMetaDescriptionAction',
        ],
        'content_generation' => [
            'enabled' => true,
            'model' => 'gpt-4-turbo',
            'handler' => GeneratorPageContentAction::class,
        ],
    ],
    'cache' => [
        'ttl' => 86400, // 1 day
    ],
];
