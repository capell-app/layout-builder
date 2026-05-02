# Package Architecture Deepening Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deepen the highest-friction Capell package modules so package seams are explicit, domain workflows live behind Actions/Data, and Support directories become package interior rather than informal public interfaces.

**Architecture:** Treat this as a staged architecture programme, not one large patch. Package seam decisions come first because they determine which packages may call each other. Each later slice moves one workflow behind a deeper interface while preserving the existing Package Surface for callers until tests prove the new seam is stable.

**Tech Stack:** PHP 8.2, Laravel, Filament, Livewire, Pest, Pest Arch, Spatie Laravel Data, Lorisleiva Actions, Capell package manifests.

---

## Scope Decisions

- `blog -> navigation` is a real dependency because Blog setup is expected to add Blog pages to Navigation.
- `assistant -> mosaic` stays optional, but Mosaic should own the Mosaic Assistant adapter/module registration.
- `campaigns -> analytics` is a real dependency because Campaign Conversion attribution and reporting depend on Analytics visits/events.
- Support modules may remain, but package callers should cross Actions/Data or declared extension points instead of calling broad Support creators/loaders directly.
- This programme should be implemented as small commits. Run the targeted package Pest command after each task and the relevant arch tests after every seam change.

## File Map

### Context

- Existing: `CONTEXT.md` — root vocabulary for the package workspace.
- Existing: `CONTEXT-MAP.md` — context ownership and product grouping map.

### Slice 1: Package Seams

- Modify: `packages/blog/capell.json`
- Modify: `packages/blog/composer.json`
- Modify: `packages/blog/tests/Arch/BlogPackageTest.php`
- Modify: `packages/assistant/src/Providers/AssistantServiceProvider.php`
- Modify: `packages/assistant/src/Integrations/Mosaic/MosaicAssistantModule.php`
- Modify: `packages/assistant/src/Integrations/Mosaic/PreviewMosaicLayoutPlanAction.php`
- Modify: `packages/assistant/tests/Feature/MosaicAssistantModuleTest.php`
- Modify: `packages/mosaic/src/Providers/MosaicServiceProvider.php`
- Create: `packages/mosaic/src/Assistant/MosaicAssistantModule.php`
- Create: `packages/mosaic/src/Assistant/PreviewMosaicLayoutPlanAction.php`
- Create: `packages/assistant/tests/Arch/AssistantBoundaryTest.php`
- Modify: `packages/campaigns/capell.json`
- Modify: `packages/campaigns/composer.json`
- Modify: `packages/campaigns/tests/CampaignsTestCase.php`
- Create: `packages/campaigns/tests/Arch/CampaignsBoundaryTest.php`

### Slice 2: Workspaces Page Import Workflow

- Modify: `packages/workspaces/src/Filament/Pages/ImportPagesPage.php`
- Create: `packages/workspaces/src/Actions/Imports/StartPageImportAction.php`
- Create: `packages/workspaces/src/Actions/Imports/AdvancePageImportToValidationAction.php`
- Create: `packages/workspaces/src/Actions/Imports/DispatchPageImportAction.php`
- Create: `packages/workspaces/src/Actions/Imports/RefreshPageImportStatusAction.php`
- Create: `packages/workspaces/src/Data/Imports/PageImportWizardStateData.php`
- Create: `packages/workspaces/src/Data/Imports/PageImportDecisionData.php`
- Create: `packages/workspaces/src/Data/Imports/PageImportStatusData.php`
- Test: `packages/workspaces/tests/Admin/Feature/Filament/Pages/ImportPagesPageTest.php`
- Test: `packages/workspaces/tests/Admin/Feature/Actions/Imports/StartPageImportActionTest.php`
- Test: `packages/workspaces/tests/Admin/Feature/Actions/Imports/PageImportWorkflowActionTest.php`

### Slice 3: SEO AI Generation Workflows

