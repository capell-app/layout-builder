<?php

declare(strict_types=1);

use Capell\Assistant\Policies\AiCreatorPolicy;
use Capell\Assistant\Settings\AssistantSettings;

function makeSettings(bool $aiCreator): AssistantSettings
{
    $settings = new AssistantSettings;
    $settings->ai_creator = $aiCreator;
    $settings->ai_provider = 'openai';
    $settings->ai_model = 'gpt-4o';
    $settings->ai_api_key = '';
    $settings->image_provider = 'openai';
    $settings->image_model = 'dall-e-3';
    $settings->image_default_size = '1024x1024';
    $settings->page_content_generator = true;
    $settings->page_title_suggestions = true;

    return $settings;
}

it('returns true when global setting is enabled and no site override', function (): void {
    $policy = new AiCreatorPolicy(makeSettings(true));
    $site = new stdClass;
    $site->ai_creator_enabled = null;

    expect($policy->isEnabledFor($site))->toBeTrue();
});

it('returns false when global setting is disabled', function (): void {
    $policy = new AiCreatorPolicy(makeSettings(false));
    $site = new stdClass;
    $site->ai_creator_enabled = null;

    expect($policy->isEnabledFor($site))->toBeFalse();
});

it('site-level override takes precedence over global', function (): void {
    $policy = new AiCreatorPolicy(makeSettings(true));
    $site = new stdClass;
    $site->ai_creator_enabled = false;

    expect($policy->isEnabledFor($site))->toBeFalse();
});

it('site-level true enables even when global is false', function (): void {
    $policy = new AiCreatorPolicy(makeSettings(false));
    $site = new stdClass;
    $site->ai_creator_enabled = true;

    expect($policy->isEnabledFor($site))->toBeTrue();
});
