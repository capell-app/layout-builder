<?php

declare(strict_types=1);

namespace Capell\Assistant\Settings;

use Spatie\LaravelSettings\Settings;

class AssistantSettings extends Settings
{
    public bool $page_content_generator;

    public bool $page_title_suggestions;

    public bool $ai_creator;

    public string $ai_provider;

    public string $ai_model;

    public string $ai_api_key;

    public string $image_provider;

    public string $image_model;

    public string $image_default_size;

    public static function group(): string
    {
        return 'assistant';
    }
}
