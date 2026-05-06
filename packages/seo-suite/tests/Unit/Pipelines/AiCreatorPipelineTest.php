<?php

declare(strict_types=1);

use Capell\SeoSuite\Actions\Ai\RecordAiGenerationAction;
use Capell\SeoSuite\Data\Ai\AiGenerationInputData;
use Capell\SeoSuite\Data\Ai\AiGenerationResultData;
use Capell\SeoSuite\DataObjects\AiCreatorData;
use Capell\SeoSuite\Support\AiRateLimiter;
use Capell\SeoSuite\Support\AiResponse;
use Capell\SeoSuite\Support\Cache\RateLimitCache;
use Capell\SeoSuite\Support\Pipelines\AiCreatorPipeline;
use Capell\SeoSuite\Support\PrismProvider;
use Capell\SeoSuite\Support\PromptRepository;
use Capell\SeoSuite\Support\SectionRegistry;
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
        new RecordAiGenerationAction,
    );
}

it('rejects AI creator sections with unregistered section types', function (): void {
    $pipeline = makeAiCreatorPipelineForJson('[{"section_type":"unknown","fields":{"heading":"Hello"}}]');

    $creatorData = new AiCreatorData(
        siteId: 1,
        userId: 10,
        intent: 'Build a landing page',
    );

    expect(fn (): AiGenerationResultData => $pipeline->execute(
        AiGenerationInputData::forAiCreator('ai_creator_layout', $creatorData),
    ))->toThrow(InvalidArgumentException::class, 'not registered');
});

it('rejects AI creator layouts with more than eight sections', function (): void {
    $sections = collect(range(1, 9))
        ->map(fn (int $sectionNumber): array => [
            'section_type' => 'hero',
            'fields' => ['heading' => 'Heading ' . $sectionNumber],
        ])
        ->all();

    $pipeline = makeAiCreatorPipelineForJson(json_encode($sections, JSON_THROW_ON_ERROR));

    $creatorData = new AiCreatorData(
        siteId: 1,
        userId: 10,
        intent: 'Build a long landing page',
    );

    expect(fn (): AiGenerationResultData => $pipeline->execute(
        AiGenerationInputData::forAiCreator('ai_creator_layout', $creatorData),
    ))->toThrow(InvalidArgumentException::class, 'at most 8 sections');
});

it('strips unknown top-level section fields from valid AI creator layouts', function (): void {
    $pipeline = makeAiCreatorPipelineForJson('[{"section_type":"hero","fields":{"heading":"Hello"},"unexpected":"remove me"}]');

    $result = $pipeline->execute(AiGenerationInputData::forAiCreator('ai_creator_layout', new AiCreatorData(
        siteId: 1,
        userId: 10,
        intent: Str::random(12),
    )));

    expect($result->output)->toBe([
        [
            'section_type' => 'hero',
            'fields' => ['heading' => 'Hello'],
        ],
    ]);
});
