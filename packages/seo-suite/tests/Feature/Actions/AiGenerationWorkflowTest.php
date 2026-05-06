<?php

declare(strict_types=1);

use Capell\SeoSuite\Actions\Ai\RecordAiGenerationAction as NamespacedRecordAiGenerationAction;
use Capell\SeoSuite\Actions\GenerateAiLayoutAction;
use Capell\SeoSuite\Actions\GeneratorPageContentAction;
use Capell\SeoSuite\Actions\SuggestMetaDescriptionsAction;
use Capell\SeoSuite\Actions\SuggestPageTitlesAction;
use Capell\SeoSuite\Contracts\AiActionContextInterface;
use Capell\SeoSuite\Data\Ai\AiGenerationResultData;
use Capell\SeoSuite\DataObjects\AiCreatorData;
use Capell\SeoSuite\Models\AiCreatorContext;
use Capell\SeoSuite\Models\AiCreatorSession;
use Capell\SeoSuite\Models\AIGenerationHistory;
use Capell\SeoSuite\Support\AiRateLimiter;
use Capell\SeoSuite\Support\AiResponse;
use Capell\SeoSuite\Support\Cache\RateLimitCache;
use Capell\SeoSuite\Support\Pipelines\AiCreatorPipeline;
use Capell\SeoSuite\Support\PrismProvider;
use Capell\SeoSuite\Support\PromptRepository;
use Capell\SeoSuite\Support\SectionRegistry;
use Illuminate\Support\Facades\Event;

function aiWorkflowContext(): AiActionContextInterface
{
    return new class implements AiActionContextInterface
    {
        public function getContent(): string
        {
            return 'Existing CMS page content about Laravel platform-builder.';
        }

        public function getKeywords(): string
        {
            return 'laravel cms, scalable content platform';
        }

        public function getPageId(): int
        {
            return 321;
        }

        public function getPageType(): string
        {
            return 'core_page';
        }

        public function getLanguageId(): int
        {
            return 7;
        }
    };
}

function bindAiWorkflowProvider(AiResponse $response): PrismProvider
{
    $provider = new class($response) extends PrismProvider
    {
        /** @var array<int, array<string, mixed>> */
        public array $calls = [];

        public function __construct(private readonly AiResponse $response)
        {
            parent::__construct(['max_retries' => 1]);
        }

        public function chat(array $params): AiResponse
        {
            $this->calls[] = $params;

            return $this->response;
        }
    };

    app()->instance(PrismProvider::class, $provider);

    return $provider;
}

function bindAiWorkflowRateLimiter(?Throwable $throwable = null): AiRateLimiter
{
    $rateLimiter = new class($throwable) extends AiRateLimiter
    {
        /** @var array<int, array{identifier: string, feature: string|null}> */
        public array $checks = [];

        public function __construct(private readonly ?Throwable $throwable)
        {
            parent::__construct(resolve(RateLimitCache::class), ['enabled' => false]);
        }

        public function checkLimit(string $identifier = 'global', ?string $feature = null): void
        {
            $this->checks[] = ['identifier' => $identifier, 'feature' => $feature];

            if ($this->throwable instanceof Throwable) {
                throw $this->throwable;
            }
        }
    };

    app()->instance(AiRateLimiter::class, $rateLimiter);

    return $rateLimiter;
}

function bindAiWorkflowRecorder(): NamespacedRecordAiGenerationAction
{
    $recorder = new class extends NamespacedRecordAiGenerationAction
    {
        /** @var array<int, AiGenerationResultData|array<string, mixed>> */
        public array $records = [];

        public function handle(AiGenerationResultData|array $result): AIGenerationHistory
        {
            $this->records[] = $result;

            return parent::handle($result);
        }
    };

    app()->instance(NamespacedRecordAiGenerationAction::class, $recorder);

    return $recorder;
}

