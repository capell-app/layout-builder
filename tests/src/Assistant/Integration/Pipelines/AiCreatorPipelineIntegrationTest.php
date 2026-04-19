<?php

declare(strict_types=1);

use Capell\Assistant\DataObjects\AiCreatorData;
use Capell\Assistant\Models\AiCreatorContext;
use Capell\Assistant\Models\AiCreatorSession;
use Capell\Assistant\Models\AIGenerationHistory;
use Capell\Assistant\Support\AiRateLimiter;
use Capell\Assistant\Support\AiResponse;
use Capell\Assistant\Support\Pipelines\AiCreatorPipeline;
use Capell\Assistant\Support\PrismProvider;
use Capell\Assistant\Support\PromptRepository;
use Capell\Assistant\Support\SectionRegistry;
use Mockery\MockInterface;

function makePipeline(
    PromptRepository $prompts,
    PrismProvider $provider,
    ?AiRateLimiter $rateLimiter = null,
    ?SectionRegistry $registry = null,
): AiCreatorPipeline {
    if (! $rateLimiter instanceof AiRateLimiter) {
        $rateLimiter = mock(AiRateLimiter::class, function (AiRateLimiter&MockInterface $mock): void {
            $mock->shouldReceive('checkLimit')->andReturnNull();
        });
    }

    return new AiCreatorPipeline($prompts, $provider, $rateLimiter, $registry ?? new SectionRegistry);
}

function makePrompts(): PromptRepository
{
    return new PromptRepository([
        'ai_creator_layout' => [
            'system' => 'You are an AI layout designer.',
            'user_template' => 'Intent: {{intent}} Tone: {{tone}} Industry: {{industry}} Audience: {{target_audience}} Brand: {{brand_voice_notes}} Sections: {{section_types}}',
        ],
    ]);
}

function makeProvider(array $sections): PrismProvider
{
    return mock(PrismProvider::class, function (PrismProvider&MockInterface $mock) use ($sections): void {
        $mock->shouldReceive('chat')->once()->andReturn(new AiResponse(
            content: json_encode($sections),
            tokensUsed: 100,
            model: 'gpt-4o',
            duration: 0.5,
            metadata: ['prompt_tokens' => 80, 'completion_tokens' => 20],
        ));
    });
}

it('creates a session and returns the proposed sections', function (): void {
    $sections = [
        ['section_type' => 'hero-fullwidth', 'fields' => ['headline' => 'Welcome']],
        ['section_type' => 'text-block', 'fields' => ['body' => 'About us']],
    ];

    $pipeline = makePipeline(makePrompts(), makeProvider($sections));
    $data = new AiCreatorData(siteId: 1, userId: 2, intent: 'Build a homepage');

    $result = $pipeline->execute($data);

    expect($result)->toBe($sections);

    $session = AiCreatorSession::query()->latest()->first();
    expect($session)->not->toBeNull()
        ->and($session->status)->toBe('review')
        ->and($session->stage)->toBe(3)
        ->and($session->intent)->toBe('Build a homepage')
        ->and($session->layout_proposal)->toBe($sections);
});

it('records an AIGenerationHistory entry after successful generation', function (): void {
    $sections = [['section_type' => 'hero', 'fields' => []]];

    $pipeline = makePipeline(makePrompts(), makeProvider($sections));
    $pipeline->execute(new AiCreatorData(siteId: 1, userId: 2, intent: 'Test intent'));

    $history = AIGenerationHistory::query()->latest()->first();
    expect($history)->not->toBeNull()
        ->and($history->action)->toBe('ai_creator_layout')
        ->and($history->model)->toBe('gpt-4o')
        ->and($history->input)->toBe('Test intent')
        ->and($history->total_tokens)->toBe(100);
});

it('loads an existing session when existingSessionId is provided', function (): void {
    $existing = AiCreatorSession::query()->create([
        'site_id' => 1,
        'user_id' => 2,
        'status' => 'generating',
        'stage' => 1,
        'intent' => 'Original intent',
    ]);

    $sections = [['section_type' => 'text', 'fields' => []]];
    $pipeline = makePipeline(makePrompts(), makeProvider($sections));

    $data = new AiCreatorData(
        siteId: 1,
        userId: 2,
        intent: 'Updated intent',
        existingSessionId: $existing->id,
    );

    $pipeline->execute($data);

    expect(AiCreatorSession::query()->count())->toBe(1);

    $existing->refresh();
    expect($existing->status)->toBe('review');
});

it('throws when ai_creator_layout prompt is missing', function (): void {
    $provider = mock(PrismProvider::class);
    $pipeline = makePipeline(new PromptRepository([]), $provider);

    expect(fn (): mixed => $pipeline->execute(new AiCreatorData(siteId: 1, userId: 2, intent: 'test')))
        ->toThrow(InvalidArgumentException::class, 'Missing ai_creator_layout');
});

