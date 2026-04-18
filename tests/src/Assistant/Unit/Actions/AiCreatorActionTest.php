<?php

declare(strict_types=1);

use Capell\Assistant\Filament\Actions\AiCreatorAction;
use Capell\Assistant\Policies\AiCreatorPolicy;
use Capell\Assistant\Settings\AssistantSettings;
use Filament\Actions\Action;

function makeCreatorSettings(bool $enabled): AssistantSettings
{
    $settings = new AssistantSettings;
    $settings->ai_creator = $enabled;
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

it('AiCreatorAction is a Filament Action named ai-creator', function (): void {
    $action = AiCreatorAction::make();

    expect($action)->toBeInstanceOf(Action::class)
        ->and($action->getName())->toBe('ai-creator');
});

it('action is hidden when policy reports AI Creator is disabled', function (): void {
    $policy = new AiCreatorPolicy(makeCreatorSettings(false));
    app()->instance(AiCreatorPolicy::class, $policy);

    $action = AiCreatorAction::make();

    // visible() closure receives the site stub from resolveSiteFromRecord() which
    // returns an object with ai_creator_enabled = null when no record is set.
    // With global disabled and no override the policy returns false.
    expect($action->isVisible())->toBeFalse();
});

it('action is visible when policy reports AI Creator is enabled', function (): void {
    $policy = new AiCreatorPolicy(makeCreatorSettings(true));
    app()->instance(AiCreatorPolicy::class, $policy);

    $action = AiCreatorAction::make();

    expect($action->isVisible())->toBeTrue();
});

it('action label is AI Creator', function (): void {
    $action = AiCreatorAction::make();

    expect($action->getLabel())->toBe('AI Creator');
});
