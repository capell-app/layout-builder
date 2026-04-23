<?php

declare(strict_types=1);

namespace Capell\Tests\Assistant\Integration\Actions\Ai;

use Capell\SeoTools\Actions\SuggestMetaDescriptionsAction;
use Capell\SeoTools\Events\AiGenerationCompleted;
use Capell\SeoTools\Support\AiResponse;
use Capell\SeoTools\Support\Context\ContentActionContext;
use Capell\SeoTools\Support\PrismProvider;
use Capell\Tests\Assistant\Fixtures\FakeContext;
use Capell\Tests\Assistant\Fixtures\FakeOpenAIProviderForDescriptions;
use Illuminate\Support\Facades\Event;
use RuntimeException;

it('suggests meta descriptions using provider', function (): void {
    app()->bind(PrismProvider::class, fn (): FakeOpenAIProviderForDescriptions => new FakeOpenAIProviderForDescriptions);

    $descriptions = SuggestMetaDescriptionsAction::run(new FakeContext('Some content'));

    expect($descriptions)->toBeArray()->toHaveCount(2)
        ->and($descriptions)->toContain('Description 1');
});

it('handles provider error path', function (): void {
    app()->bind(PrismProvider::class, fn (): PrismProvider => new class([]) extends PrismProvider
    {
        public function chat(array $params): AiResponse
        {
            throw new RuntimeException('provider down');
        }
    });

    expect(fn (): mixed => SuggestMetaDescriptionsAction::run(new FakeContext('content')))
        ->toThrow(RuntimeException::class);
});

it('suggests meta descriptions and dispatches event', function (): void {
    app()->bind(PrismProvider::class, fn (): PrismProvider => new class([]) extends PrismProvider
    {
        public function chat(array $params): AiResponse
        {
            return new AiResponse(
                content: "- First\n- Second\n- Third",
                tokensUsed: 20,
                model: 'gpt-4o',
                duration: 0.001,
                metadata: ['prompt_tokens' => 8, 'completion_tokens' => 12],
            );
        }
    });

    Event::fake();
    $context = new ContentActionContext(content: 'Meta desc test', keywords: 'desc, test', pageId: 3, pageType: 'page', languageId: 1);
    $descs = SuggestMetaDescriptionsAction::run($context);
    expect($descs)->toBeArray()->and(count($descs))->toBeGreaterThan(0);
    Event::assertDispatched(AiGenerationCompleted::class);
});