- Modify: `packages/seo-tools/src/Actions/GenerateAiLayoutAction.php`
- Modify: `packages/seo-tools/src/Actions/GeneratorPageContentAction.php`
- Modify: `packages/seo-tools/src/Actions/SuggestPageTitlesAction.php`
- Modify: `packages/seo-tools/src/Actions/SuggestMetaDescriptionsAction.php`
- Modify: `packages/seo-tools/src/Support/Pipelines/AiCreatorPipeline.php`
- Modify: `packages/seo-tools/src/Support/Pipelines/GenerateContentPipeline.php`
- Modify: `packages/seo-tools/src/Support/Pipelines/SuggestTitlesPipeline.php`
- Modify: `packages/seo-tools/src/Support/Pipelines/SuggestMetaDescriptionsPipeline.php`
- Create: `packages/seo-tools/src/Data/Ai/AiGenerationInputData.php`
- Create: `packages/seo-tools/src/Data/Ai/AiGenerationResultData.php`
- Create: `packages/seo-tools/src/Actions/Ai/RecordAiGenerationAction.php`
- Test: `packages/seo-tools/tests/Feature/Actions/AiGenerationWorkflowTest.php`
- Test: `packages/seo-tools/tests/Unit/Pipelines/GenerateContentPipelineTest.php`
- Test: `packages/seo-tools/tests/Unit/Pipelines/AiCreatorPipelineTest.php`

### Slice 4: Blog Publishing Surface

- Modify: `packages/blog/src/Support/Creator/BlogCreator.php`
- Modify: `packages/blog/src/Actions/CreateBlogPagesAction.php`
- Modify: `packages/blog/src/Actions/InstallPackageAction.php`
- Modify: `packages/blog/src/Providers/AdminServiceProvider.php`
- Modify: `packages/blog/database/factories/ArticleFactory.php`
- Create: `packages/blog/src/Actions/EnsureBlogPublishingSurfaceAction.php`
- Create: `packages/blog/src/Actions/EnsureArticlePublishingDefaultsAction.php`
- Create: `packages/blog/src/Data/BlogPublishingSurfaceData.php`
- Test: `packages/blog/tests/Integration/Actions/EnsureBlogPublishingSurfaceActionTest.php`
- Test: `packages/blog/tests/Integration/Actions/EnsureArticlePublishingDefaultsActionTest.php`
- Test: `packages/blog/tests/Arch/BlogPackageTest.php`

### Slice 5: Mosaic Demo And Widget Catalog

- Modify: `packages/mosaic/src/Support/Creator/DemoCreator.php`
- Modify: `packages/mosaic/src/Support/Creator/WidgetCreator.php`
- Modify: `packages/mosaic/src/Actions/InstallPackageAction.php`
- Modify: `packages/mosaic/src/Console/Commands/DemoCommand.php`
- Create: `packages/mosaic/src/Actions/InstallMosaicWidgetCatalogAction.php`
- Create: `packages/mosaic/src/Actions/CreateMosaicDemoSiteAction.php`
- Create: `packages/mosaic/src/Data/WidgetDefinitionData.php`
- Create: `packages/mosaic/src/Data/DemoSitePlanData.php`
- Test: `packages/mosaic/tests/Integration/Actions/InstallMosaicWidgetCatalogActionTest.php`
- Test: `packages/mosaic/tests/Integration/Actions/CreateMosaicDemoSiteActionTest.php`
- Test: `packages/mosaic/tests/Arch/LayoutPackageTest.php`

### Slice 6: Navigation Render Model

- Modify: `packages/navigation/src/Support/Loader/NavigationItemsLoader.php`
- Modify: `packages/mosaic/src/View/Components/Widget/Navigation.php`
- Modify: `packages/theme-default/resources/views/components/header/index.blade.php`
- Modify: `packages/mosaic/resources/views/components/widget/navigation/index.blade.php`
- Create: `packages/navigation/src/Actions/BuildNavigationRenderModelAction.php`
- Create: `packages/navigation/src/Data/NavigationRenderContextData.php`
- Create: `packages/navigation/src/Data/NavigationItemRenderData.php`
- Create: `packages/navigation/src/Data/NavigationRenderData.php`
- Test: `packages/navigation/tests/Integration/Actions/BuildNavigationRenderModelActionTest.php`
- Test: `packages/navigation/tests/Integration/Loader/NavigationItemsLoaderTest.php`
- Test: `packages/navigation/tests/Arch/NavigationBoundaryTest.php`

