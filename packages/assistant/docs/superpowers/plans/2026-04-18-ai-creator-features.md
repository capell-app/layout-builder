# AI Creator — Features Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Prerequisites:** Complete `2026-04-18-ai-creator-foundation.md` before starting this plan. This plan assumes `PrismProvider`, `SectionRegistry`, `ContentTargetResolver`, `AiCreatorContext`, `AiCreatorSession`, `AiCreatorPolicy`, and the admin extender interfaces all exist and are registered.

**Goal:** Build the `AiCreatorAction` (multi-step Filament wizard for generating page layouts), `SubmitAiCreatorDraftAction` (workspace integration), and `AiImageGeneratorAction` (inline image generation for any media field), all registered on existing admin resources via the extender tag pattern.

**Architecture:** `AiCreatorAction` is a Filament action using a `Wizard` form component, backed by `GenerateAiLayoutAction` which runs through `AiCreatorPipeline`. The action is registered against page and site resources through extender classes tagged in `AssistantServiceProvider`. `AiImageGeneratorAction` is a standalone reusable action that composes an AI image prompt from nearby Filament form field values passed to it from the parent resource. Both actions call `GenerateAiLayoutAction` / `GenerateAiImageAction` which accept typed data objects so they can also be called programmatically.

**Tech Stack:** PHP 8.2, Laravel, Filament v3, prism-php/prism, Lorisleiva Laravel Actions, Pest

---

## File Map

### New files — `capell-app/assistant`

```
src/DataObjects/AiCreatorData.php
src/DataObjects/AiImageData.php
src/Actions/GenerateAiLayoutAction.php
src/Actions/GenerateAiImageAction.php
src/Actions/SubmitAiCreatorDraftAction.php
src/Support/Pipelines/AiCreatorPipeline.php
src/Support/Admin/AiCreatorPageExtender.php
src/Support/Admin/AiCreatorSiteExtender.php
src/Filament/Actions/AiCreatorAction.php
src/Filament/Actions/AiImageGeneratorAction.php
tests/Unit/Actions/GenerateAiLayoutActionTest.php
tests/Unit/Actions/GenerateAiImageActionTest.php
tests/Unit/Actions/SubmitAiCreatorDraftActionTest.php
```

### Modified files — `capell-app/assistant`

```
config/capell-assistant.php                    add ai_creator prompts section
src/Providers/AssistantServiceProvider.php     register extenders + new action singletons
```

---

## Task 1: Create `AiCreatorData` and `AiImageData` DTOs

**Files:**

- Create: `src/DataObjects/AiCreatorData.php`
- Create: `src/DataObjects/AiImageData.php`

These are typed value objects that allow `GenerateAiLayoutAction` and `GenerateAiImageAction` to be called programmatically without a UI.

- [x] **Step 1: Create `AiCreatorData`**

```php
<?php

declare(strict_types=1);

namespace Capell\Assistant\DataObjects;

final readonly class AiCreatorData
{
    public function __construct(
        public int $siteId,
        public int $userId,
        public string $intent,
        public int $pageCount = 1,
        public ?string $tone = null,
        public ?string $industry = null,
        public ?string $targetAudience = null,
        public ?string $brandVoiceNotes = null,
        public ?int $existingSessionId = null,
    ) {}
}
```

- [x] **Step 2: Create `AiImageData`**

```php
<?php

declare(strict_types=1);

namespace Capell\Assistant\DataObjects;

final readonly class AiImageData
{
    /**
     * @param  array<string, string>  $contextFields  e.g. ['title' => '...', 'body' => '...']
     */
    public function __construct(
        public string $prompt,
        public array $contextFields = [],
        public string $size = '1024x1024',
        public ?string $model = null,
        public ?string $provider = null,
    ) {}
}
```

- [x] **Step 3: Commit**

```bash
git add src/DataObjects/AiCreatorData.php src/DataObjects/AiImageData.php
git commit -m "feat: add AiCreatorData and AiImageData DTOs"
```

---

## Task 2: Add AI Creator prompts to config

**Files:**

- Modify: `config/capell-assistant.php`

- [x] **Step 1: Add prompts for AI Creator layout generation**

In `config/capell-assistant.php`, add to the `'prompts'` array:

