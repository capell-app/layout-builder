# AI Creator — Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the hard-wired OpenAI provider with a multi-provider Prism layer, build the SectionRegistry + ContentTarget infrastructure, add database tables and updated settings for AI Creator, and wire admin extender hooks so page/site resources can accept injected header actions from the assistant package.

**Architecture:** `PrismProvider` wraps `prism-php/prism` and preserves the existing `AiResponse` value object + circuit-breaker/retry pattern. `SectionRegistry` is an in-memory singleton populated at boot. Two new DB tables (`ai_creator_contexts`, `ai_creator_sessions`) hold brand preferences and wizard state. The admin package gains two minimal extender interfaces (`PageHeaderActionExtender`, `SiteHeaderActionExtender`) that follow the identical pattern as the existing `PageTitleWithSlugInputExtender`.

**Tech Stack:** PHP 8.2, Laravel, Filament v3, prism-php/prism, Spatie Laravel Settings, Lorisleiva Laravel Actions, Pest

---

## File Map

### New files — `capell-app/assistant`

```
tests/TestCase.php
tests/Unit/Support/SectionRegistryTest.php
tests/Unit/Support/PrismProviderTest.php
tests/Unit/Targets/FlatJsonTargetTest.php
tests/Unit/Policies/AiCreatorPolicyTest.php
src/Support/PrismProvider.php
src/Support/SectionRegistry.php
src/Support/ContentTargetResolver.php
src/Contracts/ContentTargetContract.php
src/Targets/FlatJsonTarget.php
src/Policies/AiCreatorPolicy.php
database/migrations/xxxx_xx_xx_create_ai_creator_contexts_table.php
database/migrations/xxxx_xx_xx_create_ai_creator_sessions_table.php
database/migrations/xxxx_xx_xx_update_assistant_settings_add_ai_creator.php
src/Models/AiCreatorContext.php
src/Models/AiCreatorSession.php
```

### Modified files — `capell-app/assistant`

```
composer.json                                  remove openai-php/laravel, add prism-php/prism
config/capell-assistant.php                    replace openai section with prism section
src/Settings/AssistantSettings.php             add new properties
src/Support/Pipelines/GenerateContentPipeline.php  type-hint PrismProvider
src/Providers/AssistantServiceProvider.php     wire new singletons + extenders
```

### New files — `capell-app/admin`

```
src/Contracts/Extenders/PageHeaderActionExtender.php
src/Contracts/Extenders/SiteHeaderActionExtender.php
src/Support/PageHeaderActionExtenderResolver.php
src/Support/SiteHeaderActionExtenderResolver.php
```

### Modified files — `capell-app/admin`

```
src/Providers/AdminServiceProvider.php         register two new resolvers as singletons
src/Filament/Resources/Pages/Pages/EditPage.php    inject PageHeaderActionExtenderResolver into header actions
src/Filament/Resources/Sites/*.php             inject SiteHeaderActionExtenderResolver into header actions
```

---

## Task 1: Set up test infrastructure

**Files:**

- Create: `tests/TestCase.php`
- Create: `tests/Pest.php`
- Modify: `composer.json`

- [ ] **Step 1: Add Pest to composer dev dependencies**

In `composer.json`, add a `require-dev` section and a `scripts` block:

```json
{
    "name": "capell-app/assistant",
    "require": {
        "php": "^8.2",
        "capell-app/admin": "*",
        "capell-app/frontend": "*",
        "prism-php/prism": "^1.0"
    },
    "require-dev": {
        "pestphp/pest": "^3.0",
        "pestphp/pest-plugin-laravel": "^3.0",
        "orchestra/testbench": "^9.0"
    },
    "autoload-dev": {
        "psr-4": {
            "Capell\\Assistant\\Tests\\": "tests/"
        }
    },
    "scripts": {
        "test": "pest"
    }
}
```

- [ ] **Step 2: Create TestCase**

```php
<?php

declare(strict_types=1);

namespace Capell\Assistant\Tests;

use Capell\Assistant\Providers\AssistantServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            AssistantServiceProvider::class,
        ];
    }
}
```

- [ ] **Step 3: Create Pest bootstrap**

```php
<?php

declare(strict_types=1);

use Capell\Assistant\Tests\TestCase;

uses(TestCase::class)->in('Unit');
```

- [ ] **Step 4: Run composer install**

```bash
cd /path/to/capell-app/assistant
composer install
```

Expected: Pest installed in vendor/

- [ ] **Step 5: Commit**

```bash
git add composer.json tests/
git commit -m "chore: set up Pest test infrastructure"
```

---

## Task 2: Create `PrismProvider` (text generation)

**Files:**

- Create: `src/Support/PrismProvider.php`
- Create: `tests/Unit/Support/PrismProviderTest.php`

