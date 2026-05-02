<?php

declare(strict_types=1);

use Capell\SeoTools\DataObjects\AiCreatorData;
use Capell\SeoTools\Support\AiRateLimiter;
use Capell\SeoTools\Support\AiResponse;
use Capell\SeoTools\Support\Cache\RateLimitCache;
use Capell\SeoTools\Support\Pipelines\AiCreatorPipeline;
use Capell\SeoTools\Support\PrismProvider;
use Capell\SeoTools\Support\PromptRepository;
use Capell\SeoTools\Support\SectionRegistry;
use Illuminate\Support\Str;

function makeAiCreatorPipelineForJson(string $json): AiCreatorPipeline
{
    $sectionRegistry = new SectionRegistry;
    $sectionRegistry->register('hero', [
        'label' => 'Hero',
        'description' => 'Page hero',
        'good_for' => ['introductions'],
        'not_for' => [],
        'fields' => ['heading'],
        'media' => [],
        'supports_translations' => true,
        'repeatable' => false,
    ]);

    return new AiCreatorPipeline(
        new PromptRepository([
            'ai_creator_layout' => [
                'system' => 'Return layout JSON.',
                'user_template' => '{{intent}} {{section_types}}',
            ],
        ]),
        new class($json) extends PrismProvider
        {
            public function __construct(private readonly string $json)
            {
                parent::__construct(['max_retries' => 1]);
            }

            public function chat(array $params): AiResponse
            {
                return new AiResponse(
                    content: $this->json,
                    tokensUsed: 1,
                    model: 'test-model',
                    duration: 0.01,
                    metadata: ['prompt_tokens' => 1, 'completion_tokens' => 0],
                );
            }
        },
        new AiRateLimiter(resolve(RateLimitCache::class), ['enabled' => false]),
        $sectionRegistry,
    );
}

it('rejects AI creator sections with unregistered section types', function (): void {
    $pipeline = makeAiCreatorPipelineForJson('[{"section_type":"unknown","fields":{"heading":"Hello"}}]');

    expect(fn (): array => $pipeline->execute(new AiCreatorData(
        siteId: 1,
        userId: 10,
        intent: 'Build a landing page',
    )))->toThrow(InvalidArgumentException::class, 'not registered');
});

it('rejects AI creator layouts with more than eight sections', function (): void {
    $sections = collect(range(1, 9))
        ->map(fn (int $sectionNumber): array => [
            'section_type' => 'hero',
            'fields' => ['heading' => 'Heading ' . $sectionNumber],
        ])
        ->all();

    $pipeline = makeAiCreatorPipelineForJson(json_encode($sections, JSON_THROW_ON_ERROR));

    expect(fn (): array => $pipeline->execute(new AiCreatorData(
        siteId: 1,
        userId: 10,
        intent: 'Build a long landing page',
    )))->toThrow(InvalidArgumentException::class, 'at most 8 sections');
});

it('strips unknown top-level section fields from valid AI creator layouts', function (): void {
    $pipeline = makeAiCreatorPipelineForJson('[{"section_type":"hero","fields":{"heading":"Hello"},"unexpected":"remove me"}]');

    $sections = $pipeline->execute(new AiCreatorData(
        siteId: 1,
        userId: 10,
        intent: Str::random(12),
    ));

    expect($sections)->toBe([
        [
            'section_type' => 'hero',
            'fields' => ['heading' => 'Hello'],
        ],
    ]);
});
