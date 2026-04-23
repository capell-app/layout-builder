<?php

declare(strict_types=1);

namespace Capell\Tests\Assistant\Integration\Actions\Ai;

use Capell\SeoTools\Actions\SuggestPageTitlesAction;
use Capell\SeoTools\Support\AiResponse;
use Capell\SeoTools\Support\PrismProvider;
use Capell\Tests\Assistant\Fixtures\FakeContext;
use Capell\Tests\Assistant\Fixtures\FakeOpenAIProvider;
use RuntimeException;

it('suggests page titles using provider', function (): void {
    app()->bind(PrismProvider::class, fn (): FakeOpenAIProvider => new FakeOpenAIProvider);

    $titles = SuggestPageTitlesAction::run(new FakeContext('Some content'));

    expect($titles)->toBeArray()->toHaveCount(3)
        ->and($titles)->toContain('Title A');
});

it('handles provider failure path', function (): void {
    app()->bind(PrismProvider::class, fn (): PrismProvider => new class([]) extends PrismProvider
    {
        public function chat(array $params): AiResponse
        {
            throw new RuntimeException('provider down');
        }
    });

    expect(fn (): mixed => SuggestPageTitlesAction::run(new FakeContext('content')))
        ->toThrow(RuntimeException::class);
});
