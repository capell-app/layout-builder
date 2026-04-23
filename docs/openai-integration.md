# OpenAI Integration

Developer overview of the AI integration that ships with the **`capell-app/assistant`** package. For install steps, config reference, and the artisan command list, see the package README at [`packages/assistant/README.md`](../packages/assistant/README.md).

- Audience: developers and contributors.
- Scope: the Assistant package and its integration points into the Admin package.

## What you get

- Generate page titles and meta descriptions.
- Suggest multiple title/description options.
- Draft long-form content and refactor existing page copy.
- Apply AI drafts to pages.
- Persistent audit trail with token usage and timings.
- Rate limiter and circuit breaker for reliability.
- Filament widget to inspect usage.

## Typical flow

1. Build a context object that implements `AiActionContextInterface` (default: `ContentActionContext`) with content, keywords, pageId, and languageId.
2. Call an action — `SuggestPageTitlesAction`, `SuggestMetaDescriptionsAction`, or `GeneratorPageContentAction`.
3. Pick a suggestion from the returned array.
4. Apply it with `ApplyAiDraftAction::run($page, $draft)`.
5. Every call is automatically logged to `ai_generation_histories` via `RecordAiGenerationAction`.

## Architecture

- Pattern: **Action → Pipeline → Services**, with lifecycle events.
- Services are OpenAI provider, rate limiter, response parser, token counter, prompt repository, and feature registry.
- Actions live under `Capell\Assistant\Actions\`.
- Persistence model: `Capell\Assistant\Models\AIGenerationHistory`.

### Services

- `OpenAIProvider` — wraps OpenAI API calls with circuit breaker and exponential backoff.
- `PromptRepository` — returns prompt templates from config.
- `AiResponseParser` — extracts values from JSON or bulleted lists, with fallback.
- `AiRateLimiter` — per-user/feature/global request limits.
- `AiTokenCounter` — token estimation and counters.
- `AiFeatureRegistry` — maps feature keys to handler actions and config.

### Context abstraction

Actions are model-agnostic. They operate on the interface:

```php
interface AiActionContextInterface
{
    public function getContent(): string;
    public function getKeywords(): string;
    public function getPageId(): int;
    public function getLanguageId(): int;
}
```

`ContentActionContext` is the default adapter. Implement your own to bridge a different content source — e.g. a `PageActionContext` that resolves a translation at call time.

### Actions

Under `Capell\Assistant\Actions\`:

| Action                          | Returns                            |
| ------------------------------- | ---------------------------------- |
| `SuggestPageTitlesAction`       | `array` of title options           |
| `SuggestMetaDescriptionsAction` | `array` of description options     |
| `GeneratorPageContentAction`    | long-form draft string             |
| `ApplyAiDraftAction`            | the persisted page                 |
| `RecordAiGenerationAction`      | the created history row (internal) |

Actions accept either an `AiActionContextInterface` + options array, or an `AiActionInput` DTO (which holds a `Translation` and is adapted automatically).

### Pipelines

Each pipeline composes:

1. Validate input.
2. Check rate limit.
3. (Optional) check cache and short-circuit.
4. Execute the OpenAI call.
5. Parse the response.
6. Record a history row.
7. Cache the result.

Pipelines ship per feature — `TitleGenerationPipeline`, `MetaDescriptionPipeline`, `SuggestTitlesPipeline`, `SuggestMetaDescriptionsPipeline`.

## Events

Fired by every action via `BaseAction`:

- `AiGenerationStarted` — before the API call.
- `AiGenerationCompleted` — on success.
- `AiGenerationFailed` — on any failure.

Listeners registered by the provider:

- `LogAiGeneration` — logs successful completions.
- `NotifyAiFailure` — warns on failures.

## Persistence

- Table: `ai_generation_histories`.
- Migration: `packages/assistant/database/migrations/create_ai_generation_histories_table.php`.
- Factory: `packages/assistant/database/factories/AIGenerationHistoryFactory.php`.

Stored fields include action name, model, input/output, `prompt_tokens`/`completion_tokens`/`total_tokens`, `duration`, `pageable_id`/`pageable_type`, `language_id`, and a free-form `metadata` JSON.

## Configuration

Config file: `config/capell-seo-tools.php`. Full reference lives in the [package README](../packages/seo-tools/README.md#configuration). Key defaults:

- `openai.default_model` = `gpt-4`
- `openai.max_tokens` = `512`
- `openai.max_retries` = `3`, `openai.retry_delay_ms` = `500`
- `rate_limiting.requests_per_minute` = `60`
- `cache.ttl` = `86400` (1 day)

The `features.*.handler` entries wire each feature key to an Action class. Feature handlers currently point at a mix of `Capell\Admin\Actions\AI\*` (title, meta) and `Capell\Assistant\Actions\*` (content) for historical reasons — swap to your own handlers by editing the config.

## Filament integration

- `AIGenerationHistoryResource` lives in the Admin package and is automatically registered when Assistant is installed.
- `Capell\Assistant\Filament\Widgets\AiUsageWidget` — dashboard widget for aggregate usage.
- `Capell\Assistant\Filament\Settings\AssistantSettingsSchema` — a Settings tab for model and rate-limit configuration.

## Commands

- `capell:assistant-install` — package install.
- `capell:admin-test-openai` — probe the OpenAI API.
- `capell:admin-clear-ai-cache` — clear the result cache.
- `capell:admin-monitor-ai-usage` — print a usage summary.

(The `capell:admin-*` prefix on three commands is a legacy of when these lived in the Admin package.)

## Usage examples

### Suggest titles from a context

```php
use Capell\Assistant\Actions\SuggestPageTitlesAction;
use Capell\Assistant\Support\Context\ContentActionContext;

