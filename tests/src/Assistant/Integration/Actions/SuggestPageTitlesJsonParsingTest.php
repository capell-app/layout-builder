<?php

declare(strict_types=1);

use Capell\Assistant\Actions\SuggestPageTitlesAction;
use Capell\Assistant\Support\AiResponse;
use Capell\Assistant\Support\Context\ContentActionContext;
use Capell\Assistant\Support\PrismProvider;

uses()->group('admin-ai');

it('parses JSON-formatted title suggestions', function (): void {
    app()->bind(PrismProvider::class, fn (): PrismProvider => new class([]) extends PrismProvider
    {
        public function chat(array $params): AiResponse
        {
            return new AiResponse(
                content: json_encode(['Awesome Laravel Guide', 'Practical PHP Tips', 'Mastering Eloquent'], JSON_THROW_ON_ERROR),
                tokensUsed: 30,
                model: 'gpt-4o',
                duration: 0.001,
                metadata: ['prompt_tokens' => 10, 'completion_tokens' => 20],
            );
        }
    });

    $context = new ContentActionContext(content: 'Laravel development tips', keywords: 'laravel, php', pageId: 1, pageType: 'page', languageId: 1);
    $titles = SuggestPageTitlesAction::run($context);

    expect($titles)->toBeArray();
    expect($titles)->toHaveCount(3);
    expect($titles[0])->toBe('Awesome Laravel Guide');
    expect($titles[1])->toBe('Practical PHP Tips');
    expect($titles[2])->toBe('Mastering Eloquent');
});