## Task 0: Protect The Existing Worktree

**Files:**

- Read-only: repository status.

- [ ] **Step 1: Check the worktree before implementation**

Run:

```bash
git status --short --untracked-files=all
git diff --name-only --diff-filter=U
```

Expected: existing unrelated changes may be present, but there must be no unresolved files. At the time this plan was written, unrelated modified files existed in Mosaic, Navigation, Tags, and Workspaces; do not revert them.

- [ ] **Step 2: Commit or intentionally preserve the context files**

Run:

```bash
git diff -- CONTEXT.md CONTEXT-MAP.md
```

Expected: only the root context vocabulary and context map are shown. If implementing this programme immediately, include those docs in the first architecture commit.

## Task 1: Lock Package Seam Decisions

**Files:**

- Modify: `packages/blog/capell.json`
- Modify: `packages/blog/composer.json`
- Modify: `packages/blog/tests/Arch/BlogPackageTest.php`
- Modify: `packages/campaigns/capell.json`
- Modify: `packages/campaigns/composer.json`
- Modify: `packages/campaigns/tests/CampaignsTestCase.php`
- Create: `packages/assistant/tests/Arch/AssistantBoundaryTest.php`
- Create: `packages/campaigns/tests/Arch/CampaignsBoundaryTest.php`

- [ ] **Step 1: Write failing arch coverage for the package seam decisions**

Add assertions that:

- Blog may use Navigation because Navigation is declared in both package manifests.
- Assistant may not import `Capell\Mosaic` from its shared package surface.
- Campaigns may use Analytics because Analytics is declared as required, not optional.

Run:

```bash
vendor/bin/pest packages/blog/tests/Arch packages/assistant/tests packages/campaigns/tests --filter='Boundary|Package|Isolation'
```

Expected before implementation: at least Assistant and Campaigns seam assertions fail.

- [ ] **Step 2: Make Blog's Navigation dependency explicit**

Update `packages/blog/capell.json` so `requires` includes `capell-app/navigation`.

Update `packages/blog/composer.json` so Composer also requires the local Navigation package in the same style as existing package dependencies.

- [ ] **Step 3: Make Campaigns require Analytics**

Move `capell-app/analytics` from `optional` to `requires` in `packages/campaigns/capell.json`.

Update `packages/campaigns/composer.json` to require the Analytics package.

Update `packages/campaigns/tests/CampaignsTestCase.php` so Analytics is loaded as part of the expected package stack rather than only when a class happens to exist.

- [ ] **Step 4: Move Mosaic Assistant registration out of Assistant**

Remove Mosaic-specific registration from `packages/assistant/src/Providers/AssistantServiceProvider.php`.

Create Mosaic-owned Assistant integration classes under `packages/mosaic/src/Assistant/`.

Register the Mosaic Assistant module from `packages/mosaic/src/Providers/MosaicServiceProvider.php` only when Assistant classes are available and the Assistant package is installed.

- [ ] **Step 5: Run seam tests**

Run:

```bash
vendor/bin/pest packages/blog/tests/Arch packages/assistant/tests packages/campaigns/tests
```

Expected: PASS.

- [ ] **Step 6: Commit seam decisions**

Run:

```bash
git add CONTEXT.md CONTEXT-MAP.md packages/blog packages/assistant packages/mosaic packages/campaigns
git commit -m "refactor: lock package architecture seams"
```

## Task 2: Deepen Workspaces Page Import

**Files:**

