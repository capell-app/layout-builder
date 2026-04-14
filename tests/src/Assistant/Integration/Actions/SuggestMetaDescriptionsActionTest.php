<?php

declare(strict_types=1);

namespace Capell\Tests\Assistant\Integration\Actions\Ai;

use Capell\Assistant\Actions\SuggestMetaDescriptionsAction;
use Capell\Assistant\Events\AiGenerationCompleted;
use Capell\Assistant\Support\AiResponse;
use Capell\Assistant\Support\Context\ContentActionContext;
use Capell\Assistant\Support\OpenAIProvider;
use Capell\Tests\Assistant\Fixtures\FakeContext;
use Capell\Tests\Assistant\Fixtures\FakeOpenAIProviderForDescriptions;
use Illuminate\Support\Facades\Event;
use OpenAI\Laravel\Facades\OpenAI;
use RuntimeException;
use stdClass;

it('suggests meta descriptions using provider', function (): void {
    app()->bind(OpenAIProvider::class, fn (): FakeOpenAIProviderForDescriptions => new FakeOpenAIProviderForDescriptions);

    $descriptions = SuggestMetaDescriptionsAction::run(new FakeContext('Some content'));

    expect($descriptions)->toBeArray()->toHaveCount(2)
        ->and($descriptions)->toContain('Description 1');
});

it('handles provider error path', function (): void {
    app()->bind(OpenAIProvider::class, fn (): OpenAIProvider => new class([]) extends OpenAIProvider
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
    OpenAI::swap(new class
    {
        private readonly object $chat;

        public function __construct()
        {
            $this->chat = new class
            {
                public function create(array $params): stdClass
                {
                    return (object) [
                        'choices' => [(object) ['message' => (object) ['content' => "- First\n- Second\n- Third"], 'finish_reason' => 'stop']],
                        'usage' => (object) ['total_tokens' => 20, 'prompt_tokens' => 8, 'completion_tokens' => 12],
                    ];
                }
            };
        }

        public function chat(): object
        {
            return $this->chat;
        }
    });

    Event::fake();
    $context = new ContentActionContext(content: 'Meta desc test', keywords: 'desc, test', pageId: 3, pageType: 'page', languageId: 1);
    $descs = SuggestMetaDescriptionsAction::run($context);
    expect($descs)->toBeArray()->and(count($descs))->toBeGreaterThan(0);
    Event::assertDispatched(AiGenerationCompleted::class);
});