it('throws when the AI returns a non-JSON string', function (): void {
    $provider = mock(PrismProvider::class, function (PrismProvider&MockInterface $mock): void {
        $mock->shouldReceive('chat')->once()->andReturn(new AiResponse(
            content: 'Sorry, I cannot generate that.',
            tokensUsed: 10,
            model: 'gpt-4o',
            duration: 0.1,
            metadata: [],
        ));
    });

    $pipeline = makePipeline(makePrompts(), $provider);

    expect(fn (): mixed => $pipeline->execute(new AiCreatorData(siteId: 1, userId: 2, intent: 'test')))
        ->toThrow(InvalidArgumentException::class, 'not a valid JSON array');
});

it('strips markdown code fences before parsing the AI response', function (): void {
    $sections = [['section_type' => 'hero', 'fields' => ['headline' => 'Hi']]];

    $provider = mock(PrismProvider::class, function (PrismProvider&MockInterface $mock) use ($sections): void {
        $mock->shouldReceive('chat')->once()->andReturn(new AiResponse(
            content: "```json\n" . json_encode($sections) . "\n```",
            tokensUsed: 10,
            model: 'gpt-4o',
            duration: 0.1,
            metadata: [],
        ));
    });

    $pipeline = makePipeline(makePrompts(), $provider);
    $result = $pipeline->execute(new AiCreatorData(siteId: 1, userId: 2, intent: 'test'));

    expect($result)->toBe($sections);
});

it('includes context brand voice and industry in the AI prompt', function (): void {
    AiCreatorContext::query()->create([
        'site_id' => 5,
        'brand_voice_notes' => 'We speak like a trusted friend',
        'industry' => 'fintech',
        'tone' => 'professional',
        'target_audience' => 'SMB owners',
    ]);

    $capturedMessages = null;

    $provider = mock(PrismProvider::class, function (PrismProvider&MockInterface $mock) use (&$capturedMessages): void {
        $mock->shouldReceive('chat')->once()->withArgs(function (array $params) use (&$capturedMessages): bool {
            $capturedMessages = $params['messages'];

            return true;
        })->andReturn(new AiResponse(
            content: json_encode([['section_type' => 'hero', 'fields' => []]]),
            tokensUsed: 10,
            model: 'gpt-4o',
            duration: 0.1,
            metadata: [],
        ));
    });

    $pipeline = makePipeline(makePrompts(), $provider);
    $pipeline->execute(new AiCreatorData(siteId: 5, userId: 2, intent: 'test'));

    $userMessage = collect($capturedMessages)->firstWhere('role', 'user')['content'] ?? '';
    expect($userMessage)
        ->toContain('We speak like a trusted friend')
        ->toContain('fintech')
        ->toContain('SMB owners');
});

it('falls back to data-level tone and industry when no context exists', function (): void {
    $capturedMessages = null;

    $provider = mock(PrismProvider::class, function (PrismProvider&MockInterface $mock) use (&$capturedMessages): void {
        $mock->shouldReceive('chat')->once()->withArgs(function (array $params) use (&$capturedMessages): bool {
            $capturedMessages = $params['messages'];

            return true;
        })->andReturn(new AiResponse(
            content: json_encode([['section_type' => 'hero', 'fields' => []]]),
            tokensUsed: 10,
            model: 'gpt-4o',
            duration: 0.1,
            metadata: [],
        ));
    });

    $pipeline = makePipeline(makePrompts(), $provider);
    $pipeline->execute(new AiCreatorData(
        siteId: 99,
        userId: 2,
        intent: 'test',
        tone: 'playful',
        industry: 'gaming',
    ));

    $userMessage = collect($capturedMessages)->firstWhere('role', 'user')['content'] ?? '';
    expect($userMessage)
        ->toContain('playful')
        ->toContain('gaming');
});

it('includes available section types in the AI prompt when registry is populated', function (): void {
    $registry = new SectionRegistry;
    $registry->register('hero-fullwidth', [
        'label' => 'Hero — Full Width',
        'description' => 'A bold full-width hero',
        'good_for' => ['homepages', 'landing pages'],
        'not_for' => [],
        'fields' => ['headline', 'subheadline', 'cta_label'],
        'media' => ['background_image'],
        'supports_translations' => true,
        'repeatable' => false,
    ]);

    $capturedMessages = null;

    $provider = mock(PrismProvider::class, function (PrismProvider&MockInterface $mock) use (&$capturedMessages): void {
        $mock->shouldReceive('chat')->once()->withArgs(function (array $params) use (&$capturedMessages): bool {
            $capturedMessages = $params['messages'];

            return true;
        })->andReturn(new AiResponse(
            content: json_encode([['section_type' => 'hero-fullwidth', 'fields' => []]]),
            tokensUsed: 10,
            model: 'gpt-4o',
            duration: 0.1,
            metadata: [],
        ));
    });

    $pipeline = makePipeline(makePrompts(), $provider, registry: $registry);
    $pipeline->execute(new AiCreatorData(siteId: 1, userId: 2, intent: 'test'));

    $userMessage = collect($capturedMessages)->firstWhere('role', 'user')['content'] ?? '';
    expect($userMessage)->toContain('hero-fullwidth');
});
