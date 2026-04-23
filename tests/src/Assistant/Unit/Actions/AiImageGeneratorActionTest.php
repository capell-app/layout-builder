<?php

declare(strict_types=1);

use Capell\SeoTools\Filament\Actions\AiImageGeneratorAction;
use Filament\Actions\Action;

it('AiImageGeneratorAction is a form Action', function (): void {
    $action = AiImageGeneratorAction::make();

    expect($action)->toBeInstanceOf(Action::class);
});

it('uses the provided name', function (): void {
    $action = AiImageGeneratorAction::make('generate-hero-image');

    expect($action->getName())->toBe('generate-hero-image');
});

it('defaults the name to generate-ai-image', function (): void {
    $action = AiImageGeneratorAction::make();

    expect($action->getName())->toBe('generate-ai-image');
});

it('accepts context field keys without error', function (): void {
    $action = AiImageGeneratorAction::make('my-image', ['page_title', 'page_description']);

    expect($action)->toBeInstanceOf(Action::class)
        ->and($action->getName())->toBe('my-image');
});
