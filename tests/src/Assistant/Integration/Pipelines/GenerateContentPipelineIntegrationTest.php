<?php

declare(strict_types=1);

use Capell\SeoTools\Actions\GeneratorPageContentAction;
use Capell\SeoTools\Models\AIGenerationHistory;
use Capell\SeoTools\Support\AiResponse;
use Capell\SeoTools\Support\Context\ContentActionContext;
use Capell\SeoTools\Support\PrismProvider;

uses()->group('admin-ai');

it('records AIGenerationHistory with metadata after generation', function (): void {
    app()->bind(PrismProvider::class, fn (): PrismProvider => new class([]) extends PrismProvider
    {
        public function chat(array $params): AiResponse
        {
            return new AiResponse(
                content: "# Title\n\nContent",
                tokensUsed: 50,
                model: 'gpt-4o',
                duration: 0.001,
                metadata: ['prompt_tokens' => 25, 'completion_tokens' => 25],
            );
        }
    });

    $context = new ContentActionContext(content: 'C', keywords: 'K', pageId: 123, pageType: 'page', languageId: 9);
    $draft = GeneratorPageContentAction::run($context, ['user_id' => 99]);

    expect($draft)->toBeString();

    /** @var AIGenerationHistory|null $record */
    $record = AIGenerationHistory::query()->latest('id')->first();

    expect($record)->not()->toBeNull()
        ->and($record->metadata)->toBeArray()
        ->and($record->metadata)->not()->toBeEmpty()
        ->and($record->pageable_type)->toBe('page')
        ->and($record->pageable_id)->toBe(123)
        ->and($record->language_id)->toBe(9);

    // Assert persisted columns for page and language identifiers when available
});