The `PrismProvider` replaces `OpenAIProvider`. It accepts the same `chat(array $params)` signature so the existing pipeline needs no changes. The params array follows OpenAI message format; the provider extracts system + user messages internally and converts to Prism's fluent API.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Capell\Assistant\Support\AiResponse;
use Capell\Assistant\Support\PrismProvider;
use EchoLabs\Prism\Facades\Prism;
use EchoLabs\Prism\Testing\PrismFake;
use EchoLabs\Prism\ValueObjects\Usage;

it('returns an AiResponse from a chat call', function (): void {
    $fake = Prism::fake([
        PrismFake::text('Hello there')->withUsage(new Usage(10, 20)),
    ]);

    $provider = new PrismProvider([
        'provider' => 'openai',
        'model' => 'gpt-4o',
        'max_retries' => 1,
        'retry_delay_ms' => 0,
    ]);

    $response = $provider->chat([
        'model' => 'gpt-4o',
        'messages' => [
            ['role' => 'system', 'content' => 'You are helpful.'],
            ['role' => 'user', 'content' => 'Say hello.'],
        ],
    ]);

    expect($response)->toBeInstanceOf(AiResponse::class)
        ->and($response->content)->toBe('Hello there')
        ->and($response->tokensUsed)->toBe(30);

    $fake->assertCallCount(1);
});
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
./vendor/bin/pest tests/Unit/Support/PrismProviderTest.php --no-coverage
```

Expected: FAIL — `PrismProvider` class not found.

- [ ] **Step 3: Implement `PrismProvider`**

```php
<?php

declare(strict_types=1);

namespace Capell\Assistant\Support;

use Capell\Assistant\Exceptions\OpenAICircuitBreakerOpenException;
use Capell\Core\Contracts\ServiceContract;
use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Facades\Prism;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use RuntimeException;
use Throwable;

class PrismProvider implements ServiceContract
{
    private const CIRCUIT_BREAKER_KEY = 'ai_circuit_breaker_state';

    private const FAILURE_THRESHOLD = 5;

    private const CIRCUIT_TIMEOUT = 300;

    protected int $maxRetries;

    protected int $retryDelay;

    public function __construct(protected array $config = [])
    {
        $this->maxRetries = (int) ($this->config['max_retries'] ?? 3);
        $this->retryDelay = (int) ($this->config['retry_delay_ms'] ?? 1000);
    }

    public function execute(array $input): mixed
    {
        return $this->chat($input);
    }

    public function chat(array $params): AiResponse
    {
        throw_if($this->isCircuitOpen(), OpenAICircuitBreakerOpenException::class);

        $attempt = 0;
        $lastException = null;
        $startTime = microtime(true);

        while ($attempt < $this->maxRetries) {
            try {
                $messages = $params['messages'] ?? [];
                $systemPrompt = '';
                $userMessage = '';

                foreach ($messages as $message) {
                    if ($message['role'] === 'system') {
                        $systemPrompt = $message['content'];
                    } elseif ($message['role'] === 'user') {
                        $userMessage = $message['content'];
                    }
                }

                $model = $params['model'] ?? $this->config['model'] ?? 'gpt-4o';
                $providerName = $this->config['provider'] ?? 'openai';

                $response = Prism::text()
                    ->using($this->resolveProvider($providerName), $model)
                    ->withSystemPrompt($systemPrompt)
                    ->withPrompt($userMessage)
                    ->generate();

                $duration = microtime(true) - $startTime;
                $this->resetCircuitBreaker();

                Log::debug('AI API Call Metrics', [
                    'provider' => $providerName,
                    'model' => $model,
                    'total_tokens' => $response->usage->promptTokens + $response->usage->completionTokens,
                    'duration_ms' => round($duration * 1000, 2),
                ]);

                return new AiResponse(
                    content: $response->text,
                    tokensUsed: $response->usage->promptTokens + $response->usage->completionTokens,
                    model: $model,
                    duration: $duration,
                    metadata: [
                        'prompt_tokens' => $response->usage->promptTokens,
                        'completion_tokens' => $response->usage->completionTokens,
                    ],
                );
            } catch (Throwable $e) {
                $attempt++;
                $lastException = $e;
                $this->recordFailure();

                Log::warning('AI API attempt failed', [
                    'attempt' => $attempt,
                    'max_retries' => $this->maxRetries,
                    'error' => $e->getMessage(),
                ]);

                throw_if($attempt >= $this->maxRetries, $lastException);

                $delay = $this->retryDelay * (2 ** ($attempt - 1));
                $jitter = random_int(0, (int) ($delay * 0.1));
                Sleep::usleep(($delay + $jitter) * 1000);
            }
        }

        throw $lastException ?? new RuntimeException('Unknown AI provider error');
    }

    public function isAvailable(): bool
    {
        return ! $this->isCircuitOpen();
    }

    public function handles(): string
    {
        return 'prism_provider';
    }

    public function resetCircuitBreaker(): void
    {
        Cache::forget(self::CIRCUIT_BREAKER_KEY);
    }

