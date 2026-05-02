# Content Blocks Package Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extract Mosaic sections into optional `capell-app/content-blocks` while preserving the existing section workflow when Content Blocks and Mosaic are both installed.

**Architecture:** Content Blocks owns the former Section domain and registers reusable content as a first-class asset. Mosaic owns layouts/widgets and exposes an adapter registry so optional packages can contribute layout assets without Mosaic importing them directly.

**Tech Stack:** PHP 8.2, Laravel package tools, Filament 4/5, Livewire, Pest, Capell core/admin/frontend extension points.

---

## File Structure

- Create `packages/content-blocks/composer.json`, `capell.json`, language files, migrations, factories, service provider, model, observer, enums, actions, Filament resources/configurators, Livewire selector, and tests.
- Create Mosaic bridge contracts in `packages/mosaic/src/Contracts` and support registry in `packages/mosaic/src/Support`.
- Modify Mosaic provider/enums/models/forms/loaders to remove direct Section ownership and use the bridge registry for optional content block assets.
- Modify root `composer.json`, `composer.local.json`, package manifest arch tests, and shared test bootstrap autoload/morph map entries.

### Task 1: Register the New Package Shell

**Files:**

- Create: `packages/content-blocks/composer.json`
- Create: `packages/content-blocks/capell.json`
- Create: `packages/content-blocks/src/Providers/ContentBlocksServiceProvider.php`
- Modify: `composer.json`
- Modify: `composer.local.json`
- Modify: `tests/Packages/Arch/ProductGroupManifestTest.php`

- [ ] **Step 1: Write the manifest/autoload expectation**

Add `foundation/content-blocks/capell.json` to the expected foundation bundle list in `tests/Packages/Arch/ProductGroupManifestTest.php`.

- [ ] **Step 2: Create package manifests**

Create Composer package `capell-app/content-blocks` with PSR-4 namespaces:

```json
"Capell\\ContentBlocks\\": "src/",
"Capell\\ContentBlocks\\Database\\Factories\\": "database/factories"
```

Create `capell.json` requiring `capell-app/core`, `capell-app/admin`, and `capell-app/frontend`, with provider `Capell\\ContentBlocks\\Providers\\ContentBlocksServiceProvider`.

- [ ] **Step 3: Register monorepo autoload**

Add `Capell\\ContentBlocks\\`, factory, and test namespaces to both root composer files.

- [ ] **Step 4: Verify shell registration**

Run: `vendor/bin/pest tests/Packages/Arch/ProductGroupManifestTest.php tests/Packages/Arch/ManifestProviderClassExistsTest.php`

Expected: package manifest tests pass.

### Task 2: Move Section Domain Into Content Blocks

**Files:**

- Move/create: `packages/content-blocks/src/Models/ContentBlock.php`
- Move/create: `packages/content-blocks/src/Observers/ContentBlockObserver.php`
- Move/create: `packages/content-blocks/database/factories/ContentBlockFactory.php`
- Move/create: `packages/content-blocks/database/migrations/create_content_blocks_table.php`
- Move/create: `packages/content-blocks/resources/lang/en/*.php`
- Modify: `packages/mosaic/src/Models/Widget.php`
- Modify: `packages/mosaic/src/Models/WidgetAsset.php`

- [ ] **Step 1: Add ContentBlock model by renaming Section**

Move `Capell\Mosaic\Models\Section` to `Capell\ContentBlocks\Models\ContentBlock`, setting `$table = 'content_blocks'`, the existing fillable/casts/relations, and morph relation behavior. Update docblocks to use `ContentBlock`.

- [ ] **Step 2: Move observer and factory**

Move `SectionObserver` to `ContentBlockObserver` and `SectionFactory` to `ContentBlockFactory`. Preserve current behavior that assigns the default section/content-block type and nested set housekeeping.

- [ ] **Step 3: Move migration and language strings**

Move `create_content_blocks_table.php` into Content Blocks for first-pass data continuity. Copy section-related language keys from Mosaic to `capell-content-blocks`.

- [ ] **Step 4: Remove direct Section relationship from Widget**

Delete the hard-coded `sections()` relation from `Capell\Mosaic\Models\Widget`. Replace downstream callers with the bridge registry in later tasks.

- [ ] **Step 5: Run focused model tests**

Run: `vendor/bin/pest packages/content-blocks/tests/Integration/Models`