- Modify: `packages/workspaces/src/Filament/Pages/ImportPagesPage.php`
- Create: `packages/workspaces/src/Actions/Imports/StartPageImportAction.php`
- Create: `packages/workspaces/src/Actions/Imports/AdvancePageImportToValidationAction.php`
- Create: `packages/workspaces/src/Actions/Imports/DispatchPageImportAction.php`
- Create: `packages/workspaces/src/Actions/Imports/RefreshPageImportStatusAction.php`
- Create: `packages/workspaces/src/Data/Imports/PageImportWizardStateData.php`
- Create: `packages/workspaces/src/Data/Imports/PageImportDecisionData.php`
- Create: `packages/workspaces/src/Data/Imports/PageImportStatusData.php`

- [ ] **Step 1: Write Action tests for each wizard transition**

Cover upload-to-review, review-to-resolve, resolve-to-validate, validate-to-executing, executing-to-completed, and executing-to-failed.

Run:

```bash
vendor/bin/pest packages/workspaces/tests/Admin/Feature/Actions/Imports
```

Expected before implementation: FAIL because the Actions/Data do not exist.

- [ ] **Step 2: Move parse/session/workspace creation into `StartPageImportAction`**

The Action owns package reading, manifest validation, Workspace creation, Import Session creation, resolution map persistence, review rows, resolve rows, and initial wizard state.

- [ ] **Step 3: Move decision sanitising and validation into `AdvancePageImportToValidationAction`**

The Action owns page decisions, relation decisions, blocking workspace conflict detection, relation decision validation, validation summary persistence, and confirmation target derivation.

- [ ] **Step 4: Move queue dispatch into `DispatchPageImportAction`**

The Action owns blocking validation errors, confirmation matching, status transition to queued, and `ExecuteImportPlanJob` dispatch.

- [ ] **Step 5: Move polling into `RefreshPageImportStatusAction`**

The Action owns reading Import Session status and returning terminal wizard state.

- [ ] **Step 6: Thin `ImportPagesPage`**

Keep Filament form schema, public properties needed for Livewire hydration, Notification rendering, and calls into the new Actions. Remove Backup package interior usage from private helper methods where the new Actions now own the workflow.

- [ ] **Step 7: Run Workspaces import tests**

Run:

```bash
vendor/bin/pest packages/workspaces/tests/Admin/Feature/Filament/Pages/ImportPagesPageTest.php packages/workspaces/tests/Admin/Feature/Actions/Imports
```

Expected: PASS.

- [ ] **Step 8: Commit Workspaces import deepening**

Run:

```bash
git add packages/workspaces
git commit -m "refactor(workspaces): deepen page import workflow"
```

## Task 3: Deepen SEO AI Generation

**Files:**

- Modify: `packages/seo-tools/src/Actions/GenerateAiLayoutAction.php`
- Modify: `packages/seo-tools/src/Actions/GeneratorPageContentAction.php`
- Modify: `packages/seo-tools/src/Support/Pipelines/*.php`
- Create: `packages/seo-tools/src/Data/Ai/AiGenerationInputData.php`
- Create: `packages/seo-tools/src/Data/Ai/AiGenerationResultData.php`
- Create: `packages/seo-tools/src/Actions/Ai/RecordAiGenerationAction.php`

- [ ] **Step 1: Write workflow tests around Action outcomes**

Tests must assert rate-limit checks, prompt rendering, provider call parameters, parsed/sanitised results, session update where applicable, and `AIGenerationHistory` persistence.

Run:

```bash
vendor/bin/pest packages/seo-tools/tests/Feature/Actions/AiGenerationWorkflowTest.php
```

Expected before implementation: FAIL because the workflow Data/Action seam does not exist.

- [ ] **Step 2: Introduce AI generation Data**

Create input/result Data classes that carry action key, context, options, provider request, provider response, parsed output, persistence metadata, and any AI Creator session identifiers.

- [ ] **Step 3: Move persistence into `RecordAiGenerationAction`**

All `AIGenerationHistory` writes should pass through this Action.