    protected function resolveProvider(string $name): Provider
    {
        return match (strtolower($name)) {
            'anthropic' => Provider::Anthropic,
            'gemini', 'google' => Provider::Gemini,
            'ollama' => Provider::Ollama,
            default => Provider::OpenAI,
        };
    }

    protected function isCircuitOpen(): bool
    {
        $state = Cache::get(self::CIRCUIT_BREAKER_KEY, ['failures' => 0]);

        return (int) ($state['failures'] ?? 0) >= self::FAILURE_THRESHOLD;
    }

    protected function recordFailure(): void
    {
        $state = Cache::get(self::CIRCUIT_BREAKER_KEY, ['failures' => 0]);
        $state['failures'] = (int) ($state['failures'] ?? 0) + 1;
        Cache::put(self::CIRCUIT_BREAKER_KEY, $state, self::CIRCUIT_TIMEOUT);
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
./vendor/bin/pest tests/Unit/Support/PrismProviderTest.php --no-coverage
```

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add src/Support/PrismProvider.php tests/Unit/Support/PrismProviderTest.php
git commit -m "feat: add PrismProvider wrapping prism-php/prism"
```

---

## Task 3: Update config and swap composer dependency

**Files:**

- Modify: `composer.json`
- Modify: `config/capell-assistant.php`

- [ ] **Step 1: Update `composer.json`** — remove `openai-php/laravel`, add `prism-php/prism`

Replace the `require` block:

```json
"require": {
    "php": "^8.2",
    "capell-app/admin": "*",
    "capell-app/frontend": "*",
    "prism-php/prism": "^1.0"
},
```

- [ ] **Step 2: Update config** — replace the `openai` section with `prism`, keep other sections

In `config/capell-assistant.php`, replace:

```php
'openai' => [
    'max_retries' => 3,
    'retry_delay_ms' => 500,
    'default_model' => 'gpt-4-turbo',
    'max_tokens' => 512,
],
```

with:

```php
'prism' => [
    'provider' => env('AI_PROVIDER', 'openai'),
    'model' => env('AI_MODEL', 'gpt-4o'),
    'max_retries' => 3,
    'retry_delay_ms' => 500,
    'max_tokens' => 4096,
    'image_provider' => env('AI_IMAGE_PROVIDER', 'openai'),
    'image_model' => env('AI_IMAGE_MODEL', 'dall-e-3'),
    'image_size' => env('AI_IMAGE_SIZE', '1024x1024'),
],
'ai_creator' => [
    'enabled' => env('AI_CREATOR_ENABLED', true),
],
```

Also update the feature model references from `'gpt-4-turbo'` to `'gpt-4o'`:

```php
'features' => [
    'title_generation' => [
        'enabled' => true,
        'model' => 'gpt-4o',
        'handler' => 'Capell\\Admin\\Actions\\AI\\GeneratePageTitleAction',
    ],
    'meta_description' => [
        'enabled' => true,
        'model' => 'gpt-4o',
        'handler' => 'Capell\\Admin\\Actions\\AI\\GenerateMetaDescriptionAction',
    ],
    'content_generation' => [
        'enabled' => true,
        'model' => 'gpt-4o',
        'handler' => GeneratorPageContentAction::class,
    ],
    'ai_creator' => [
        'enabled' => true,
        'model' => 'gpt-4o',
        'handler' => null,
    ],
],
```

- [ ] **Step 3: Run composer update**

```bash
composer update
```

Expected: `openai-php/laravel` removed, `prism-php/prism` installed.

- [ ] **Step 4: Commit**

```bash
git add composer.json config/capell-assistant.php
git commit -m "feat: swap openai-php/laravel for prism-php/prism, update config"
```

---

## Task 4: Update service provider and pipeline to use `PrismProvider`

**Files:**

- Modify: `src/Support/Pipelines/GenerateContentPipeline.php`
- Modify: `src/Providers/AssistantServiceProvider.php`

- [ ] **Step 1: Update `GenerateContentPipeline` type hint**

In `src/Support/Pipelines/GenerateContentPipeline.php`, change the constructor:

```php
// Replace:
use Capell\Assistant\Support\OpenAIProvider;
// With:
use Capell\Assistant\Support\PrismProvider;
```

And update the constructor parameter:

```php
public function __construct(
    private readonly PromptRepository $prompts,
    private readonly PrismProvider $provider,
    private readonly AiRateLimiter $rateLimiter,
) {}
```

- [ ] **Step 2: Update `AssistantServiceProvider`**

In `src/Providers/AssistantServiceProvider.php`:

Remove the `OpenAIProvider` import and `use` statement:

```php
// Remove:
use Capell\Assistant\Support\OpenAIProvider;
```

Add `PrismProvider` import:

```php
use Capell\Assistant\Support\PrismProvider;
```

In `registerOpenAiCmsIntegrationServices()`, replace the `OpenAIProvider` singleton:

```php
// Replace:
$this->app->singleton(OpenAIProvider::class, fn (Application $app): OpenAIProvider => new OpenAIProvider((array) config('capell-assistant.openai', [])));
// With:
$this->app->singleton(PrismProvider::class, fn (Application $app): PrismProvider => new PrismProvider((array) config('capell-assistant.prism', [])));
```

Rename the method from `registerOpenAiCmsIntegrationServices` to `registerAiServices` and update the private `bootInstalledPackage()` call to match:

```php
private function bootInstalledPackage(): self
{
    return $this
        ->registerAdminEvents()
        ->registerAdminExtenders()
        ->registerAiServices()
        ->registerAiEventListeners();
}
```

Also rename `registerOpenAiCmsIntegrationEventListeners` to `registerAiEventListeners`.

- [ ] **Step 3: Run existing tests**

```bash
./vendor/bin/pest --no-coverage
```

Expected: PASS (or no tests affected by this change)

- [ ] **Step 4: Delete `OpenAIProvider`**

```bash
rm src/Support/OpenAIProvider.php
```

- [ ] **Step 5: Commit**

```bash
git add src/Support/Pipelines/GenerateContentPipeline.php src/Providers/AssistantServiceProvider.php
git rm src/Support/OpenAIProvider.php
git commit -m "refactor: replace OpenAIProvider with PrismProvider throughout"
```

---

## Task 5: Build `SectionRegistry`

**Files:**

- Create: `src/Support/SectionRegistry.php`
- Create: `tests/Unit/Support/SectionRegistryTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Capell\Assistant\Support\SectionRegistry;

it('registers and retrieves section descriptors', function (): void {
    $registry = new SectionRegistry;

    $registry->register('hero-fullwidth', [
        'label'       => 'Full-width Hero',
        'description' => 'Large banner',
        'good_for'    => ['landing pages'],
        'not_for'     => ['blog posts'],
        'fields'      => ['headline', 'subheading'],
        'media'       => ['background_image'],
        'supports_translations' => true,
        'repeatable'  => false,
    ]);

    expect($registry->all())->toHaveKey('hero-fullwidth')
        ->and($registry->all()['hero-fullwidth']['label'])->toBe('Full-width Hero');
});

it('formats sections for AI prompt context', function (): void {
    $registry = new SectionRegistry;

    $registry->register('text-block', [
        'label'       => 'Text Block',
        'description' => 'Rich text paragraph block',
        'good_for'    => ['articles', 'about pages'],
        'not_for'     => [],
        'fields'      => ['body'],
        'media'       => [],
        'supports_translations' => true,
        'repeatable'  => true,
    ]);

    $output = $registry->forAi();

    expect($output)->toContain('text-block')
        ->and($output)->toContain('Text Block')
        ->and($output)->toContain('articles');
});

it('returns empty collection when no sections registered', function (): void {
    $registry = new SectionRegistry;

    expect($registry->all())->toBeEmpty()
        ->and($registry->forAi())->toBe('No section types registered.');
});
```

- [ ] **Step 2: Run test to verify failure**

```bash
./vendor/bin/pest tests/Unit/Support/SectionRegistryTest.php --no-coverage
```

Expected: FAIL — `SectionRegistry` not found.

- [ ] **Step 3: Implement `SectionRegistry`**

```php
<?php

declare(strict_types=1);

namespace Capell\Assistant\Support;

class SectionRegistry
{
    /** @var array<string, array<string, mixed>> */
    private array $sections = [];

    /**
     * @param  array{label: string, description: string, good_for: list<string>, not_for: list<string>, fields: list<string>, media: list<string>, supports_translations: bool, repeatable: bool}  $descriptor
     */
    public function register(string $key, array $descriptor): void
    {
        $this->sections[$key] = $descriptor;
    }

    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        return $this->sections;
    }

    public function forAi(): string
    {
        if (empty($this->sections)) {
            return 'No section types registered.';
        }

        $lines = ['Available section types:'];

        foreach ($this->sections as $key => $descriptor) {
            $goodFor = implode(', ', $descriptor['good_for'] ?? []);
            $notFor = implode(', ', $descriptor['not_for'] ?? []);
            $fields = implode(', ', $descriptor['fields'] ?? []);
            $media = implode(', ', $descriptor['media'] ?? []);

            $lines[] = sprintf(
                '- %s (%s): %s. Good for: %s.%s Fields: %s.%s%s',
                $key,
                $descriptor['label'] ?? $key,
                $descriptor['description'] ?? '',
                $goodFor ?: 'general use',
                $notFor ? " Avoid for: {$notFor}." : '',
                $fields ?: 'none',
                $media ? " Media: {$media}." : '',
                ($descriptor['repeatable'] ?? false) ? ' Repeatable.' : '',
            );
        }

        return implode("\n", $lines);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
./vendor/bin/pest tests/Unit/Support/SectionRegistryTest.php --no-coverage
```

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add src/Support/SectionRegistry.php tests/Unit/Support/SectionRegistryTest.php
git commit -m "feat: add SectionRegistry for AI section type awareness"
```

---

## Task 6: Create `ContentTargetContract`, `FlatJsonTarget`, `ContentTargetResolver`

**Files:**

- Create: `src/Contracts/ContentTargetContract.php`
- Create: `src/Targets/FlatJsonTarget.php`
- Create: `src/Support/ContentTargetResolver.php`
- Create: `tests/Unit/Targets/FlatJsonTargetTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Capell\Assistant\Targets\FlatJsonTarget;

it('reports its handle key', function (): void {
    expect((new FlatJsonTarget)->handles())->toBe('flat_json');
});

it('implements ContentTargetContract', function (): void {
    expect(new FlatJsonTarget)
        ->toBeInstanceOf(\Capell\Assistant\Contracts\ContentTargetContract::class);
});
```

- [ ] **Step 2: Run test to verify failure**

```bash
./vendor/bin/pest tests/Unit/Targets/FlatJsonTargetTest.php --no-coverage
```

Expected: FAIL — `ContentTargetContract` and `FlatJsonTarget` not found.

- [ ] **Step 3: Create `ContentTargetContract`**

```php
<?php

declare(strict_types=1);

namespace Capell\Assistant\Contracts;

use Capell\Assistant\Models\AiCreatorSession;

interface ContentTargetContract
{
    /**
     * Apply generated sections to the target content model.
     *
     * @param  array<int, array<string, mixed>>  $sections
     */
    public function apply(array $sections, AiCreatorSession $session): void;

    public function handles(): string;
}
```

- [ ] **Step 4: Create `FlatJsonTarget`**

```php
<?php

declare(strict_types=1);

namespace Capell\Assistant\Targets;

use Capell\Assistant\Contracts\ContentTargetContract;
use Capell\Assistant\Models\AiCreatorSession;

class FlatJsonTarget implements ContentTargetContract
{
    public function apply(array $sections, AiCreatorSession $session): void
    {
        $session->generated_output = array_merge(
            (array) ($session->generated_output ?? []),
            ['flat_json' => $sections],
        );
        $session->save();
    }

    public function handles(): string
    {
        return 'flat_json';
    }
}
```

- [ ] **Step 5: Create `ContentTargetResolver`**

```php
<?php

declare(strict_types=1);

namespace Capell\Assistant\Support;

use Capell\Assistant\Contracts\ContentTargetContract;

class ContentTargetResolver
{
    /** @var array<string, ContentTargetContract> */
    private array $targets = [];

    public function register(ContentTargetContract $target): void
    {
        $this->targets[$target->handles()] = $target;
    }

    public function resolve(string $handle): ?ContentTargetContract
    {
        return $this->targets[$handle] ?? null;
    }

    public function preferred(): ?ContentTargetContract
    {
        return array_values($this->targets)[count($this->targets) - 1] ?? null;
    }

    /** @return array<string, ContentTargetContract> */
    public function all(): array
    {
        return $this->targets;
    }
}
```

- [ ] **Step 6: Run tests to verify they pass**

```bash
./vendor/bin/pest tests/Unit/Targets/FlatJsonTargetTest.php --no-coverage
```

Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add src/Contracts/ContentTargetContract.php src/Targets/FlatJsonTarget.php src/Support/ContentTargetResolver.php tests/Unit/Targets/FlatJsonTargetTest.php
git commit -m "feat: add ContentTargetContract, FlatJsonTarget, ContentTargetResolver"
```

---

## Task 7: Update `AssistantSettings` with AI Creator properties

**Files:**

- Modify: `src/Settings/AssistantSettings.php`

- [ ] **Step 1: Add new properties to `AssistantSettings`**

Replace the entire file with:

```php
<?php

declare(strict_types=1);

namespace Capell\Assistant\Settings;

use Spatie\LaravelSettings\Settings;

class AssistantSettings extends Settings
{
    public bool $page_content_generator;

    public bool $page_title_suggestions;

    public bool $ai_creator;

    public string $ai_provider;

    public string $ai_model;

    public string $ai_api_key;

    public string $image_provider;

    public string $image_model;

    public string $image_default_size;

    public static function group(): string
    {
        return 'assistant';
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add src/Settings/AssistantSettings.php
git commit -m "feat: add AI Creator properties to AssistantSettings"
```

---

## Task 8: Create settings migration for new `AssistantSettings` fields

**Files:**

- Create: `database/migrations/2026_04_18_000001_update_assistant_settings_add_ai_creator.php`

- [ ] **Step 1: Create the migration**

```php
<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('assistant.ai_creator', true);
        $this->migrator->add('assistant.ai_provider', 'openai');
        $this->migrator->add('assistant.ai_model', 'gpt-4o');
        $this->migrator->add('assistant.ai_api_key', '');
        $this->migrator->add('assistant.image_provider', 'openai');
        $this->migrator->add('assistant.image_model', 'dall-e-3');
        $this->migrator->add('assistant.image_default_size', '1024x1024');
    }
};
```

- [ ] **Step 2: Commit**

```bash
git add database/migrations/2026_04_18_000001_update_assistant_settings_add_ai_creator.php
git commit -m "feat: add settings migration for AI Creator provider settings"
```

---

## Task 9: Create `ai_creator_contexts` migration and model

**Files:**

- Create: `database/migrations/2026_04_18_000002_create_ai_creator_contexts_table.php`
- Create: `src/Models/AiCreatorContext.php`

- [ ] **Step 1: Create the migration**

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_creator_contexts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('site_id');
            $table->enum('tone', ['professional', 'friendly', 'playful', 'authoritative'])
                ->default('professional');
            $table->string('industry')->default('');
            $table->text('brand_voice_notes')->nullable();
            $table->text('target_audience')->nullable();
            $table->timestamps();

            $table->unique('site_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_creator_contexts');
    }
};
```

- [ ] **Step 2: Create `AiCreatorContext` model**

```php
<?php