```php
'ai_creator_layout' => [
    'system' => <<<'PROMPT'
You are an expert CMS content architect. Your job is to propose a structured page layout composed of named section types.

RULES:
- Propose sections as a JSON array only — no prose, no HTML, no markdown outside the JSON.
- Each section must map to one of the registered section types provided to you.
- Output ONLY original content. Never reproduce real brand copy, logos, or copyrighted material.
- All text values in the output are placeholder suggestions, clearly labelled as such.
- Image fields must be set to null — images are handled separately.
- Maximum 8 sections per page.

Respond with a JSON array of section objects in this exact shape:
[
  {
    "section_type": "<registered key>",
    "fields": {
      "<field_name>": "<placeholder text>"
    },
    "ai_metadata": {
      "ai_placeholder": true
    }
  }
]
PROMPT,
    'user_template' => <<<'PROMPT'
Page intent: {{intent}}
Tone: {{tone}}
Industry: {{industry}}
Target audience: {{target_audience}}

Available section types:
{{section_types}}

Brand voice notes: {{brand_voice_notes}}

Propose a layout. Respond with JSON only.
PROMPT,
],

'ai_creator_clarify' => [
    'system' => 'You are a friendly CMS assistant helping a user build a page. Ask ONE short clarifying question to help you understand their intent better. Keep it concise. Do not ask about tone or brand — those are handled separately.',
    'user_template' => 'The user wants to create: {{intent}}. What single question would help you propose a better layout? Reply with just the question, nothing else.',
],

'ai_image_generation' => [
    'system' => 'You are an expert at writing image generation prompts. Given context about a page, write a concise, vivid image generation prompt. Focus on visual composition, mood, and subject. No text in the image.',
    'user_template' => 'Generate a professional image for: {{context}}. Style: {{style}}. Size ratio: {{size}}. Respond with just the image generation prompt.',
],
```

- [x] **Step 2: Commit**

```bash
git add config/capell-assistant.php
git commit -m "feat: add AI Creator prompt templates to config"
```

---

## Task 3: Build `AiCreatorPipeline`

**Files:**

- Create: `src/Support/Pipelines/AiCreatorPipeline.php`

The pipeline stages:

1. Load or create `AiCreatorSession`
2. Load `AiCreatorContext` (brand/tone) for the site
3. Call Prism for layout generation
4. Parse and validate JSON response
5. Persist generated sections to session

- [x] **Step 1: Create `AiCreatorPipeline`**

````php
<?php

declare(strict_types=1);

namespace Capell\Assistant\Support\Pipelines;

use Capell\Assistant\DataObjects\AiCreatorData;
use Capell\Assistant\Models\AIGenerationHistory;
use Capell\Assistant\Models\AiCreatorContext;
use Capell\Assistant\Models\AiCreatorSession;
use Capell\Assistant\Support\AiRateLimiter;
use Capell\Assistant\Support\AiResponse;
use Capell\Assistant\Support\PrismProvider;
use Capell\Assistant\Support\PromptRepository;
use Capell\Assistant\Support\SectionRegistry;
use Illuminate\Pipeline\Pipeline;
use InvalidArgumentException;

class AiCreatorPipeline
{
    public function __construct(
        private readonly PromptRepository $prompts,
        private readonly PrismProvider $provider,
        private readonly AiRateLimiter $rateLimiter,
        private readonly SectionRegistry $sectionRegistry,
    ) {}

    /**
     * @return array<int, array<string, mixed>>  The proposed sections array
     */
    public function execute(AiCreatorData $data): array
    {
        $payload = ['data' => $data, 'sections' => [], 'session' => null, 'context' => null, 'response' => null];

        $result = resolve(Pipeline::class)
            ->send($payload)
            ->through([
                fn (array $p, callable $next): array => $this->loadOrCreateSession($p, $next),
                fn (array $p, callable $next): array => $this->loadContext($p, $next),
                fn (array $p, callable $next): array => $this->checkRateLimit($p, $next),
                fn (array $p, callable $next): array => $this->executeAiCall($p, $next),
                fn (array $p, callable $next): array => $this->parseSections($p, $next),
                fn (array $p, callable $next): array => $this->persistResult($p, $next),
            ])
            ->thenReturn();

        return $result['sections'];
    }

    private function loadOrCreateSession(array $payload, callable $next): array
    {
        /** @var AiCreatorData $data */
        $data = $payload['data'];

        if ($data->existingSessionId !== null) {
            $session = AiCreatorSession::findOrFail($data->existingSessionId);
        } else {
            $session = AiCreatorSession::create([
                'site_id' => $data->siteId,
                'user_id' => $data->userId,
                'status'  => 'generating',
                'stage'   => 1,
                'intent'  => $data->intent,
            ]);
        }

        $payload['session'] = $session;

        return $next($payload);
    }

    private function loadContext(array $payload, callable $next): array
    {
        /** @var AiCreatorData $data */
        $data = $payload['data'];

        $payload['context'] = AiCreatorContext::where('site_id', $data->siteId)->first();

        return $next($payload);
    }

    private function checkRateLimit(array $payload, callable $next): array
    {
        $this->rateLimiter->check((string) $payload['data']->userId);

        return $next($payload);
    }