$context = new ContentActionContext($content, $keywords, $pageId, 'page', $languageId);
$titles = SuggestPageTitlesAction::run($context, ['user_id' => auth()->id()]);
```

### Apply a chosen draft

```php
use Capell\Assistant\Actions\ApplyAiDraftAction;

ApplyAiDraftAction::run($page, $chosenText);
```

### Draft long-form content

```php
use Capell\Assistant\Actions\GeneratorPageContentAction;

$draft = GeneratorPageContentAction::run($context, [
    'user_id'       => auth()->id(),
    'current_title' => $currentTitle, // improves coherence
    'target_length' => 800,
    'refactor'      => true,          // refactor existing content vs. generate fresh
]);
```

### Custom context adapter

Bridge any content source by implementing the interface:

```php
use Capell\Assistant\Contracts\AiActionContextInterface;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;

final class PageActionContext implements AiActionContextInterface
{
    public function __construct(private Page $page, private Language $language) {}

    public function getContent(): string  { /* ... */ }
    public function getKeywords(): string { /* ... */ }
    public function getPageId(): int      { return (int) $this->page->id; }
    public function getLanguageId(): int  { return (int) $this->language->id; }
}
```

## Testing

All tests use Pest. See `packages/assistant/tests/` for current coverage. Typical assertions:

```php
use Capell\Assistant\Events\AiGenerationCompleted;
use Illuminate\Support\Facades\Event;

Event::fake();
SuggestPageTitlesAction::run($context);
Event::assertDispatched(AiGenerationCompleted::class);
```

Mock OpenAI cleanly via the SDK facade:

```php
use OpenAI\Laravel\Facades\OpenAI;

OpenAI::shouldReceive('chat->create')->andReturn((object) [
    'choices' => [(object) ['message' => (object) ['content' => "- First\n- Second"], 'finish_reason' => 'stop']],
    'usage'   => (object) ['total_tokens' => 30, 'prompt_tokens' => 10, 'completion_tokens' => 20],
]);
```

## Troubleshooting

- **Circuit breaker open** — the provider trips after consecutive failures and resets on success. Check connectivity and inspect logs.
- **Rate limit exceeded** — lower call frequency or raise `rate_limiting.requests_per_minute`.
- **Empty results** — `$context->getContent()` was empty. Extract plain text from rich content before passing in.
- **Repeated results despite input changes** — cache keys include page/language/content/keywords. Invalidate the cache or tweak the inputs.
- **Parsing oddities** — the parser supports JSON and bullet/numbered lists. If the model returns prose, tighten your prompt.

## Best practices

- Keep `system` prompts concise and directive.
- Spec the output shape explicitly — bullet list or JSON, with a character limit.
- Include language and tone in the `system` prompt, content and keywords in the `user_template`.
- Never log full API keys.
- Use the rate limiter on any user-facing trigger.

## See also

- [Assistant package README](../packages/assistant/README.md)
- [Assistant API reference](../packages/assistant/docs/assistant-api.md)
- [Assistant Database reference](../packages/assistant/docs/assistant-database.md)
- [Test plan for AI actions & services](test-plan-actions-services.md)
