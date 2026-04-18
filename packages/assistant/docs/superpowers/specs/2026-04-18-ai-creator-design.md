# AI Creator — Design Spec

**Date:** 2026-04-18
**Package:** `capell-app/assistant` (self-contained)
**Status:** Approved for implementation

---

## Overview

AI Creator is a conversational wizard delivered as a Filament action (modal/slide-over), attachable to any resource in the admin panel. It guides non-technical users through describing a page they want to build, optionally accepts a URL or image as inspiration, then generates a structured layout composed of properly separated CMS sections. Nothing goes live without human approval through the existing Workspace workflow.

The wizard opens in context — on a page resource it creates content for that page, on a site resource it can ask "how many pages would you like to create?" and scaffold multiple pages. Both actions are also callable programmatically by passing data objects, with no UI required.

There are no dedicated assistant panel pages. All AI features surface as actions registered against existing admin resources via the extender tag pattern.

---

## Constraints

- The `assistant` package is self-contained. No other package may add `capell-app/assistant` as a dependency.
- The `assistant` package may extend `admin`, `mosaic`, and other packages by registering extenders in its own service provider via the `app()->tag()` pattern those packages already use.
- Admin exposes an extender interface for dynamic workspace actions. The assistant registers its own actions against this interface at boot — admin has no knowledge of assistant.
- All new code lives inside `capell-app/assistant` unless explicitly noted (mosaic target, admin extender interface).

---

## 1. Multi-Provider AI (Prism)

### Problem

The current `OpenAIProvider` is hard-wired to `openai-php/laravel`. Users cannot switch providers from the admin.

### Solution

Replace `openai-php/laravel` with `echolabs/prism` (multi-provider: OpenAI, Anthropic, Gemini). A new `PrismProvider` wraps Prism's driver system and implements the same internal contract as the current `OpenAIProvider`, preserving the circuit breaker, retry/backoff, and `AiResponse` value object.

The active provider, model, and API key are stored in `AssistantSettings` and surfaced in the Filament settings page. Image generation can use a different provider from text generation (e.g. text on Anthropic, images on OpenAI DALL-E).

### Settings added to `AssistantSettings`

```
ai_creator (bool)
ai_provider (string)        // e.g. 'openai', 'anthropic', 'gemini'
ai_model (string)
ai_api_key (string, encrypted)
image_provider (string)
image_model (string)
image_default_size (string) // e.g. '1024x1024'
```

### Files changed

- `src/Support/PrismProvider.php` — new, replaces `OpenAIProvider`
- `src/Support/OpenAIProvider.php` — removed
- `src/Settings/AssistantSettings.php` — updated
- `composer.json` — swap `openai-php/laravel` → `echolabs/prism`

---

## 2. Section Registry

### Purpose

Teach the AI which section types are available so it can propose a sensible layout. Different installations have different sections registered (mosaic, custom packages), so the registry must be populated at boot, not hardcoded.

### Interface

```php
SectionRegistry::register('hero-fullwidth', [
    'label'                 => 'Full-width Hero',
    'description'           => 'Large banner with headline, subheading, and CTA button',
    'good_for'              => ['landing pages', 'homepages'],
    'not_for'               => ['blog posts', 'legal pages'],
    'fields'                => ['headline', 'subheading', 'cta_label', 'cta_url'],
    'media'                 => ['background_image'],
    'supports_translations' => true,
    'repeatable'            => false,
]);
```

`SectionRegistry::forAi()` returns a formatted capability list injected into AI prompts as context. The registry is an in-memory singleton — no database table.

Packages register their sections in their own service provider `boot()` methods. The assistant package does not need to know which packages are installed.

### Files

- `src/Support/SectionRegistry.php`

---

## 3. Content Target Contract

### Purpose

Decouple the AI Creator's output step from any specific content model. Some installations use flat JSON, others use mosaic structured layouts.

### Interface

```php
interface ContentTargetContract {
    public function apply(array $sections, AiCreatorSession $session): void;
    public function handles(): string;
}
```