Expected: content block model tests pass after test files are moved in Task 7.

### Task 3: Add Mosaic Layout Asset Bridge

**Files:**

- Create: `packages/mosaic/src/Contracts/LayoutAssetBridge.php`
- Create: `packages/mosaic/src/Data/LayoutAssetBridgeData.php`
- Create: `packages/mosaic/src/Support/LayoutAssetBridgeRegistry.php`
- Modify: `packages/mosaic/src/Providers/MosaicServiceProvider.php`
- Modify: `packages/mosaic/src/Livewire/Assets/Table/AbstractAssets.php`
- Modify: `packages/mosaic/src/Livewire/Assets/Table/PageAssets.php`

- [ ] **Step 1: Create bridge data object**

`LayoutAssetBridgeData` stores asset key, model class, label, icon, color, component, form class, create action, default-data action, translation support, and optional Livewire table class.

- [ ] **Step 2: Create registry**

`LayoutAssetBridgeRegistry` supports `register(LayoutAssetBridgeData $asset): void`, `all(): array`, `get(string $key): ?LayoutAssetBridgeData`, and `has(string $key): bool`.

- [ ] **Step 3: Register registry in Mosaic provider**

Bind the registry as a singleton during `registeringPackage()` before layout builder components boot.

- [ ] **Step 4: Convert Mosaic asset lookup points**

Update Mosaic asset selector/rendering paths to read bridge assets from the registry in addition to core page assets. Do not import `Capell\ContentBlocks`.

- [ ] **Step 5: Verify Mosaic boots without Content Blocks**

Run: `vendor/bin/pest packages/mosaic/tests/Arch/LayoutPackageTest.php`

Expected: no Content Blocks classes are required for Mosaic package loading.

### Task 4: Register Content Blocks With Core/Admin/Frontend and Mosaic Bridge

**Files:**

- Modify: `packages/content-blocks/src/Providers/ContentBlocksServiceProvider.php`
- Create: `packages/content-blocks/src/Enums/ContentBlockAssetEnum.php`
- Create: `packages/content-blocks/src/Enums/ContentBlockTypeEnum.php`
- Create: `packages/content-blocks/src/Support/Mosaic/ContentBlockLayoutAssetBridge.php`

- [ ] **Step 1: Register package metadata**

Use `CapellCore::registerPackage()` with package name `capell-app/content-blocks`, install/setup command metadata when commands exist, and translations from `capell-content-blocks`.

- [ ] **Step 2: Register content block asset and page type**

Register a page/content type for the `content_block` type value and a core/admin/frontend asset with key `content_block` so existing widget asset rows still resolve.

- [ ] **Step 3: Register Mosaic bridge conditionally**

In Content Blocks provider, check `class_exists(\Capell\Mosaic\Support\LayoutAssetBridgeRegistry::class)`. If present, resolve the registry and register the `content_block` bridge data. This is the only integration point and must remain conditional.

- [ ] **Step 4: Register relationships**

Register `Page::contentBlocks()`, `Page::widgetAssets()`, `Site::contentBlocks()`, and `Type::contentBlocks()` from Content Blocks. Preserve old relation names only where tests or existing user code prove they are needed.

- [ ] **Step 5: Register workspaces conditionally**

If `Capell\Workspaces\WorkspaceRegistry` exists, register `ContentBlock::class`.

### Task 5: Move Filament and Livewire Section UI

**Files:**

- Move/create: `packages/content-blocks/src/Filament/Resources/ContentBlocks/*`
- Move/create: `packages/content-blocks/src/Filament/Configurators/ContentBlocks/*`
- Move/create: `packages/content-blocks/src/Livewire/Assets/Table/ContentBlockAssets.php`
- Move/create: `packages/content-blocks/resources/views/components/content-block/*.blade.php`
- Modify: moved namespaces and translations.

- [ ] **Step 1: Move Filament resource tree**

Move `Filament/Resources/Sections` to `Filament/Resources/ContentBlocks`, rename classes from `Section*` to `ContentBlock*`, and keep route slugs/labels compatible where practical.

- [ ] **Step 2: Move configurators**

Move `DefaultSectionConfigurator`, `HeroSectionConfigurator`, and `TestimonialSectionConfigurator` to Content Blocks. Rename enum to `ContentBlockConfiguratorEnum`.

- [ ] **Step 3: Move Livewire asset table**

