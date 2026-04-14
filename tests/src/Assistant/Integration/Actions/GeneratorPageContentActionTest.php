<?php

declare(strict_types=1);

namespace Capell\Tests\Assistant\Integration\Actions\Ai;

use Capell\Assistant\Actions\GeneratorPageContentAction;
use Capell\Assistant\Support\AiResponse;
use Capell\Assistant\Support\Context\ContentActionContext;
use Capell\Assistant\Support\OpenAIProvider;
use Capell\Tests\Assistant\Fixtures\FakeContext;
use Capell\Tests\Assistant\Fixtures\FakeOpenAIProviderForContent;
use OpenAI\Laravel\Facades\OpenAI;
use RuntimeException;
use stdClass;

it('generates page content using provider', function (): void {
    app()->bind(OpenAIProvider::class, fn (): FakeOpenAIProviderForContent => new FakeOpenAIProviderForContent);

    $result = GeneratorPageContentAction::run(new FakeContext('Create content'));

    $lines = preg_split('/\r\n|\r|\n/', (string) $result) ?? [];

    expect($result)->toBeString()
        ->and($result)->toContain('Hello')
        ->and($result)->toContain('World')
        ->and(array_values(array_filter($lines, fn (string $l): bool => trim($l) !== '')))->toHaveCount(2);
});

it('handles provider failure', function (): void {
    app()->bind(OpenAIProvider::class, fn (): OpenAIProvider => new class([]) extends OpenAIProvider
    {
        public function chat(array $params): AiResponse
        {
            throw new RuntimeException('provider down');
        }
    });

    expect(fn (): mixed => GeneratorPageContentAction::run(new FakeContext('Create content')))
        ->toThrow(RuntimeException::class);
});

it('generates long-form page content through pipeline', function (): void {
    OpenAI::swap(new class
    {
        private readonly object $chat;

        public function __construct()
        {
            $this->chat = new class
            {
                public function create(array $params): stdClass
                {
                    // Return a markdown-like structured draft
                    return (object) [
                        'choices' => [
                            (object) ['message' => (object) ['content' => "# Laravel Tips\n\n## Introduction\nLearn practical Laravel and PHP tips.\n\n## Best Practices\n- Use actions\n- Prefer composition\n\n## Conclusion\nStart building better apps today."], 'finish_reason' => 'stop'],
                        ],
                        'usage' => (object) ['total_tokens' => 120, 'prompt_tokens' => 60, 'completion_tokens' => 60],
                    ];
                }
            };
        }

        public function chat(): object
        {
            return $this->chat;
        }
    });

    $context = new ContentActionContext(content: 'Laravel development tips', keywords: 'laravel, php', pageId: 1, pageType: 'page', languageId: 1);

    $options = [
        'user_id' => 1,
        'current_title' => 'Laravel Development Tips',
        'target_length' => 800,
        'refactor' => true,
    ];

    $draft = GeneratorPageContentAction::run($context, $options);

    expect($draft)->toBeString()
        ->and($draft)->toContain('# Laravel Tips')
        ->and($draft)->toContain('## Best Practices');
});

it('throws when rate limited for content generation', function (): void {
    config()->set('capell-assistant.rate_limiting', ['enabled' => true, 'requests_per_minute' => 0]);

    $context = new ContentActionContext(content: 'Laravel development tips', keywords: 'laravel, php', pageId: 1, pageType: 'page', languageId: 1);

    expect(fn (): mixed => GeneratorPageContentAction::run($context))->toThrow(RuntimeException::class);
});
