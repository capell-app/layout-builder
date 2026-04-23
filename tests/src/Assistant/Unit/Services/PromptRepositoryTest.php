<?php

declare(strict_types=1);

use Capell\SeoTools\Data\PromptData;

uses()->group('admin-ai');

it('maps settings prompts to typed prompt data', function (): void {
    $prompts = new PromptData(
        model: 'gpt-4-turbo',
        titleGeneration: true,
        titleGenerationSystem: 'title-system',
        titleGenerationUserTemplate: 'title-template',
        metaDescription: true,
        metaDescriptionSystem: 'meta-system',
        metaDescriptionUserTemplate: 'meta-template',
        contentGeneration: true,
        contentGenerationSystem: 'content-system',
        contentGenerationUserTemplate: 'content-template',
    );

    expect($prompts)->titleGeneration->toBeTrue()
        ->model->toBe('gpt-4-turbo')
        ->and($prompts->titleGenerationSystem)->toBe('title-system')
        ->and($prompts->titleGenerationUserTemplate)->toBe('title-template')
        ->and($prompts->metaDescription)->toBeTrue()
        ->and($prompts->metaDescriptionSystem)->toBe('meta-system')
        ->and($prompts->metaDescriptionUserTemplate)->toBe('meta-template')
        ->and($prompts->contentGeneration)->toBeTrue()
        ->and($prompts->contentGenerationSystem)->toBe('content-system')
        ->and($prompts->contentGenerationUserTemplate)->toBe('content-template');
});