Move `SectionAssets` to `ContentBlockAssets`, keeping the Livewire alias compatible through bridge registration.

- [ ] **Step 4: Move Blade asset views**

Move `resources/views/components/section/*` to Content Blocks and update component namespaces.

- [ ] **Step 5: Run focused UI tests**

Run: `vendor/bin/pest packages/content-blocks/tests/Feature/Filament packages/content-blocks/tests/Feature/Livewire`

Expected: moved UI tests pass.

### Task 6: Remove Section Ownership From Mosaic

**Files:**

- Modify: `packages/mosaic/src/Providers/MosaicServiceProvider.php`
- Modify: `packages/mosaic/src/Enums/LayoutTypeEnum.php`
- Modify: `packages/mosaic/src/Enums/ResourceEnum.php`
- Modify: `packages/mosaic/src/Enums/TypeEnum.php`
- Modify: `packages/mosaic/src/Enums/AssetEnum.php`
- Modify: `packages/mosaic/src/Enums/ConfiguratorTypeEnum.php`
- Modify: `packages/mosaic/src/Enums/LivewireComponentsEnum.php`
- Modify: `packages/mosaic/src/Support/LayoutModelRegistrar.php`
- Modify: `packages/mosaic/src/Support/Loader/LayoutLoader.php`
- Modify: Mosaic creators/demo commands that currently create sections.

- [ ] **Step 1: Strip provider section registration**

Remove section resource, section asset, section model, section relationships, section events, and section workspace registration from Mosaic.

- [ ] **Step 2: Keep only widget/layout type ownership**

Update Mosaic enums so Mosaic owns Widget/Layout concepts only. Section/content-block type data comes from Content Blocks.

- [ ] **Step 3: Make demos conditional**

Move content block demo creation into Content Blocks or guard Mosaic demo paths so missing Content Blocks does not fail package boot.

- [ ] **Step 4: Run Mosaic package tests**

Run: `vendor/bin/pest packages/mosaic/tests`

Expected: Mosaic tests pass after section tests are moved or rewritten.

### Task 7: Move and Rewrite Tests

**Files:**

- Move: Mosaic section tests to `packages/content-blocks/tests`
- Create: `packages/content-blocks/tests/Arch/ContentBlocksPackageTest.php`
- Create: `packages/mosaic/tests/Feature/ContentBlocksOptionalBootTest.php`
- Create: `packages/mosaic/tests/Feature/ContentBlocksBridgeTest.php`
- Modify: `tests/AbstractTestCase.php`

- [ ] **Step 1: Move section tests**

Move tests under `packages/mosaic/tests/Feature/Filament/Resources/Section`, section model tests, and section asset Livewire tests into Content Blocks, updating namespaces/imports.

- [ ] **Step 2: Add arch boundary tests**

Assert Content Blocks does not use `Capell\Mosaic\` except optional bridge files if needed, and Mosaic does not use `Capell\ContentBlocks\` in core classes.

- [ ] **Step 3: Add optional boot test**

Test Mosaic provider registration without Content Blocks bridge registration and assert no section class is required.

- [ ] **Step 4: Add bridge integration test**

With both packages registered, create a `ContentBlock`, attach it to a Mosaic widget asset through the bridge key `content_block`, and assert the existing render/selection path sees it.

- [ ] **Step 5: Run package test slices**

Run: `vendor/bin/pest packages/content-blocks/tests packages/mosaic/tests`

Expected: Content Blocks and Mosaic tests pass together.

### Task 8: Final Verification

**Files:**

- All changed package, test, and composer files.

- [ ] **Step 1: Dump autoload**

Run: `COMPOSER=composer.local.json composer dump-autoload --no-scripts`

Expected: autoload generation succeeds.

- [ ] **Step 2: Run focused test suite**

Run: `vendor/bin/pest packages/content-blocks/tests packages/mosaic/tests tests/Packages/Arch`

Expected: focused tests pass.

- [ ] **Step 3: Run static checks for boundary leaks**

Run: `rg -n 'Capell\\\\Mosaic' packages/content-blocks/src packages/content-blocks/tests`

Expected: only explicitly approved optional bridge file references, or no matches.

Run: `rg -n 'Capell\\\\ContentBlocks' packages/mosaic/src`

Expected: no matches.

- [ ] **Step 4: Run formatting**

Run: `composer lint`

Expected: Pint passes or formats only touched files.