declare(strict_types=1);

namespace Capell\Assistant\Models;

use Illuminate\Database\Eloquent\Model;

class AiCreatorContext extends Model
{
    protected $fillable = [
        'site_id',
        'tone',
        'industry',
        'brand_voice_notes',
        'target_audience',
    ];

    protected $casts = [
        'site_id' => 'integer',
    ];
}
```

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026_04_18_000002_create_ai_creator_contexts_table.php src/Models/AiCreatorContext.php
git commit -m "feat: add ai_creator_contexts table and model"
```

---

## Task 10: Create `ai_creator_sessions` migration and model

**Files:**

- Create: `database/migrations/2026_04_18_000003_create_ai_creator_sessions_table.php`
- Create: `src/Models/AiCreatorSession.php`

- [ ] **Step 1: Create the migration**

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_creator_sessions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('site_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('status', ['in_progress', 'generating', 'review', 'submitted', 'abandoned'])
                ->default('in_progress');
            $table->tinyInteger('stage')->default(0)->unsigned();
            $table->text('intent')->nullable();
            $table->json('clarifications')->nullable();
            $table->json('layout_proposal')->nullable();
            $table->json('generated_output')->nullable();
            $table->json('ai_messages')->nullable();
            $table->unsignedBigInteger('ai_history_id')->nullable();
            $table->unsignedBigInteger('workspace_id')->nullable();
            $table->timestamps();

            $table->foreign('ai_history_id')
                ->references('id')
                ->on('ai_generation_histories')
                ->nullOnDelete();

            $table->index(['site_id', 'user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_creator_sessions');
    }
};
```

- [ ] **Step 2: Create `AiCreatorSession` model**

```php
<?php