### Implementations

| Class            | Package   | Registered via                                                          |
| ---------------- | --------- | ----------------------------------------------------------------------- |
| `FlatJsonTarget` | assistant | Always registered                                                       |
| `MosaicTarget`   | mosaic    | `app()->tag([MosaicTarget::class], 'capell-assistant:content-targets')` |

The assistant's service provider resolves all tagged targets at boot and registers them with a `ContentTargetResolver` service. Active target is determined by which packages are installed and by a per-site setting.

### Files

- `src/Contracts/ContentTargetContract.php`
- `src/Targets/FlatJsonTarget.php`
- `src/Support/ContentTargetResolver.php`
- Mosaic: `src/Targets/MosaicTarget.php` (in mosaic package)

---

## 4. Persistent State — Two Database Tables

### 4a. `ai_creator_contexts` — Brand/Tone Preferences

Stores site-level brand preferences so the wizard skips questions that have already been answered. One row per site.

| Column                  | Type       | Notes                                          |
| ----------------------- | ---------- | ---------------------------------------------- |
| id                      | bigint PK  |                                                |
| site_id                 | bigint FK  |                                                |
| tone                    | enum       | professional, friendly, playful, authoritative |
| industry                | varchar    |                                                |
| brand_voice_notes       | text       | nullable                                       |
| target_audience         | text       | nullable                                       |
| created_at / updated_at | timestamps |                                                |

If a context row exists for the site, the wizard omits brand/tone clarifying questions and injects the stored values into the prompt directly.

### 4b. `ai_creator_sessions` — Wizard Progress

One row per wizard session. Persists across browser closes so users can resume.

| Column                  | Type       | Notes                                                 |
| ----------------------- | ---------- | ----------------------------------------------------- |
| id                      | bigint PK  |                                                       |
| site_id                 | bigint FK  |                                                       |
| user_id                 | bigint FK  |                                                       |
| status                  | enum       | in_progress, generating, review, submitted, abandoned |
| stage                   | tinyint    | current wizard stage number                           |
| intent                  | text       | nullable — user's initial description                 |
| clarifications          | json       | nullable — Q&A pairs                                  |
| layout_proposal         | json       | nullable — proposed sections array                    |
| generated_output        | json       | nullable — final output per target                    |
| ai_messages             | json       | nullable — conversation history for continuity        |
| ai_history_id           | bigint FK  | nullable → ai_generation_histories                    |
| workspace_id            | bigint     | nullable — set on submission                          |
| created_at / updated_at | timestamps |                                                       |

### Files

- `database/migrations/create_ai_creator_contexts_table.php`
- `database/migrations/create_ai_creator_sessions_table.php`
- `database/migrations/update_assistant_settings_add_ai_creator.php`
- `src/Models/AiCreatorContext.php`
- `src/Models/AiCreatorSession.php`

---

## 5. AI Creator Wizard (Action-based)

### Entry point — `AiCreatorAction`

A Filament wizard action (multi-step modal/slide-over). No dedicated panel page. The action is registered against existing admin resources via the extender tag pattern:

```php
// In AssistantServiceProvider::boot()
$this->app->tag([AiCreatorAction::class], 'capell-admin:page-actions');
$this->app->tag([AiCreatorAction::class], 'capell-admin:site-actions');
```

Admin iterates tagged actions and injects them into the relevant resource header/table row actions at boot. The assistant has no knowledge of admin internals; admin has no knowledge of assistant.

### Programmatic invocation

The action wraps a standalone `GenerateAiLayoutAction` that accepts a data object:

```php
GenerateAiLayoutAction::make()->handle(new AiCreatorData(
    siteId: $site->id,
    intent: 'Build a contact page',
    pageCount: 1,
));
```

This allows other parts of the system (jobs, other actions, tests) to trigger layout generation without a UI.

### Context-aware behaviour

The action inspects the resource it is mounted on:

