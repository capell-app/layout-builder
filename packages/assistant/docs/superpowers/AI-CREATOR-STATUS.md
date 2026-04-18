# AI Creator — Implementation Status & Future Ideas

**Last updated:** 2026-04-18
**PR:** [capell-app/packages#41](https://github.com/capell-app/packages/pull/41)
**Admin changes:** pushed to `4.x` on `capell-app/capell`

---

## What Was Built (Phase 1 + 2 + 4)

### Multi-Provider AI — Prism swap ✅
- Removed `openai-php/laravel`, added `prism-php/prism`
- `PrismProvider` wraps Prism's fluent API; preserves circuit breaker, retry/backoff, `AiResponse` VO
- Config: `capell-assistant.prism.*` keys (provider, model, max_tokens, image_provider, image_model, image_size)
- `AssistantSettings` gains 7 new fields: `ai_creator`, `ai_provider`, `ai_model`, `ai_api_key`, `image_provider`, `image_model`, `image_default_size`

### Infrastructure ✅
- `SectionRegistry` — in-memory singleton; `register(key, descriptor)`, `forAi()` formats for AI prompt
- `ContentTargetContract` — interface for applying generated sections to content models
- `ContentTargetResolver` — registry; `preferred()` = last registered (extensible via `capell-assistant:content-targets` tag)
- `FlatJsonTarget` — built-in target; stores sections as `flat_json` in `session->generated_output`

### Database ✅
- `ai_creator_contexts` — brand/tone preferences per site (tone, industry, target_audience, brand_voice_notes)
- `ai_creator_sessions` — wizard state (status enum, stage, intent, layout_proposal JSON, ai_history_id FK, workspace_id)
- Settings migration: adds 7 new `AssistantSettings` rows

### Policy ✅
- `AiCreatorPolicy::isEnabledFor($site)` — per-site override → global fallback

### Admin Extensibility ✅
- `PageHeaderActionExtender` interface + resolver registered in admin's `AdminServiceProvider`
- `SiteHeaderActionExtender` interface + resolver registered in admin's `AdminServiceProvider`
- Both inject `$extenderActions` into `EditPage::getActions()` and `EditSite::getActions()`

### AI Creator Wizard ✅
- `AiCreatorAction` — Filament `Action` with 4-step `Wizard` form (Describe → Brand → Layout → Review)
- AI generation fires during **Brand step's `afterValidation`** — Layout step shows real AI-proposed sections the user can reorder before submitting
- Session ID passed via `Hidden::make('ai_session_id')` between steps
- Brand context persisted to `AiCreatorContext` (skips questions on next run)
- `page_count` field visible only when mounted on a site resource
- Error handling: generation failure cancels wizard progression via `$this->halt()`
- `AiCreatorPipeline` — 6 stages: load/create session → load context → rate limit → AI call → parse JSON → persist

### AI Image Generator ✅
- `AiImageGeneratorAction` — inline Filament field action
- Auto-composes prompt from sibling field values passed as `$contextFieldKeys`
- Preview modal: editable prompt → Generate → image preview → Accept (updates parent field)
- `GenerateAiImageAction` — calls `Prism::image()` with provider/model resolution
- Blade view: `resources/views/filament/fields/image-preview.blade.php`

### Actions ✅
- `GenerateAiLayoutAction` — dispatches `AiCreatorPipeline`, fires `AiGenerationStarted/Completed/Failed` events
- `GenerateAiImageAction` — calls Prism image API
- `SubmitAiCreatorDraftAction` — applies sections via `ContentTargetResolver::preferred()`, marks session `submitted`

### Extenders wired ✅
- `AiCreatorPageExtender` — implements `PageHeaderActionExtender`, returns `[AiCreatorAction::make()]`
- `AiCreatorSiteExtender` — implements `SiteHeaderActionExtender`, returns `[AiCreatorAction::make()]`
- Both tagged in `AssistantServiceProvider::registerAdminExtenders()`

---

## Deviations from Original Spec

| Original spec | What was built | Reason |
|---|---|---|
| `SubmitAiCreatorDraftAction` calls admin's `SubmitForApprovalAction` directly | Uses `ContentTargetResolver::preferred()` to apply sections; marks session `submitted` | `SubmitForApprovalAction` is a Filament UI action (not a service), operates on an existing `Workspace` record — direct call not possible without a page/record in scope |
| Workspace integration (Phase 3) | Not implemented | Depends on the above; deferred to next phase |
| `workspace_id` populated on session | Field exists on model, left `null` | Workspace creation happens via admin's page-save flow, not AI Creator |
| Multi-page scaffolding from site level | `page_count` field wired, not acted on | Pipeline always creates 1 session; multi-page loop deferred |

---

## Not Yet Built (Deferred)

### Phase 3 — Workspace Integration
The `SubmitAiCreatorDraftAction` currently applies sections via `ContentTargetResolver` and marks the session `submitted`. True workspace integration requires:
- A way to create a workspace draft from code (not from a Filament UI action)
- Admin to expose a `WorkspaceService::createDraft(array $content)` or similar service class (not a Filament action)
- `SubmitAiCreatorDraftAction` calls that service, stores `workspace_id` on the session
- Post-submission notification includes a "View in Workspace →" link

### Phase 5 — Polish & Extensions
- **`MosaicTarget`** in the mosaic package — registers via `capell-assistant:content-targets` tag; maps AI section output onto real Mosaic `Section` models
- **Resume cards** — if an in-progress session exists for the current page/site, offer to resume it in the wizard's opening step rather than starting fresh
- **Starter prompts** — derive an initial intent prompt from the page's existing title/keywords
- **Multi-page scaffolding** — site-level action uses `page_count` to generate N sessions; shows progress per page
- **Per-site AI provider** — settings cascade (`AssistantSettings` → site override → store override) is designed but provider resolution in `PrismProvider` always reads global config; need to thread site context through the pipeline
- **Filament settings page** — expose `AssistantSettings` new fields (provider, model, api_key, image settings) in the admin panel UI
- **`AiCreatorContext` skip step** — if a context already exists for the site, skip the Brand step entirely and pre-fill from saved context (currently pre-fills but still shows the step)
- **Rename `OpenAICircuitBreakerOpenException`** — still named OpenAI in a multi-provider context; should be `AiCircuitBreakerOpenException`

---

## Future Ideas

- **Inspiration URL/image** — accept a URL or screenshot as inspiration; extract structure and content tone via vision model
- **Content regeneration per section** — "I don't like this section, regenerate it" without re-running the full wizard
- **AI audit / SEO critique** — post-creation analysis: "Your layout is missing a call-to-action in the hero"; surface as a dismissible widget on the edit page
- **Tone consistency checker** — compare generated copy across sections; flag inconsistencies
- **Translation-aware generation** — when generating content for a site with multiple languages, generate all translations in one AI call
- **History/favourites** — let users save a wizard run as a "template" to reuse for similar pages
- **Prompt library** — admin-editable prompt templates stored in DB (not just config), per-site overrideable
- **AI-assisted image alt text** — after accepting a generated image, auto-draft alt text based on the prompt + page context
- **Webhook / async generation** — for slow providers or large multi-page runs, queue the pipeline and notify via Filament when done