    private function executeAiCall(array $payload, callable $next): array
    {
        /** @var AiCreatorData $data */
        $data = $payload['data'];
        /** @var AiCreatorContext|null $context */
        $context = $payload['context'];

        $prompt = $this->prompts->get('ai_creator_layout');

        throw_unless($prompt, InvalidArgumentException::class, 'Missing ai_creator_layout prompt');

        $userMessage = strtr($prompt['user_template'], [
            '{{intent}}'           => $data->intent,
            '{{tone}}'             => $data->tone ?? $context?->tone ?? 'professional',
            '{{industry}}'         => $data->industry ?? $context?->industry ?? 'general',
            '{{target_audience}}'  => $data->targetAudience ?? $context?->target_audience ?? 'general audience',
            '{{section_types}}'    => $this->sectionRegistry->forAi(),
            '{{brand_voice_notes}}' => $data->brandVoiceNotes ?? $context?->brand_voice_notes ?? 'none',
        ]);

        $response = $this->provider->chat([
            'model'    => config('capell-assistant.features.ai_creator.model', 'gpt-4o'),
            'messages' => [
                ['role' => 'system', 'content' => $prompt['system']],
                ['role' => 'user', 'content' => $userMessage],
            ],
        ]);

        $payload['response'] = $response;

        return $next($payload);
    }

    private function parseSections(array $payload, callable $next): array
    {
        /** @var AiResponse $response */
        $response = $payload['response'];

        $content = trim($response->content);

        // Strip markdown code fences if present
        $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
        $content = preg_replace('/\s*```$/', '', $content);

        $decoded = json_decode($content, true);

        throw_unless(
            is_array($decoded) && array_is_list($decoded),
            InvalidArgumentException::class,
            'AI response was not a valid JSON array of sections: ' . $content,
        );

        $payload['sections'] = $decoded;

        return $next($payload);
    }

    private function persistResult(array $payload, callable $next): array
    {
        /** @var AiCreatorSession $session */
        $session = $payload['session'];
        /** @var AiResponse $response */
        $response = $payload['response'];

        $history = AIGenerationHistory::create([
            'action'            => 'ai_creator_layout',
            'model'             => $response->model,
            'input'             => $payload['data']->intent,
            'output'            => $response->content,
            'prompt_tokens'     => $response->metadata['prompt_tokens'] ?? 0,
            'completion_tokens' => $response->metadata['completion_tokens'] ?? 0,
            'total_tokens'      => $response->tokensUsed,
            'duration'          => $response->duration,
        ]);

        $session->update([
            'status'          => 'review',
            'stage'           => 3,
            'layout_proposal' => $payload['sections'],
            'ai_history_id'   => $history->id,
        ]);

        $payload['session'] = $session->fresh();

        return $next($payload);
    }
}
````

- [x] **Step 2: Commit**

```bash
git add src/Support/Pipelines/AiCreatorPipeline.php
git commit -m "feat: add AiCreatorPipeline for layout generation"
```

---

## Task 4: Build `GenerateAiLayoutAction`

**Files:**

- Create: `src/Actions/GenerateAiLayoutAction.php`
- Create: `tests/Unit/Actions/GenerateAiLayoutActionTest.php`

- [x] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Capell\Assistant\Actions\GenerateAiLayoutAction;
use Capell\Assistant\DataObjects\AiCreatorData;
use Capell\Assistant\Models\AiCreatorSession;
use Capell\Assistant\Support\Pipelines\AiCreatorPipeline;

it('returns sections array from pipeline', function (): void {
    $sections = [
        ['section_type' => 'hero-fullwidth', 'fields' => ['headline' => 'Welcome'], 'ai_metadata' => ['ai_placeholder' => true]],
    ];

    $pipeline = Mockery::mock(AiCreatorPipeline::class);
    $pipeline->shouldReceive('execute')->once()->andReturn($sections);

    $action = new GenerateAiLayoutAction($pipeline);

    $data = new AiCreatorData(
        siteId: 1,
        userId: 2,
        intent: 'Build a homepage',
    );

    $result = $action->handle($data);

    expect($result)->toBe($sections);
});
```

- [x] **Step 2: Run the test to verify failure**

```bash
./vendor/bin/pest tests/Unit/Actions/GenerateAiLayoutActionTest.php --no-coverage
```

Expected: FAIL — `GenerateAiLayoutAction` not found.

- [x] **Step 3: Implement `GenerateAiLayoutAction`**

```php
<?php

declare(strict_types=1);

namespace Capell\Assistant\Actions;

use Capell\Assistant\DataObjects\AiCreatorData;
use Capell\Assistant\Events\AiGenerationCompleted;
use Capell\Assistant\Events\AiGenerationFailed;
use Capell\Assistant\Events\AiGenerationStarted;
use Capell\Assistant\Support\Pipelines\AiCreatorPipeline;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

class GenerateAiLayoutAction
{
    use AsAction;

