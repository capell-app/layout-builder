# Database Reference — Capell Assistant

One migration, one table.

## Migration

- `database/migrations/create_ai_generation_histories_table.php` — creates `ai_generation_histories`.

Run via `php artisan capell:assistant-install`.

## `ai_generation_histories`

Every AI call (successful or failed) is recorded here for audit, usage tracking, and the dashboard widget.

Key columns:

- `action` — the feature that ran (e.g. `title_generation`, `meta_description`, `content_generation`)
- `model` — the OpenAI model used for the call
- `input` — the prompt/input text sent
- `output` — the response returned (nullable on failure)
- `prompt_tokens`, `completion_tokens`, `total_tokens` — token accounting
- `duration` — wall-clock time for the call (ms)
- `pageable_id`, `pageable_type` — polymorphic pointer to the edited entity (usually a Page)
- `language_id` — which language was being edited
- `metadata` (JSON) — free-form metadata from the pipeline (user id, feature flags, retry counts, etc.)
- timestamps

## Factory

- `database/factories/AIGenerationHistoryFactory.php` — handy for tests and demo seeds.

## Model

- `src/Models/AIGenerationHistory.php`

Relations: the polymorphic `pageable()` resolves to whatever model was being edited (typically a `Page`).