| Mounted on              | Opening question                                                        |
| ----------------------- | ----------------------------------------------------------------------- |
| Page resource           | "What would you like this page to contain?"                             |
| Site resource           | "How many pages would you like to create?" + brief description per page |
| Article / blog resource | "Describe the article — I'll suggest a structure and draft the content" |

When mounted on a site, a `pageCount` step is shown first. Each page then runs its own clarification loop and generates independently, with progress shown inline.

### Stage 0 — Action opening step

Before the first input the wizard shows (inside the modal):

- **Resume card** if an `in_progress` session exists for this record + user
- **Starter prompts** derived from `SectionRegistry::forAi()` + recent site history
- **Recent completions** — last 3 submitted layouts with workspace status badges

### Wizard flow

1. User describes what they want (or picks a starter prompt / resumes existing session)
2. AI inspects `AiCreatorContext` for the site — skips brand/tone questions if context exists
3. AI asks one clarifying question at a time (max 4 before proceeding)
4. AI proposes a layout: named sections in order, each mapped to a type from the registry
5. User can adjust the proposal (reorder, add, remove sections) within the modal
6. AI generates field content for each section
7. User reviews generated content inline, can edit before submitting
8. "Submit for Review" → workspace draft created, modal closes with status notification

### Output rules

- Sections are always individual units — never an HTML blob
- Every generated field carries `ai_placeholder: true` in its metadata
- Image fields are always placeholders — filled afterward via `AiImageGeneratorAction`
- All prompts include a copyright guardrail: generate original content only

### Files

- `src/Filament/Actions/AiCreatorAction.php`
- `src/Actions/GenerateAiLayoutAction.php`
- `src/DataObjects/AiCreatorData.php`
- `src/Support/Pipelines/AiCreatorPipeline.php`
- `src/Support/Prompts/` — prompt templates

---

## 6. AI Image Generator

No dedicated panel page. Image generation surfaces only as an inline action.

### `AiImageGeneratorAction`

A reusable Filament action attachable to any image/media field in any form. Also callable programmatically via a data object:

```php
GenerateAiImageAction::make()->handle(new AiImageData(
    prompt: 'A professional hero banner for a law firm',
    contextFields: ['title' => 'Contact Us', 'body' => '...'],
    size: '1792x1024',
));
```

**Context gathering:** the action receives nearby Filament form field values (page title, body, etc.) as context, passed explicitly from the parent form/resource. It inspects the layout to understand what other media fields exist at the same level and uses that to weight the prompt.

**Priority for context:** page title > page body/content > layout section field label > fallback generic description.

**UX flow:**

1. User clicks "Generate with AI" on an image field
2. Modal opens showing an auto-composed prompt (editable) derived from context fields
3. "Generate" produces an image preview inside the modal
4. User can edit the prompt and click "Regenerate" as many times as needed
5. "Accept" closes the modal, saves the image to the media library, and updates the field reference directly

### Files

- `src/Filament/Actions/AiImageGeneratorAction.php`
- `src/Actions/GenerateAiImageAction.php`
- `src/DataObjects/AiImageData.php`

---

## 7. Workspace Integration

### Approval flow

When the user completes the AI Creator wizard and clicks "Submit for Review":

1. `SubmitAiCreatorDraftAction` (in assistant) is called
2. It directly calls admin's `SubmitForApprovalAction` — this is valid because `assistant` already requires `capell-app/admin` as a composer dependency
3. The returned workspace model provides the `workspace_id`
4. `ai_creator_sessions.workspace_id` is populated with the resulting workspace ID
5. Workspace metadata includes `ai_origin: true` and `ai_session_id`

### Status display

After submission, the `AiCreatorAction` modal closes and a Filament notification is shown with the workspace status and a "View in Workspace →" link to the admin compare/diff page. The resource record itself gains a workspace status badge via the existing admin extender pattern.

### Admin extender interface