- [ ] **Step 4: Keep pipeline internals private to the Action seam**

Actions become the public module interface. Pipelines may remain as internal implementation modules, but callers and tests should not need to understand payload arrays.

- [ ] **Step 5: Run SEO AI tests**

Run:

```bash
vendor/bin/pest packages/seo-tools/tests/Feature/Actions/AiGenerationWorkflowTest.php packages/seo-tools/tests/Unit/Pipelines
```

Expected: PASS.

- [ ] **Step 6: Commit SEO AI deepening**

Run:

```bash
git add packages/seo-tools
git commit -m "refactor(seo-tools): deepen ai generation workflows"
```

## Task 4: Deepen Blog Publishing Setup

**Files:**

- Modify: `packages/blog/src/Support/Creator/BlogCreator.php`
- Modify: `packages/blog/src/Actions/CreateBlogPagesAction.php`
- Modify: `packages/blog/src/Actions/InstallPackageAction.php`
- Modify: `packages/blog/src/Providers/AdminServiceProvider.php`
- Create: `packages/blog/src/Actions/EnsureBlogPublishingSurfaceAction.php`
- Create: `packages/blog/src/Actions/EnsureArticlePublishingDefaultsAction.php`
- Create: `packages/blog/src/Data/BlogPublishingSurfaceData.php`

- [ ] **Step 1: Write tests for Blog package interface**

Cover installing page types, layouts, widgets, Blog pages, archive/tag pages, translations, URLs, and Navigation links through Actions.

Run:

```bash
vendor/bin/pest packages/blog/tests/Integration/Actions/EnsureBlogPublishingSurfaceActionTest.php packages/blog/tests/Integration/Actions/EnsureArticlePublishingDefaultsActionTest.php
```

Expected before implementation: FAIL because the new Action seam does not exist.

- [ ] **Step 2: Introduce `EnsureBlogPublishingSurfaceAction`**

Move site-specific page creation and Navigation linking behind this Action. Return `BlogPublishingSurfaceData` containing the Blog page, Archives page, Archive page, Tags page, and Tag page identifiers.

- [ ] **Step 3: Introduce `EnsureArticlePublishingDefaultsAction`**

Move article type/layout/widget defaults behind this Action.

- [ ] **Step 4: Make `BlogCreator` package interior**

Keep granular creation helpers in `BlogCreator` where useful, but stop providers/factories/Filament schema from calling broad setup methods directly.

- [ ] **Step 5: Run Blog tests**

Run:

```bash
vendor/bin/pest packages/blog/tests/Integration/Actions packages/blog/tests/Feature/Pages packages/blog/tests/Arch
```

Expected: PASS.

- [ ] **Step 6: Commit Blog publishing deepening**

Run:

```bash
git add packages/blog
git commit -m "refactor(blog): deepen publishing setup actions"
```

## Task 5: Deepen Mosaic Widget Catalog And Demo Creation

**Files:**

- Modify: `packages/mosaic/src/Support/Creator/DemoCreator.php`
- Modify: `packages/mosaic/src/Support/Creator/WidgetCreator.php`
- Modify: `packages/mosaic/src/Actions/InstallPackageAction.php`
- Modify: `packages/mosaic/src/Console/Commands/DemoCommand.php`
- Create: `packages/mosaic/src/Actions/InstallMosaicWidgetCatalogAction.php`
- Create: `packages/mosaic/src/Actions/CreateMosaicDemoSiteAction.php`
- Create: `packages/mosaic/src/Data/WidgetDefinitionData.php`
- Create: `packages/mosaic/src/Data/DemoSitePlanData.php`

- [ ] **Step 1: Write tests for the new Mosaic Actions**

Cover widget catalog installation, idempotency, translations, key meta fields, demo media attachment, demo section creation, and Navigation adapter calls.

Run:

```bash
vendor/bin/pest packages/mosaic/tests/Integration/Actions/InstallMosaicWidgetCatalogActionTest.php packages/mosaic/tests/Integration/Actions/CreateMosaicDemoSiteActionTest.php
```