declare(strict_types=1);

namespace Capell\Assistant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiCreatorSession extends Model
{
    protected $fillable = [
        'site_id',
        'user_id',
        'status',
        'stage',
        'intent',
        'clarifications',
        'layout_proposal',
        'generated_output',
        'ai_messages',
        'ai_history_id',
        'workspace_id',
    ];

    protected $casts = [
        'site_id'         => 'integer',
        'user_id'         => 'integer',
        'stage'           => 'integer',
        'clarifications'  => 'array',
        'layout_proposal' => 'array',
        'generated_output'=> 'array',
        'ai_messages'     => 'array',
    ];

    public function history(): BelongsTo
    {
        return $this->belongsTo(AIGenerationHistory::class, 'ai_history_id');
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    public function isInProgress(): bool
    {
        return in_array($this->status, ['in_progress', 'generating', 'review'], true);
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026_04_18_000003_create_ai_creator_sessions_table.php src/Models/AiCreatorSession.php
git commit -m "feat: add ai_creator_sessions table and model"
```

---

## Task 11: Create `AiCreatorPolicy`

**Files:**

- Create: `src/Policies/AiCreatorPolicy.php`
- Create: `tests/Unit/Policies/AiCreatorPolicyTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Capell\Assistant\Policies\AiCreatorPolicy;
use Capell\Assistant\Settings\AssistantSettings;

it('returns true when global setting is enabled and no site override', function (): void {
    $settings = new AssistantSettings;
    $settings->ai_creator = true;
    $settings->ai_provider = 'openai';
    $settings->ai_model = 'gpt-4o';
    $settings->ai_api_key = '';
    $settings->image_provider = 'openai';
    $settings->image_model = 'dall-e-3';
    $settings->image_default_size = '1024x1024';
    $settings->page_content_generator = true;
    $settings->page_title_suggestions = true;

    $policy = new AiCreatorPolicy($settings);
    $site = new \stdClass;
    $site->ai_creator_enabled = null;

    expect($policy->isEnabledFor($site))->toBeTrue();
});

it('returns false when global setting is disabled', function (): void {
    $settings = new AssistantSettings;
    $settings->ai_creator = false;
    $settings->ai_provider = 'openai';
    $settings->ai_model = 'gpt-4o';
    $settings->ai_api_key = '';
    $settings->image_provider = 'openai';
    $settings->image_model = 'dall-e-3';
    $settings->image_default_size = '1024x1024';
    $settings->page_content_generator = false;
    $settings->page_title_suggestions = false;

    $policy = new AiCreatorPolicy($settings);
    $site = new \stdClass;
    $site->ai_creator_enabled = null;

    expect($policy->isEnabledFor($site))->toBeFalse();
});

it('site-level override takes precedence over global', function (): void {
    $settings = new AssistantSettings;
    $settings->ai_creator = true;
    $settings->ai_provider = 'openai';
    $settings->ai_model = 'gpt-4o';
    $settings->ai_api_key = '';
    $settings->image_provider = 'openai';
    $settings->image_model = 'dall-e-3';
    $settings->image_default_size = '1024x1024';
    $settings->page_content_generator = true;
    $settings->page_title_suggestions = true;

    $policy = new AiCreatorPolicy($settings);
    $site = new \stdClass;
    $site->ai_creator_enabled = false; // site disables it

    expect($policy->isEnabledFor($site))->toBeFalse();
});
```

- [ ] **Step 2: Run test to verify failure**

```bash
./vendor/bin/pest tests/Unit/Policies/AiCreatorPolicyTest.php --no-coverage
```

Expected: FAIL — `AiCreatorPolicy` not found.

- [ ] **Step 3: Implement `AiCreatorPolicy`**

```php
<?php

declare(strict_types=1);

namespace Capell\Assistant\Policies;

use Capell\Assistant\Settings\AssistantSettings;

class AiCreatorPolicy
{
    public function __construct(private readonly AssistantSettings $settings) {}

    public function isEnabledFor(object $site): bool
    {
        $siteOverride = $site->ai_creator_enabled ?? null;

        if ($siteOverride !== null) {
            return (bool) $siteOverride;
        }

        return $this->settings->ai_creator;
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

```bash
./vendor/bin/pest tests/Unit/Policies/AiCreatorPolicyTest.php --no-coverage
```

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add src/Policies/AiCreatorPolicy.php tests/Unit/Policies/AiCreatorPolicyTest.php
git commit -m "feat: add AiCreatorPolicy with site/global settings cascade"
```

---

## Task 12: Add `PageHeaderActionExtender` to admin package

**Files (all in `capell-app/admin`):**

- Create: `src/Contracts/Extenders/PageHeaderActionExtender.php`
- Create: `src/Support/PageHeaderActionExtenderResolver.php`
- Modify: `src/Providers/AdminServiceProvider.php`
- Modify: the page `EditPage` or `ListPages` resource file (wherever header actions are defined)

> **Note:** This task modifies `capell-app/admin` at `/Users/ben/Sites/packages/capell/capell-4/packages/admin`. Confirm the exact path before running git commands.

- [ ] **Step 1: Create the extender interface**

```php
<?php

declare(strict_types=1);

namespace Capell\Admin\Contracts\Extenders;

use Filament\Actions\Action;

interface PageHeaderActionExtender
{
    public const TAG = 'capell-admin:page-header-actions';

    /** @return array<int, Action> */
    public function actions(): array;
}
```

- [ ] **Step 2: Create the resolver**

```php
<?php

declare(strict_types=1);

namespace Capell\Admin\Support;

use Capell\Admin\Contracts\Extenders\PageHeaderActionExtender;
use Filament\Actions\Action;

class PageHeaderActionExtenderResolver
{
    /** @return array<int, Action> */
    public function resolveActions(): array
    {
        $actions = [];

        foreach (app()->tagged(PageHeaderActionExtender::TAG) as $extender) {
            $actions = array_merge($actions, $extender->actions());
        }

        return $actions;
    }
}
```

- [ ] **Step 3: Register the resolver as a singleton in `AdminServiceProvider`**

Add to the appropriate `register*` method in `AdminServiceProvider`:

```php
$this->app->singleton(PageHeaderActionExtenderResolver::class);
```

Also add the import:

```php
use Capell\Admin\Support\PageHeaderActionExtenderResolver;
```

- [ ] **Step 4: Inject resolver into the page edit resource header actions**

Find the `getHeaderActions()` method in the page edit/list resource. It will look something like:

```php
protected function getHeaderActions(): array
{
    return [
        // existing actions...
    ];
}
```

Update it to merge extender actions:

```php
protected function getHeaderActions(): array
{
    $extender = app(PageHeaderActionExtenderResolver::class);

    return array_merge([
        // existing actions...
    ], $extender->resolveActions());
}
```

- [ ] **Step 5: Commit (in admin package)**

```bash
git add src/Contracts/Extenders/PageHeaderActionExtender.php src/Support/PageHeaderActionExtenderResolver.php src/Providers/AdminServiceProvider.php
git commit -m "feat: add PageHeaderActionExtender interface and resolver"
```

---

## Task 13: Add `SiteHeaderActionExtender` to admin package

**Files (all in `capell-app/admin`):**

- Create: `src/Contracts/Extenders/SiteHeaderActionExtender.php`
- Create: `src/Support/SiteHeaderActionExtenderResolver.php`
- Modify: `src/Providers/AdminServiceProvider.php`
- Modify: site resource edit page (wherever site header actions are defined)

- [ ] **Step 1: Create the extender interface**

```php
<?php

declare(strict_types=1);

namespace Capell\Admin\Contracts\Extenders;

use Filament\Actions\Action;

interface SiteHeaderActionExtender
{
    public const TAG = 'capell-admin:site-header-actions';

    /** @return array<int, Action> */
    public function actions(): array;
}
```

- [ ] **Step 2: Create the resolver**

```php
<?php

declare(strict_types=1);

namespace Capell\Admin\Support;

use Capell\Admin\Contracts\Extenders\SiteHeaderActionExtender;
use Filament\Actions\Action;

class SiteHeaderActionExtenderResolver
{
    /** @return array<int, Action> */
    public function resolveActions(): array
    {
        $actions = [];

        foreach (app()->tagged(SiteHeaderActionExtender::TAG) as $extender) {
            $actions = array_merge($actions, $extender->actions());
        }

        return $actions;
    }
}
```

- [ ] **Step 3: Register in `AdminServiceProvider`**

Add alongside the `PageHeaderActionExtenderResolver` registration:

```php
$this->app->singleton(SiteHeaderActionExtenderResolver::class);
```

- [ ] **Step 4: Inject resolver into the site resource header actions**

Find the site resource's `getHeaderActions()` and merge in extender actions (same pattern as Task 12 Step 4):

```php
protected function getHeaderActions(): array
{
    $extender = app(SiteHeaderActionExtenderResolver::class);

    return array_merge([
        // existing actions...
    ], $extender->resolveActions());
}
```

- [ ] **Step 5: Commit (in admin package)**

```bash
git add src/Contracts/Extenders/SiteHeaderActionExtender.php src/Support/SiteHeaderActionExtenderResolver.php src/Providers/AdminServiceProvider.php
git commit -m "feat: add SiteHeaderActionExtender interface and resolver"
```

---

## Task 14: Wire new services into `AssistantServiceProvider`

**Files:**

- Modify: `src/Providers/AssistantServiceProvider.php`

- [ ] **Step 1: Add singleton registrations for new services**

In `registerAiServices()`, add after the existing singletons:

```php
use Capell\Assistant\Models\AiCreatorContext;
use Capell\Assistant\Models\AiCreatorSession;
use Capell\Assistant\Policies\AiCreatorPolicy;
use Capell\Assistant\Support\ContentTargetResolver;
use Capell\Assistant\Support\SectionRegistry;
use Capell\Assistant\Targets\FlatJsonTarget;

// ...

$this->app->singleton(SectionRegistry::class, fn (): SectionRegistry => new SectionRegistry);

$this->app->singleton(ContentTargetResolver::class, function (Application $app): ContentTargetResolver {
    $resolver = new ContentTargetResolver;
    $resolver->register($app->make(FlatJsonTarget::class));

    foreach ($app->tagged('capell-assistant:content-targets') as $target) {
        $resolver->register($target);
    }

    return $resolver;
});

$this->app->singleton(AiCreatorPolicy::class, fn (Application $app): AiCreatorPolicy => new AiCreatorPolicy(
    $app->make(AssistantSettings::class),
));
```

- [ ] **Step 2: Register new models in `registerModels()`**

```php
private function registerModels(): self
{
    CapellCore::registerModel('AIGenerationHistory', AIGenerationHistory::class);
    CapellCore::registerModel('AiCreatorContext', AiCreatorContext::class);
    CapellCore::registerModel('AiCreatorSession', AiCreatorSession::class);

    return $this;
}
```

- [ ] **Step 3: Run all tests**

```bash
./vendor/bin/pest --no-coverage
```

Expected: All PASS

- [ ] **Step 4: Commit**

```bash
git add src/Providers/AssistantServiceProvider.php
git commit -m "feat: wire SectionRegistry, ContentTargetResolver, AiCreatorPolicy into service provider"
```

---

## Self-Review Checklist

- [x] Prism swap: Task 2 (PrismProvider), Task 3 (config/composer), Task 4 (service provider + pipeline update)
- [x] SectionRegistry: Task 5
- [x] ContentTargetContract + FlatJsonTarget + ContentTargetResolver: Task 6
- [x] AssistantSettings new fields: Task 7
- [x] Settings migration: Task 8
- [x] DB tables + models: Tasks 9–10
- [x] AiCreatorPolicy: Task 11
- [x] Admin extender hooks (page + site): Tasks 12–13
- [x] Service provider wiring: Task 14
- [x] All method names consistent across tasks: `PrismProvider::chat()`, `SectionRegistry::forAi()`, `ContentTargetResolver::register()`, `AiCreatorPolicy::isEnabledFor()`
- [x] No TBDs or placeholders