Admin exposes a `WorkspaceActionExtenderInterface` (or extends the existing extender pattern used by admin's service provider). The assistant registers any workspace-level Filament actions it needs at boot, through its own service provider, without admin importing assistant classes.

```php
// In AssistantServiceProvider::boot()
$this->app->tag([AiCreatorWorkspaceAction::class], 'capell-admin:workspace-actions');
```

Admin iterates tagged workspace actions and injects them into the workspace resource at boot. This pattern mirrors the existing extender registration in `AssistantServiceProvider`.

### Files

- `src/Actions/SubmitAiCreatorDraftAction.php`
- Admin: expose `capell-admin:workspace-actions` tag (minimal change, follows existing pattern)

---

## 8. Settings Hierarchy

### Cascade

```
Global (AssistantSettings)
  └─ Site-level override (nullable → inherits global)
       └─ Store-level override (nullable → inherits site)
```

`AiCreatorPolicy::isEnabledFor($site)` resolves the cascade: checks store settings first, then site, then global.

### Resolution logic

```php
AiCreatorPolicy::isEnabledFor($site): bool
// store setting ?? site setting ?? AssistantSettings::ai_creator
```

The same cascade applies to `ai_provider`, `ai_model`, and image settings — per-site installations can use a different AI provider from the global default.

### Files

- `src/Policies/AiCreatorPolicy.php`
- `src/Settings/AssistantSettings.php` (updated)

---

## 9. Implementation Phases

### Phase 1 — Foundation

- Swap to `echolabs/prism`, build `PrismProvider`
- `SectionRegistry` + `ContentTargetContract` + `FlatJsonTarget`
- DB migrations + models (`AiCreatorContext`, `AiCreatorSession`)
- `AssistantSettings` additions + settings Filament page update

### Phase 2 — AI Creator Action + Wizard

- `AiCreatorAction` (Filament wizard action) registered on page + site resources via tags
- `AiCreatorData` DTO for programmatic invocation
- `GenerateAiLayoutAction` + `AiCreatorPipeline`
- Prompt templates with copyright guardrails + section registry injection
- Context-aware opening step (page vs site vs article)
- Session persistence (resume, stage tracking)
- Brand context loading (skip questions if context exists)

### Phase 3 — Workspace Integration

- `SubmitAiCreatorDraftAction` (calls admin's `SubmitForApprovalAction` directly)
- Admin `capell-admin:page-actions` + `capell-admin:site-actions` tags (minimal admin change, follows existing pattern)
- Post-submission notification with workspace link

### Phase 4 — Image Generator

- `AiImageGeneratorAction` (inline field action, context-aware, preview modal)
- `AiImageData` DTO for programmatic invocation
- `GenerateAiImageAction` + image provider pipeline

### Phase 5 — Polish

- `MosaicTarget` in mosaic package
- Starter prompts from section registry + history
- Resume cards in action opening step
- Per-site/store settings cascade
- Multi-page scaffolding from site-level action

---

## New Files Summary

### `capell-app/assistant`

```
src/Contracts/ContentTargetContract.php
src/Support/SectionRegistry.php
src/Support/PrismProvider.php
src/Support/ContentTargetResolver.php
src/Targets/FlatJsonTarget.php
src/Models/AiCreatorContext.php
src/Models/AiCreatorSession.php
src/Policies/AiCreatorPolicy.php
src/Settings/AssistantSettings.php               (updated)
src/Filament/Actions/AiCreatorAction.php
src/Filament/Actions/AiImageGeneratorAction.php
src/Actions/GenerateAiLayoutAction.php
src/Actions/GenerateAiImageAction.php
src/Actions/SubmitAiCreatorDraftAction.php
src/DataObjects/AiCreatorData.php
src/DataObjects/AiImageData.php
src/Support/Pipelines/AiCreatorPipeline.php
database/migrations/create_ai_creator_contexts_table.php
database/migrations/create_ai_creator_sessions_table.php
database/migrations/update_assistant_settings_add_ai_creator.php
```

### `capell-app/mosaic` (optional extension)

```
src/Targets/MosaicTarget.php
```

### `capell-app/admin` (minimal — extender interface only)

```
Expose capell-admin:workspace-actions tagged binding
```
