<?php

declare(strict_types=1);

use Capell\SeoSuite\Actions\Ai\RecordAiGenerationAction;
use Capell\SeoSuite\Contracts\AiActionContextInterface;
use Capell\SeoSuite\Data\Ai\AiGenerationInputData;
use Capell\SeoSuite\Support\AiRateLimiter;
use Capell\SeoSuite\Support\AiResponse;
use Capell\SeoSuite\Support\Cache\RateLimitCache;
use Capell\SeoSuite\Support\Pipelines\GenerateContentPipeline;
use Capell\SeoSuite\Support\PrismProvider;
use Capell\SeoSuite\Support\PromptRepository;

function executeGenerateContentPipelineWithHtml(string $html): string
{
    $pipeline = new GenerateContentPipeline(
        new PromptRepository([
            'content_generation' => [
                'system' => 'Return safe page content.',
                'user_template' => '{{content}}',
                'model' => 'test-model',
            ],
        ]),
        new class($html) extends PrismProvider
        {
            public function __construct(private readonly string $html)
            {
                parent::__construct(['max_retries' => 1]);
            }

            public function chat(array $params): AiResponse
            {
                return new AiResponse(
                    content: $this->html,
                    tokensUsed: 1,
                    model: 'test-model',
                    duration: 0.01,
                    metadata: ['prompt_tokens' => 1, 'completion_tokens' => 0],
                );
            }
        },
        new AiRateLimiter(resolve(RateLimitCache::class), ['enabled' => false]),
        new RecordAiGenerationAction,
    );

    $context = new class implements AiActionContextInterface
    {
        public function getContent(): string
        {
            return 'Original content';
        }

        public function getKeywords(): string
        {
            return 'keyword';
        }

        public function getPageId(): int
        {
            return 1;
        }

        public function getPageType(): string
        {
            return 'page';
        }

        public function getLanguageId(): int
        {
            return 1;
        }
    };

    return (string) $pipeline->execute(AiGenerationInputData::forContextAction(
        'GeneratorPageContentAction',
        $context,
        ['user_id' => 123],
    ))->output;
}

it('sanitizes unsafe AI generated HTML attributes and schemes', function (string $html, array $missingFragments, array $expectedFragments): void {
    $result = executeGenerateContentPipelineWithHtml($html);

    foreach ($missingFragments as $missingFragment) {
        expect($result)->not->toContain($missingFragment);
    }

    foreach ($expectedFragments as $expectedFragment) {
        expect($result)->toContain($expectedFragment);
    }
})->with([
    'unquoted image event handler' => [
        '<img src=x onerror=alert(1)>',
        ['onerror', 'alert(1)'],
        ['<img src="x">'],
    ],
    'svg event handler' => [
        '<svg onload=alert(1)><circle></circle></svg>',
        ['svg', 'onload', 'alert(1)'],
        [],
    ],
    'quoted mixed-case event handler' => [
        '<p OnClick="alert(1)">Hello</p>',
        ['OnClick', 'onclick', 'alert(1)'],
        ['<p>Hello</p>'],
    ],
    'javascript href' => [
        '<a href=javascript:alert(1)>Bad link</a>',
        ['href="javascript:', 'javascript:alert'],
        ['<span>Bad link</span>'],
    ],
]);

it('removes external links but keeps relative links', function (): void {
    $result = executeGenerateContentPipelineWithHtml(
        '<a href="https://evil.test" target="_blank">External</a><a href="/safe" target="_blank" rel="noopener">Safe</a>',
    );

    expect($result)
        ->not->toContain('https://evil.test')
        ->not->toContain('target=')
        ->not->toContain('rel=')
        ->toContain('<span>External</span>')
        ->toContain('<a href="/safe">Safe</a>');
});
