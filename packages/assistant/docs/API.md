# API Reference — Capell Assistant

Browse `src/` for full source. This page is a map of the key entry points.

## Service provider

- `src/Providers/AssistantServiceProvider.php` — registers the model, commands, OpenAI services, admin extenders, listeners, Filament widgets, and settings schema.

## Actions

Under `src/Actions/` — all extend `BaseAction` and run via `lorisleiva/laravel-actions`:

- `SuggestPageTitlesAction` — multiple title options
- `SuggestMetaDescriptionsAction` — multiple meta description options
- `GeneratorPageContentAction` — long-form draft content (supports `target_length`, `refactor`)
- `ApplyAiDraftAction` — persist a chosen draft to a page
- `RecordAiGenerationAction` — internal; writes the history row
- `BaseAction` — shared lifecycle hooks (dispatches the three events below)

## Events

Under `src/Events/`:

- `AiGenerationStarted` — before the OpenAI call
- `AiGenerationCompleted` — after a successful response
- `AiGenerationFailed` — after a failure (rate limit, API error, etc.)

## Listeners

Under `src/Listeners/`:

- `LogAiGeneration` — subscribes to `AiGenerationCompleted`
- `NotifyAiFailure` — subscribes to `AiGenerationFailed` (logs a warning)

## Handlers

Under `src/Handlers/`:

- `ClearCircuitBreakerHandler` — subscribed to the admin's `EditPage` lifecycle to reset circuit-breaker state between edits.

## Model

- `src/Models/AIGenerationHistory.php` — see [Database.md](Database.md).

## Filament surfaces

- `src/Filament/Widgets/AiUsageWidget.php` — dashboard widget showing token usage, recent activity.
- `src/Filament/Settings/AssistantSettingsSchema.php` — Settings page tab for the package.

## Admin extenders

Registered in the provider to inject AI actions into Admin-owned pages:

- `PageContentEditor` extender — adds the "Draft content" action to the page editor
- `PageTitleWithSlugInput` extender — adds the "Generate title" action
- `SearchMetaData` extender — adds the "Generate meta description" action

(Exact file paths are in `src/Filament/Extenders/` — check there for the current wiring.)

## Settings

- `src/Settings/AssistantSettings.php` — typed settings object backing the Filament schema.

## Contracts

Under `src/Contracts/`:

- `ActionContract`
- `AiActionContextInterface` — `getContent()`, `getKeywords()`, `getPageId()`, `getLanguageId()`

Implement `AiActionContextInterface` on your own adapters to make any domain model available to the actions. `Capell\Assistant\Support\Context\ContentActionContext` is the default adapter.

## Commands

Under `src/Console/`:

- `InstallCommand` — `capell:assistant-install`
- `ClearAiCacheCommand` — `capell:admin-clear-ai-cache`
- `TestOpenAiConnectionCommand` — `capell:admin-test-openai`
- `MonitorAiUsageCommand` — `capell:admin-monitor-ai-usage`

## Composer dependencies

- `capell-app/admin`
- `capell-app/frontend`
- `openai-php/laravel` (`^0.10|^0.18`)

## Quick links

- Source directory: [`./src`](../src)
- Database reference: [Database.md](Database.md)
- Package README: [../README.md](../README.md)
- OpenAI integration overview: [../../../docs/openai-integration.md](../../../docs/openai-integration.md)