    public function __construct(private readonly AiCreatorPipeline $pipeline) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function handle(AiCreatorData $data): array
    {
        $startTime = microtime(true);
        Event::dispatch(new AiGenerationStarted(static::class, [$data]));

        try {
            $sections = $this->pipeline->execute($data);

            Event::dispatch(new AiGenerationCompleted(
                static::class,
                [$data],
                microtime(true) - $startTime,
            ));

            return $sections;
        } catch (Throwable $e) {
            Event::dispatch(new AiGenerationFailed(static::class, [$data], $e));

            throw $e;
        }
    }
}
```

- [x] **Step 4: Run tests to verify they pass**

```bash
./vendor/bin/pest tests/Unit/Actions/GenerateAiLayoutActionTest.php --no-coverage
```

Expected: PASS

- [x] **Step 5: Commit**

```bash
git add src/Actions/GenerateAiLayoutAction.php tests/Unit/Actions/GenerateAiLayoutActionTest.php
git commit -m "feat: add GenerateAiLayoutAction"
```

---

## Task 5: Build `GenerateAiImageAction`

**Files:**

- Create: `src/Actions/GenerateAiImageAction.php`
- Create: `tests/Unit/Actions/GenerateAiImageActionTest.php`

- [x] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Capell\Assistant\Actions\GenerateAiImageAction;
use Capell\Assistant\DataObjects\AiImageData;
use Capell\Assistant\Support\PrismProvider;
use EchoLabs\Prism\Facades\Prism;
use EchoLabs\Prism\Testing\PrismFake;

it('returns an image URL from the AI provider', function (): void {
    $fake = Prism::fake([
        PrismFake::image('https://example.com/generated.jpg'),
    ]);

    $data = new AiImageData(
        prompt: 'A professional hero banner for a law firm',
        contextFields: ['title' => 'Contact Us'],
        size: '1024x1024',
    );

    $action = new GenerateAiImageAction;
    $url = $action->handle($data);

    expect($url)->toBe('https://example.com/generated.jpg');
    $fake->assertCallCount(1);
});
```

- [x] **Step 2: Run the test to verify failure**

```bash
./vendor/bin/pest tests/Unit/Actions/GenerateAiImageActionTest.php --no-coverage
```

Expected: FAIL — `GenerateAiImageAction` not found.

- [x] **Step 3: Implement `GenerateAiImageAction`**

```php
<?php

declare(strict_types=1);

namespace Capell\Assistant\Actions;

use Capell\Assistant\DataObjects\AiImageData;
use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Facades\Prism;
use Lorisleiva\Actions\Concerns\AsAction;

class GenerateAiImageAction
{
    use AsAction;

    public function handle(AiImageData $data): string
    {
        $providerName = $data->provider ?? config('capell-assistant.prism.image_provider', 'openai');
        $model = $data->model ?? config('capell-assistant.prism.image_model', 'dall-e-3');
        $size = $data->size ?? config('capell-assistant.prism.image_size', '1024x1024');

        $provider = $this->resolveProvider($providerName);

        $response = Prism::image()
            ->using($provider, $model)
            ->withPrompt($data->prompt)
            ->generate();

        return $response->images[0]->url ?? $response->images[0]->base64 ?? '';
    }

    private function resolveProvider(string $name): Provider
    {
        return match (strtolower($name)) {
            'anthropic' => Provider::Anthropic,
            'gemini', 'google' => Provider::Gemini,
            default => Provider::OpenAI,
        };
    }
}
```

- [x] **Step 4: Run the test to verify it passes**

```bash
./vendor/bin/pest tests/Unit/Actions/GenerateAiImageActionTest.php --no-coverage
```

Expected: PASS

- [x] **Step 5: Commit**

```bash
git add src/Actions/GenerateAiImageAction.php tests/Unit/Actions/GenerateAiImageActionTest.php
git commit -m "feat: add GenerateAiImageAction"
```

---

## Task 6: Build `SubmitAiCreatorDraftAction`

**Files:**

- Create: `src/Actions/SubmitAiCreatorDraftAction.php`
- Create: `tests/Unit/Actions/SubmitAiCreatorDraftActionTest.php`

This action calls admin's `SubmitForApprovalAction` directly (assistant already requires admin as a composer dependency). It populates `ai_creator_sessions.workspace_id` with the result.

- [x] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Capell\Admin\Filament\Resources\Workspaces\Actions\SubmitForApprovalAction;
use Capell\Assistant\Actions\SubmitAiCreatorDraftAction;
use Capell\Assistant\Models\AiCreatorSession;

