# Capell Assistant

AI-assisted content editing for Capell, powered by OpenAI. Adds admin-side helpers for generating page titles, meta descriptions, and long-form draft content — with rate limiting, result caching, and a persistent audit log.

## What this package adds

- **Title suggestions** — generate one or many page title options from the current draft.
- **Meta description suggestions** — same for SEO meta descriptions.
- **Long-form content drafting** — draft or refactor page body content with configurable length and tone.
- **Apply draft** action — persist a chosen AI suggestion to a page with one call.
- **AI generation history** — every call is logged to `ai_generation_histories` with tokens, duration, and metadata.
- **Filament widget** — `AiUsageWidget` surfaces aggregate usage on the admin dashboard.
- **Filament settings schema** — a Settings tab for configuring models and rate limits from the admin.
- **Pipelines with rate limit, retry, circuit breaker, and caching** — OpenAI calls are resilient by default.

## Prerequisites

- `capell-app/admin`
- `capell-app/frontend`
- An OpenAI API key

## Installation

```sh
composer require capell-app/assistant
php artisan capell:assistant-install
```

Add your OpenAI credentials to `.env`:

```
OPENAI_API_KEY=sk-...
```

Optional — publish the config:

```sh
php artisan vendor:publish --tag=capell-assistant-config
```

## Configuration

`config/capell-assistant.php`:

| Key | Default | Purpose |
| --- | --- | --- |
| `openai.default_model` | `gpt-4` | Fallback model when a feature doesn't pin its own |
| `openai.max_tokens` | `512` | Token cap per response |
| `openai.max_retries` | `3` | Retries on transient failures |
| `openai.retry_delay_ms` | `500` | Initial backoff between retries (ms) |
| `rate_limiting.enabled` | `true` | Per-user + global request throttling |
| `rate_limiting.requests_per_minute` | `60` | Throttle ceiling |
| `features.title_generation` | enabled, `gpt-4-turbo` | Wired to a title action |
| `features.meta_description` | enabled, `gpt-4-turbo` | Wired to a meta-description action |
| `features.content_generation` | enabled, `gpt-4-turbo` | Wired to `GeneratorPageContentAction` |
| `cache.ttl` | `86400` | Result cache TTL in seconds (1 day) |

Each feature declares its own `handler` (an Action class) so you can swap in custom implementations without forking the package.

## Artisan commands

| Command | Purpose |
| --- | --- |
| `capell:assistant-install` | Publish config, run migrations, register resources |
| `capell:admin-test-openai` | Probe the OpenAI API with the configured key |
| `capell:admin-clear-ai-cache` | Clear the generation result cache |
| `capell:admin-monitor-ai-usage` | Print a usage summary from the history table |

> The three `capell:admin-*` signatures are a naming quirk carried over from when these lived in the Admin package. They still ship from the Assistant provider.

## How it's used

### From application code

```php
use Capell\Assistant\Actions\SuggestPageTitlesAction;
use Capell\Assistant\Support\Context\ContentActionContext;

$context = new ContentActionContext($content, $keywords, $pageId, 'page', $languageId);
$titles = SuggestPageTitlesAction::run($context, ['user_id' => auth()->id()]);
```

Apply a chosen draft:

```php
use Capell\Assistant\Actions\ApplyAiDraftAction;

ApplyAiDraftAction::run($page, $chosenText);
```

### Long-form drafts

```php
use Capell\Assistant\Actions\GeneratorPageContentAction;

$draft = GeneratorPageContentAction::run($context, [
    'user_id'      => auth()->id(),
    'target_length'=> 800,
    'refactor'     => true,
]);
```

### From the admin

Enable the feature(s) in `config/capell-assistant.php`, and the matching actions appear on the page edit form (title action, meta action, content drafter). The AI generation history and usage widget live in the Admin panel alongside the other Filament resources.

## Database

Migration: `database/migrations/create_ai_generation_histories_table.php` — creates `ai_generation_histories` with token, duration, and pageable metadata columns. See [docs/Database.md](docs/Database.md).

## Further reading

- [Database reference](docs/Database.md)
- [API reference](docs/API.md)
- [OpenAI integration overview](../../docs/openai-integration.md)
- Capell core docs: [Packages overview](../../../capell-4/docs/packages.md)