Expected before implementation: FAIL because the Actions/Data do not exist.

- [ ] **Step 2: Move catalog definitions into `WidgetDefinitionData`**

Represent each widget definition as structured Data. Keep translation writes and Eloquent persistence inside `InstallMosaicWidgetCatalogAction`.

- [ ] **Step 3: Move demo composition into `CreateMosaicDemoSiteAction`**

The Action owns demo plan execution. `DemoCreator` can provide internal helper methods, but `DemoCommand` should call the Action seam.

- [ ] **Step 4: Run Mosaic tests**

Run:

```bash
vendor/bin/pest packages/mosaic/tests/Integration/Actions packages/mosaic/tests/Feature/Widgets packages/mosaic/tests/Arch
```

Expected: PASS.

- [ ] **Step 5: Commit Mosaic deepening**

Run:

```bash
git add packages/mosaic
git commit -m "refactor(mosaic): deepen widget catalog and demo creation"
```

## Task 6: Deepen Navigation Rendering

**Files:**

- Modify: `packages/navigation/src/Support/Loader/NavigationItemsLoader.php`
- Modify: `packages/mosaic/src/View/Components/Widget/Navigation.php`
- Modify: `packages/theme-default/resources/views/components/header/index.blade.php`
- Modify: `packages/mosaic/resources/views/components/widget/navigation/index.blade.php`
- Create: `packages/navigation/src/Actions/BuildNavigationRenderModelAction.php`
- Create: `packages/navigation/src/Data/NavigationRenderContextData.php`
- Create: `packages/navigation/src/Data/NavigationItemRenderData.php`
- Create: `packages/navigation/src/Data/NavigationRenderData.php`

- [ ] **Step 1: Write render model tests**

Cover current page active state, child active state, auto-child expansion, URL generation, current Site/Language/Domain context, and cache clearing.

Run:

```bash
vendor/bin/pest packages/navigation/tests/Integration/Actions/BuildNavigationRenderModelActionTest.php
```

Expected before implementation: FAIL because the render model Action/Data do not exist.

- [ ] **Step 2: Add Navigation render Data**

Create context, item, and aggregate render Data classes. They should contain only view-ready fields.

- [ ] **Step 3: Wrap `NavigationItemsLoader` behind the Action**

Keep the existing loader as implementation while moving construction invariants into `BuildNavigationRenderModelAction`.

- [ ] **Step 4: Update callers**

Blade and widget callers should receive or request render Data. They should not instantiate `NavigationItemsLoader` directly.

- [ ] **Step 5: Run Navigation and affected frontend tests**

Run:

```bash
vendor/bin/pest packages/navigation/tests packages/mosaic/tests/Feature/Widgets/Navigation packages/theme-default/tests
```

Expected: PASS.

- [ ] **Step 6: Commit Navigation render deepening**

Run:

```bash
git add packages/navigation packages/mosaic packages/theme-default
git commit -m "refactor(navigation): deepen frontend render model"
```

## Final Verification

- [ ] **Step 1: Run package-focused suites**

Run:

```bash
vendor/bin/pest packages/blog/tests packages/mosaic/tests packages/navigation/tests packages/workspaces/tests/Admin packages/seo-tools/tests packages/assistant/tests packages/campaigns/tests
```

Expected: PASS.

- [ ] **Step 2: Run full repo tests when the worktree is clean enough**

Run:

```bash
composer test
```

Expected: PASS.

- [ ] **Step 3: Run preflight before merge**

Run:

```bash
composer preflight
```

Expected: PASS.

## Self-Review Notes

- This plan covers every architecture candidate accepted in the review.
- The package seam decisions happen first so later slices do not build on ambiguous dependencies.
- Campaigns requires Analytics in this plan.
- Assistant/Mosaic optional collaboration is moved to Mosaic ownership.
- Blog/Navigation collaboration is made explicit.
- Each workflow slice has its own tests and commit.