it('marks session as submitted and records workspace id', function (): void {
    $session = AiCreatorSession::factory()->create([
        'status' => 'review',
        'layout_proposal' => [['section_type' => 'hero', 'fields' => ['headline' => 'Test']]],
    ]);

    // Mock the admin SubmitForApprovalAction to return a fake workspace ID
    $mockWorkspace = new stdClass;
    $mockWorkspace->id = 99;

    $submitAction = Mockery::mock(SubmitForApprovalAction::class);
    $submitAction->shouldReceive('handle')
        ->once()
        ->andReturn($mockWorkspace);

    $action = new SubmitAiCreatorDraftAction($submitAction);
    $action->handle($session);

    $session->refresh();

    expect($session->status)->toBe('submitted')
        ->and($session->workspace_id)->toBe(99);
});
```

- [x] **Step 2: Run the test to verify failure**

```bash
./vendor/bin/pest tests/Unit/Actions/SubmitAiCreatorDraftActionTest.php --no-coverage
```

Expected: FAIL — `SubmitAiCreatorDraftAction` not found.

- [x] **Step 3: Implement `SubmitAiCreatorDraftAction`**

```php
<?php

declare(strict_types=1);

namespace Capell\Assistant\Actions;

use Capell\Admin\Filament\Resources\Workspaces\Actions\SubmitForApprovalAction;
use Capell\Assistant\Models\AiCreatorSession;
use Lorisleiva\Actions\Concerns\AsAction;

class SubmitAiCreatorDraftAction
{
    use AsAction;

    public function __construct(
        private readonly SubmitForApprovalAction $submitForApproval,
    ) {}

    public function handle(AiCreatorSession $session): void
    {
        $draft = [
            'sections'    => $session->layout_proposal ?? [],
            'intent'      => $session->intent,
            'ai_origin'   => true,
            'ai_session_id' => $session->id,
        ];

        $workspace = $this->submitForApproval->handle(
            content: $draft,
            metadata: ['ai_origin' => true, 'ai_session_id' => $session->id],
        );

        $session->update([
            'status'       => 'submitted',
            'workspace_id' => $workspace->id,
        ]);
    }
}
```

> **Note:** The exact signature of `SubmitForApprovalAction::handle()` must be verified against the admin package source at `/Users/ben/Sites/packages/capell/capell-4/packages/admin/src/Filament/Resources/Workspaces/Actions/SubmitForApprovalAction.php`. Adjust the call arguments to match what that action actually accepts.

- [x] **Step 4: Verify test passes**

```bash
./vendor/bin/pest tests/Unit/Actions/SubmitAiCreatorDraftActionTest.php --no-coverage
```

Expected: PASS

- [x] **Step 5: Commit**

```bash
git add src/Actions/SubmitAiCreatorDraftAction.php tests/Unit/Actions/SubmitAiCreatorDraftActionTest.php
git commit -m "feat: add SubmitAiCreatorDraftAction wiring AI Creator to workspace approval"
```

---

## Task 7: Build `AiCreatorAction` (Filament wizard)

**Files:**

- Create: `src/Filament/Actions/AiCreatorAction.php`

This is a Filament `Action` using a `Wizard` form component with 4 steps: Describe → Context → Layout → Review. On final submission it calls `GenerateAiLayoutAction` then `SubmitAiCreatorDraftAction`.

- [x] **Step 1: Create `AiCreatorAction`**

```php
<?php

declare(strict_types=1);

namespace Capell\Assistant\Filament\Actions;

use Capell\Assistant\Actions\GenerateAiLayoutAction;
use Capell\Assistant\Actions\SubmitAiCreatorDraftAction;
use Capell\Assistant\DataObjects\AiCreatorData;
use Capell\Assistant\Models\AiCreatorContext;
use Capell\Assistant\Models\AiCreatorSession;
use Capell\Assistant\Policies\AiCreatorPolicy;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Throwable;

class AiCreatorAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->name('ai-creator')
            ->label('AI Creator')
            ->icon('heroicon-o-sparkles')
            ->slideOver()
            ->visible(fn (): bool => app(AiCreatorPolicy::class)->isEnabledFor(
                $this->resolveSiteFromRecord()
            ))
            ->form(fn (): array => $this->buildWizardForm())
            ->action(fn (array $data): void => $this->runCreator($data));
    }

    private function buildWizardForm(): array
    {
        return [
            Wizard::make([
                Wizard\Step::make('Describe')
                    ->label('What are we building?')
                    ->schema([
                        Textarea::make('intent')
                            ->label('Describe the page you want to create')
                            ->placeholder('e.g. A homepage for a law firm with a hero, services section, and contact form')
                            ->required()
                            ->rows(4),
                        Select::make('page_count')
                            ->label('How many pages?')
                            ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5'])
                            ->default(1)
                            ->visible(fn (): bool => $this->isMountedOnSiteResource()),
                    ]),

                Wizard\Step::make('Brand')
                    ->label('Brand & tone')
                    ->schema(fn (): array => $this->buildBrandStep()),

                Wizard\Step::make('Layout')
                    ->label('Proposed layout')
                    ->schema([
                        Repeater::make('layout_preview')
                            ->label('AI-proposed sections (reorder or remove as needed)')
                            ->schema([
                                TextInput::make('section_type')->label('Section type')->disabled(),
                                Textarea::make('fields_preview')->label('Fields preview')->disabled(),
                            ])
                            ->addable(false)
                            ->reorderable()
                            ->columns(2),
                    ]),

                Wizard\Step::make('Review')
                    ->label('Review & submit')
                    ->schema([
                        Textarea::make('review_notes')
                            ->label('Notes for reviewer (optional)')
                            ->rows(3),
                    ]),
            ])->submitAction(
                \Filament\Forms\Components\Actions\Action::make('submit')
                    ->label('Submit for Review')
                    ->color('primary')
            ),
        ];
    }

    private function buildBrandStep(): array
    {
        $siteId = $this->resolveSiteId();
        $existingContext = $siteId ? AiCreatorContext::where('site_id', $siteId)->first() : null;

        return [
            Select::make('tone')
                ->label('Tone of voice')
                ->options([
                    'professional'  => 'Professional & formal',
                    'friendly'      => 'Warm & approachable',
                    'playful'       => 'Fun & playful',
                    'authoritative' => 'Authoritative & expert',
                ])
                ->default($existingContext?->tone ?? 'professional')
                ->required(),

            TextInput::make('industry')
                ->label('Industry / sector')
                ->default($existingContext?->industry ?? '')
                ->placeholder('e.g. Legal, Healthcare, E-commerce'),

            Textarea::make('target_audience')
                ->label('Target audience')
                ->default($existingContext?->target_audience ?? '')
                ->placeholder('e.g. Small business owners aged 30-50')
                ->rows(2),

            Textarea::make('brand_voice_notes')
                ->label('Brand voice notes (optional)')
                ->default($existingContext?->brand_voice_notes ?? '')
                ->placeholder('e.g. We never use jargon. Always end with a call to action.')
                ->rows(2),
        ];
    }

    private function runCreator(array $data): void
    {
        $siteId = $this->resolveSiteId() ?? 0;
        $userId = (int) Auth::id();

        // Persist brand context for next time (skip questions)
        AiCreatorContext::updateOrCreate(
            ['site_id' => $siteId],
            [
                'tone'             => $data['tone'] ?? 'professional',
                'industry'         => $data['industry'] ?? '',
                'target_audience'  => $data['target_audience'] ?? null,
                'brand_voice_notes' => $data['brand_voice_notes'] ?? null,
            ],
        );

        try {
            $creatorData = new AiCreatorData(
                siteId: $siteId,
                userId: $userId,
                intent: $data['intent'],
                pageCount: (int) ($data['page_count'] ?? 1),
                tone: $data['tone'] ?? null,
                industry: $data['industry'] ?? null,
                targetAudience: $data['target_audience'] ?? null,
                brandVoiceNotes: $data['brand_voice_notes'] ?? null,
            );

            $sections = app(GenerateAiLayoutAction::class)->handle($creatorData);

            $session = AiCreatorSession::where([
                'site_id' => $siteId,
                'user_id' => $userId,
                'status'  => 'review',
            ])->latest()->first();

            if ($session) {
                app(SubmitAiCreatorDraftAction::class)->handle($session);

                Notification::make()
                    ->title('Layout submitted for review')
                    ->body('Your AI-generated layout has been sent to the workspace for approval.')
                    ->success()
                    ->send();
            }
        } catch (Throwable $e) {
            Notification::make()
                ->title('AI Creator failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function resolveSiteFromRecord(): object
    {
        $record = $this->getRecord();

        if ($record && method_exists($record, 'getSite')) {
            return $record->getSite();
        }

        return (object) ['ai_creator_enabled' => null];
    }

    private function resolveSiteId(): ?int
    {
        $record = $this->getRecord();

        if ($record && method_exists($record, 'getSiteId')) {
            return $record->getSiteId();
        }

        if ($record && isset($record->site_id)) {
            return (int) $record->site_id;
        }

        if ($record && isset($record->id) && str_contains(get_class($record), 'Site')) {
            return (int) $record->id;
        }

        return null;
    }

    private function isMountedOnSiteResource(): bool
    {
        $record = $this->getRecord();

        return $record !== null && str_contains(get_class($record), 'Site');
    }
}
```

- [x] **Step 2: Commit**

```bash
git add src/Filament/Actions/AiCreatorAction.php
git commit -m "feat: add AiCreatorAction Filament wizard"
```

---

## Task 8: Build `AiImageGeneratorAction`

**Files:**

- Create: `src/Filament/Actions/AiImageGeneratorAction.php`

A reusable inline Filament action that composes a prompt from nearby form field context, generates an image, and updates the parent field directly on Accept.

- [x] **Step 1: Create `AiImageGeneratorAction`**

```php
<?php

declare(strict_types=1);

namespace Capell\Assistant\Filament\Actions;

use Capell\Assistant\Actions\GenerateAiImageAction;
use Capell\Assistant\DataObjects\AiImageData;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Throwable;

class AiImageGeneratorAction extends Action
{
    /**
     * @param  array<string, string>  $contextFieldKeys  Keys of sibling Filament fields to read as context
     */
    public static function make(string $name = 'generate-ai-image', array $contextFieldKeys = []): static
    {
        return parent::make($name)
            ->label('Generate with AI')
            ->icon('heroicon-o-sparkles')
            ->modalHeading('AI Image Generator')
            ->modalSubmitActionLabel('Accept')
            ->form(function (Get $get) use ($contextFieldKeys): array {
                // Compose context string from sibling field values
                $contextParts = [];
                foreach ($contextFieldKeys as $key => $label) {
                    $value = $get($key);
                    if (filled($value)) {
                        $contextParts[] = "{$label}: {$value}";
                    }
                }
                $autoPrompt = implode('. ', $contextParts);

                return [
                    Textarea::make('prompt')
                        ->label('Describe the image')
                        ->default($autoPrompt)
                        ->required()
                        ->rows(3)
                        ->helperText('Edit to refine, then click Generate.'),

                    \Filament\Forms\Components\Actions::make([
                        \Filament\Forms\Components\Actions\Action::make('generate_preview')
                            ->label('Generate')
                            ->color('gray')
                            ->action(function (array $state, Set $set): void {
                                try {
                                    $data = new AiImageData(
                                        prompt: $state['prompt'],
                                        size: config('capell-assistant.prism.image_size', '1024x1024'),
                                    );

                                    $url = app(GenerateAiImageAction::class)->handle($data);
                                    $set('preview_url', $url);
                                } catch (Throwable $e) {
                                    Notification::make()
                                        ->title('Image generation failed')
                                        ->body($e->getMessage())
                                        ->danger()
                                        ->send();
                                }
                            }),
                    ]),

                    ViewField::make('preview_url')
                        ->view('capell-assistant::filament.fields.image-preview')
                        ->visible(fn (Get $get): bool => filled($get('preview_url'))),
                ];
            })
            ->action(function (array $data, Set $set): void {
                $url = $data['preview_url'] ?? null;

                if (! $url) {
                    Notification::make()
                        ->title('No image generated yet')
                        ->warning()
                        ->send();

                    return;
                }

                // Update the parent field with the generated image URL
                $set('../../' . $name, $url);

                Notification::make()
                    ->title('Image applied')
                    ->success()
                    ->send();
            });
    }
}
```

- [x] **Step 2: Create the image preview Blade view**

Create `resources/views/filament/fields/image-preview.blade.php`:

```blade
@php
    $url = $getState();
@endphp

@if ($url)
    <div class="mt-2">
        <img
            src="{{ $url }}"
            alt="AI Generated Image Preview"
            class="max-w-full rounded-lg border border-gray-200 shadow-sm dark:border-gray-700"
            style="max-height: 300px; object-fit: cover"
        />
    </div>
@endif
```

- [x] **Step 3: Commit**

```bash
git add src/Filament/Actions/AiImageGeneratorAction.php resources/views/filament/fields/image-preview.blade.php
git commit -m "feat: add AiImageGeneratorAction for inline AI image generation"
```

---

## Task 9: Create extender classes and wire into service provider

**Files:**

- Create: `src/Support/Admin/AiCreatorPageExtender.php`
- Create: `src/Support/Admin/AiCreatorSiteExtender.php`
- Modify: `src/Providers/AssistantServiceProvider.php`

- [x] **Step 1: Create `AiCreatorPageExtender`**

```php
<?php

declare(strict_types=1);

namespace Capell\Assistant\Support\Admin;

use Capell\Admin\Contracts\Extenders\PageHeaderActionExtender;
use Capell\Assistant\Filament\Actions\AiCreatorAction;
use Filament\Actions\Action;

class AiCreatorPageExtender implements PageHeaderActionExtender
{
    /** @return array<int, Action> */
    public function actions(): array
    {
        return [AiCreatorAction::make()];
    }
}
```

- [x] **Step 2: Create `AiCreatorSiteExtender`**

```php
<?php

declare(strict_types=1);

namespace Capell\Assistant\Support\Admin;

use Capell\Admin\Contracts\Extenders\SiteHeaderActionExtender;
use Capell\Assistant\Filament\Actions\AiCreatorAction;
use Filament\Actions\Action;

class AiCreatorSiteExtender implements SiteHeaderActionExtender
{
    /** @return array<int, Action> */
    public function actions(): array
    {
        return [AiCreatorAction::make()];
    }
}
```

- [x] **Step 3: Register extenders in `AssistantServiceProvider::registerAdminExtenders()`**

Add the following to the existing `registerAdminExtenders()` method in `AssistantServiceProvider`:

```php
use Capell\Admin\Contracts\Extenders\PageHeaderActionExtender;
use Capell\Admin\Contracts\Extenders\SiteHeaderActionExtender;
use Capell\Assistant\Support\Admin\AiCreatorPageExtender;
use Capell\Assistant\Support\Admin\AiCreatorSiteExtender;

// Inside registerAdminExtenders():
$this->app->tag([
    AiCreatorPageExtender::class,
], PageHeaderActionExtender::TAG);

$this->app->tag([
    AiCreatorSiteExtender::class,
], SiteHeaderActionExtender::TAG);
```

- [x] **Step 4: Register `AiCreatorPipeline` as a singleton in `registerAiServices()`**

```php
use Capell\Assistant\Support\Pipelines\AiCreatorPipeline;

$this->app->singleton(AiCreatorPipeline::class, fn (Application $app): AiCreatorPipeline => new AiCreatorPipeline(
    $app->make(PromptRepository::class),
    $app->make(PrismProvider::class),
    $app->make(AiRateLimiter::class),
    $app->make(SectionRegistry::class),
));
```

- [x] **Step 5: Run all tests**

```bash
./vendor/bin/pest --no-coverage
```

Expected: All PASS

- [x] **Step 6: Commit**

```bash
git add src/Support/Admin/AiCreatorPageExtender.php src/Support/Admin/AiCreatorSiteExtender.php src/Providers/AssistantServiceProvider.php
git commit -m "feat: register AiCreatorAction extenders on page and site resources"
```

---

## Task 10: Smoke test end-to-end in a browser

Before opening a PR, verify the feature works in the actual Filament admin.

- [x] **Step 1: Run migrations**

```bash
php artisan migrate
```

Expected: All three new AI Creator migrations run cleanly.

- [x] **Step 2: Set env variables**

In the host app's `.env`, ensure:

```
AI_PROVIDER=openai
AI_MODEL=gpt-4o
AI_API_KEY=sk-...  (or configure via Filament settings page)
```

- [x] **Step 3: Open admin and navigate to a Page record**

The page edit/list header should now show an "AI Creator" button (with sparkles icon).

- [x] **Step 4: Click "AI Creator" and complete the wizard**

Walk through all 4 steps:

1. Enter intent: "A homepage for a software company"
2. Brand step: verify tone/industry fields pre-fill from context if one exists
3. Layout step: AI returns proposed sections — verify JSON parses and displays
4. Review: click "Submit for Review"

Expected: Notification "Layout submitted for review", workspace entry created.

- [x] **Step 5: Navigate to a Site record**

Verify "AI Creator" button also appears, and that the wizard shows the "How many pages?" field on step 1.

- [x] **Step 6: Test the image generator on an image field**

On any resource that uses `AiImageGeneratorAction`, click "Generate with AI", enter a prompt, click Generate, verify preview appears, click Accept.

Expected: Image URL written back to the parent field.

- [x] **Step 7: Commit any fixes found**

```bash
git add -p
git commit -m "fix: smoke test corrections for AI Creator wizard"
```

---

## Self-Review Checklist

- [x] DTOs: `AiCreatorData`, `AiImageData` — Task 1
- [x] Prompts: `ai_creator_layout`, `ai_creator_clarify`, `ai_image_generation` — Task 2
- [x] Pipeline: `AiCreatorPipeline` with 6 stages — Task 3
- [x] Layout generation: `GenerateAiLayoutAction` dispatches events, delegates to pipeline — Task 4
- [x] Image generation: `GenerateAiImageAction` uses Prism image API — Task 5
- [x] Workspace integration: `SubmitAiCreatorDraftAction` calls admin `SubmitForApprovalAction` directly — Task 6
- [x] Filament wizard: `AiCreatorAction` with 4 Wizard steps, persists brand context, shows on page + site — Task 7
- [x] Inline image action: `AiImageGeneratorAction` reads context fields, shows editable prompt + preview — Task 8
- [x] Extenders: `AiCreatorPageExtender`, `AiCreatorSiteExtender` tagged against admin interfaces — Task 9
- [x] Smoke test: Task 10
- [x] Type names consistent throughout:
    - `AiCreatorData` (not `AiCreatorDataObject`)
    - `GenerateAiLayoutAction::handle(AiCreatorData $data): array`
    - `GenerateAiImageAction::handle(AiImageData $data): string`
    - `SubmitAiCreatorDraftAction::handle(AiCreatorSession $session): void`
    - `AiCreatorPipeline::execute(AiCreatorData $data): array`
- [x] No TBDs or placeholders
- [x] **Note on `SubmitForApprovalAction`:** Task 6 Step 3 includes a note that the exact call signature must be verified against the admin source before running. Do this before executing Task 6.