it('generates sanitized page content through the action seam and records history through the recorder action', function (): void {
    Event::fake();

    app()->instance(PromptRepository::class, new PromptRepository([
        'content_generation' => [
            'system' => 'Return safe HTML.',
            'user_template' => 'Title: {{current_title}} Keywords: {{keywords}} Content: {{content}} Length: {{target_length}} Refactor: {{refactor}}',
            'model' => 'workflow-content-model',
        ],
    ]));

    $provider = bindAiWorkflowProvider(new AiResponse(
        content: '<h2 onclick="alert(1)">Fresh content</h2><script>alert(1)</script><a href="/contact" target="_blank">Contact us</a>',
        tokensUsed: 15,
        model: 'workflow-content-model',
        duration: 0.25,
        metadata: ['prompt_tokens' => 6, 'completion_tokens' => 9],
    ));
    $rateLimiter = bindAiWorkflowRateLimiter();
    $recorder = bindAiWorkflowRecorder();

    $result = resolve(GeneratorPageContentAction::class)->handle(aiWorkflowContext(), [
        'user_id' => 44,
        'current_title' => 'Existing service page',
        'target_length' => 300,
        'refactor' => false,
    ]);

    expect($result)
        ->toContain('<h2>Fresh content</h2>')
        ->toContain('<a href="/contact">Contact us</a>')
        ->not->toContain('onclick')
        ->not->toContain('<script>')
        ->and($rateLimiter->checks)->toBe([['identifier' => '44', 'feature' => 'content_generation']])
        ->and($provider->calls[0]['model'])->toBe('workflow-content-model')
        ->and($provider->calls[0]['messages'][1]['content'])->toContain('Existing service page')
        ->and($provider->calls[0]['messages'][1]['content'])->toContain('Refactor: no')
        ->and($recorder->records[0])->toBeInstanceOf(AiGenerationResultData::class);

    $history = AIGenerationHistory::query()->latest('id')->first();

    expect($history?->action)->toBe('GeneratorPageContentAction')
        ->and($history?->output)->toBe($result)
        ->and($history?->pageable_id)->toBe(321)
        ->and($history?->pageable_type)->toBe('core_page')
        ->and($history?->language_id)->toBe(7)
        ->and($history?->metadata['ai_params']['temperature'])->toBe(0.7);
});

it('suggests page titles with rendered prompt parameters and persisted parsed output', function (): void {
    config(['capell-seo-suite.prism.max_tokens' => 128]);

    app()->instance(PromptRepository::class, new PromptRepository([
        'title_generation' => [
            'system' => 'Write titles.',
            'user_template' => 'Content={{content}} Keywords={{keywords}} Current={{current_title}}',
            'model' => 'workflow-title-model',
        ],
    ]));

    $provider = bindAiWorkflowProvider(new AiResponse(
        content: "- First SEO Title\n- Second SEO Title\n- First SEO Title",
        tokensUsed: 9,
        model: 'workflow-title-model',
        duration: 0.12,
        metadata: ['prompt_tokens' => 4, 'completion_tokens' => 5],
    ));
    $rateLimiter = bindAiWorkflowRateLimiter();
    $recorder = bindAiWorkflowRecorder();

    $titles = resolve(SuggestPageTitlesAction::class)->handle(aiWorkflowContext(), [
        'user_id' => 55,
        'current_title' => 'Current CMS title',
    ]);

    expect($titles)->toBe(['First SEO Title', 'Second SEO Title'])
        ->and($rateLimiter->checks)->toBe([['identifier' => '55', 'feature' => 'title_suggestions']])
        ->and($provider->calls[0]['messages'][1]['content'])->toContain('Current CMS title')
        ->and($provider->calls[0]['max_tokens'])->toBe(128)
        ->and($recorder->records[0])->toBeInstanceOf(AiGenerationResultData::class);

    $history = AIGenerationHistory::query()->latest('id')->first();

    expect($history?->action)->toBe('SuggestPageTitlesAction')
        ->and($history?->output)->toBe("First SEO Title\nSecond SEO Title")
        ->and($history?->metadata['ai_messages'][0]['content'])->toBe('Write titles.');
});

it('suggests meta descriptions with rendered prompt parameters and persisted parsed output', function (): void {
    config(['capell-seo-suite.prism.max_tokens' => 160]);

    app()->instance(PromptRepository::class, new PromptRepository([
        'meta_description' => [
            'system' => 'Write descriptions.',
            'user_template' => 'Content={{content}} Keywords={{keywords}}',
            'model' => 'workflow-meta-model',
        ],
    ]));

    $provider = bindAiWorkflowProvider(new AiResponse(
        content: "- Build scalable Laravel CMS platform-builder with practical SEO foundations.\n- Plan a CMS platform that keeps teams publishing confidently.\n- Build scalable Laravel CMS platform-builder with practical SEO foundations.",
        tokensUsed: 11,
        model: 'workflow-meta-model',
        duration: 0.14,
        metadata: ['prompt_tokens' => 5, 'completion_tokens' => 6],
    ));
    $rateLimiter = bindAiWorkflowRateLimiter();
    $recorder = bindAiWorkflowRecorder();

    $descriptions = resolve(SuggestMetaDescriptionsAction::class)->handle(aiWorkflowContext(), [
        'user_id' => 66,
    ]);

    expect($descriptions)->toBe([
        'Build scalable Laravel CMS platform-builder with practical SEO foundations.',
        'Plan a CMS platform that keeps teams publishing confidently.',
    ])
        ->and($rateLimiter->checks)->toBe([['identifier' => '66', 'feature' => 'meta_suggestions']])
        ->and($provider->calls[0]['model'])->toBe('workflow-meta-model')
        ->and($provider->calls[0]['max_tokens'])->toBe(160)
        ->and($provider->calls[0]['temperature'])->toBe(0.7)
        ->and($provider->calls[0]['messages'][0]['content'])->toBe('Write descriptions.')
        ->and($provider->calls[0]['messages'][1]['content'])->toContain('Existing CMS page content about Laravel platform-builder.')
        ->and($provider->calls[0]['messages'][1]['content'])->toContain('laravel cms, scalable content platform')
        ->and($provider->calls[0]['messages'][1]['content'])->toContain('Please provide 3 meta description options')
        ->and($recorder->records[0])->toBeInstanceOf(AiGenerationResultData::class);

    $history = AIGenerationHistory::query()->latest('id')->first();

    expect($history?->action)->toBe('SuggestMetaDescriptionsAction')
        ->and($history?->output)->toBe("Build scalable Laravel CMS platform-builder with practical SEO foundations.\nPlan a CMS platform that keeps teams publishing confidently.")
        ->and($history?->pageable_id)->toBe(321)
        ->and($history?->pageable_type)->toBe('core_page')
        ->and($history?->language_id)->toBe(7)
        ->and($history?->metadata['ai_messages'][0]['content'])->toBe('Write descriptions.')
        ->and($history?->metadata['ai_params']['model'])->toBe('workflow-meta-model');
});

it('stops meta description generation at the rate limiter before provider calls or history writes', function (): void {
    app()->instance(PromptRepository::class, new PromptRepository([
        'meta_description' => [
            'system' => 'Write descriptions.',
            'user_template' => 'Content={{content}} Keywords={{keywords}}',
            'model' => 'workflow-meta-model',
        ],
    ]));

    $provider = bindAiWorkflowProvider(new AiResponse(
        content: '- A clean description',
        tokensUsed: 1,
        model: 'workflow-meta-model',
        duration: 0.01,
    ));
    $rateLimiter = bindAiWorkflowRateLimiter(new RuntimeException('AI rate limit exceeded for user:66'));
    bindAiWorkflowRecorder();

    expect(fn (): array => resolve(SuggestMetaDescriptionsAction::class)->handle(aiWorkflowContext(), ['user_id' => 66]))
        ->toThrow(RuntimeException::class, 'AI rate limit exceeded');

    expect($rateLimiter->checks)->toBe([['identifier' => '66', 'feature' => 'meta_suggestions']])
        ->and($provider->calls)->toBe([])
        ->and(AIGenerationHistory::query()->count())->toBe(0);
});

it('generates an AI creator layout, updates the session, and stores history through the recorder action', function (): void {
    app()->instance(PromptRepository::class, new PromptRepository([
        'ai_creator_layout' => [
            'system' => 'Return layout JSON.',
            'user_template' => 'Intent={{intent}} Tone={{tone}} Industry={{industry}} Audience={{target_audience}} Sections={{section_types}} Notes={{brand_voice_notes}}',
        ],
    ]));

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
    app()->instance(SectionRegistry::class, $sectionRegistry);
    app()->forgetInstance(AiCreatorPipeline::class);

    AiCreatorContext::query()->create([
        'site_id' => 10,
        'tone' => 'friendly',
        'industry' => 'SaaS',
        'target_audience' => 'Operations leaders',
        'brand_voice_notes' => 'Clear and practical',
    ]);

    $provider = bindAiWorkflowProvider(new AiResponse(
        content: '[{"section_type":"hero","fields":{"heading":"Placeholder hero"},"unexpected":"ignored"}]',
        tokensUsed: 20,
        model: 'workflow-creator-model',
        duration: 0.31,
        metadata: ['prompt_tokens' => 8, 'completion_tokens' => 12],
    ));
    $rateLimiter = bindAiWorkflowRateLimiter();
    $recorder = bindAiWorkflowRecorder();

    $sections = resolve(GenerateAiLayoutAction::class)->handle(new AiCreatorData(
        siteId: 10,
        userId: 77,
        intent: 'Build a homepage for the new product',
    ));

    expect($sections)->toBe([
        ['section_type' => 'hero', 'fields' => ['heading' => 'Placeholder hero']],
    ])
        ->and($rateLimiter->checks)->toBe([['identifier' => '77', 'feature' => 'ai_creator']])
        ->and($provider->calls[0]['messages'][1]['content'])->toContain('Build a homepage for the new product')
        ->and($provider->calls[0]['messages'][1]['content'])->toContain('Operations leaders')
        ->and($recorder->records[0])->toBeInstanceOf(AiGenerationResultData::class);

    $session = AiCreatorSession::query()->latest('id')->first();
    $history = AIGenerationHistory::query()->latest('id')->first();

    expect($session?->status)->toBe('review')
        ->and($session?->stage)->toBe(3)
        ->and($session?->layout_proposal)->toBe($sections)
        ->and($session?->ai_history_id)->toBe($history?->id)
        ->and($history?->action)->toBe('ai_creator_layout')
        ->and($history?->metadata['ai_creator_session_id'])->toBe($session?->id);
});
