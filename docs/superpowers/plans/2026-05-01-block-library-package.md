# Block Library Package Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Architecture:** Block Library owns the former Section domain and registers reusable content as a first-class asset. LayoutBuilder owns layouts/widgets and exposes an adapter registry so optional packages can contribute layout assets without LayoutBuilder importing them directly.

**Tech Stack:** PHP 8.2, Laravel package tools, Filament 4/5, Livewire, Pest, Capell core/admin/frontend extension points.

---

## File Structure

- Create LayoutBuilder bridge contracts in `packages/layout-builder/src/Contracts` and support registry in `packages/layout-builder/src/Support`.
- Modify LayoutBuilder provider/enums/models/form-builder/loaders to remove direct Section ownership and use the bridge registry for optional content block assets.
- Modify root `composer.json`, `composer.local.json`, package manifest arch tests, and shared test bootstrap autoload/morph map entries.

### Task 1: Register the New Package Shell

**Files:**

- Modify: `composer.json`
- Modify: `composer.local.json`
- Modify: `tests/Packages/Arch/ProductGroupManifestTest.php`

- [ ] **Step 1: Write the manifest/autoload expectation**

- [ ] **Step 2: Create package manifests**

```json

```

- [ ] **Step 3: Register monorepo autoload**

- [ ] **Step 4: Verify shell registration**

Run: `vendor/bin/pest tests/Packages/Arch/ProductGroupManifestTest.php tests/Packages/Arch/ManifestProviderClassExistsTest.php`

Expected: package manifest tests pass.

### Task 2: Move Section Domain Into Block Library

**Files:**

- Modify: `packages/layout-builder/src/Models/Widget.php`
- Modify: `packages/layout-builder/src/Models/WidgetAsset.php`

- [ ] **Step 2: Move observer and factory**

- [ ] **Step 3: Move migration and language strings**

- [ ] **Step 4: Remove direct Section relationship from Widget**

Delete the hard-coded `sections()` relation from `Capell\LayoutBuilder\Models\Widget`. Replace downstream callers with the bridge registry in later tasks.

- [ ] **Step 5: Run focused model tests**

Expected: content block model tests pass after test files are moved in Task 7.

### Task 3: Add LayoutBuilder Layout Asset Bridge

**Files:**

- Create: `packages/layout-builder/src/Contracts/LayoutAssetBridge.php`
- Create: `packages/layout-builder/src/Data/LayoutAssetBridgeData.php`
- Create: `packages/layout-builder/src/Support/LayoutAssetBridgeRegistry.php`
- Modify: `packages/layout-builder/src/Providers/LayoutBuilderServiceProvider.php`
- Modify: `packages/layout-builder/src/Livewire/Assets/Table/AbstractAssets.php`
- Modify: `packages/layout-builder/src/Livewire/Assets/Table/PageAssets.php`

- [ ] **Step 1: Create bridge data object**

`LayoutAssetBridgeData` stores asset key, model class, label, icon, color, component, form class, create action, default-data action, translation support, and optional Livewire table class.

- [ ] **Step 2: Create registry**

`LayoutAssetBridgeRegistry` supports `register(LayoutAssetBridgeData $asset): void`, `all(): array`, `get(string $key): ?LayoutAssetBridgeData`, and `has(string $key): bool`.

- [ ] **Step 3: Register registry in LayoutBuilder provider**

Bind the registry as a singleton during `registeringPackage()` before layout builder components boot.

- [ ] **Step 4: Convert LayoutBuilder asset lookup points**

- [ ] **Step 5: Verify LayoutBuilder boots without Block Library**

Run: `vendor/bin/pest packages/layout-builder/tests/Arch/LayoutPackageTest.php`

Expected: no Block Library classes are required for LayoutBuilder package loading.

### Task 4: Register Block Library With Core/Admin/Frontend and LayoutBuilder Bridge

**Files:**

- [ ] **Step 1: Register package metadata**

- [ ] **Step 2: Register content block asset and page type**

- [ ] **Step 3: Register LayoutBuilder bridge conditionally**

- [ ] **Step 4: Register relationships**

Register `Page::contentBlocks()`, `Page::widgetAssets()`, `Site::contentBlocks()`, and `Type::contentBlocks()` from Block Library. Preserve old relation names only where tests or existing user code prove they are needed.

- [ ] **Step 5: Register publishing-studio conditionally**

### Task 5: Move Filament and Livewire Section UI

**Files:**

- Modify: moved namespaces and translations.

- [ ] **Step 1: Move Filament resource tree**

- [ ] **Step 2: Move configurators**

- [ ] **Step 3: Move Livewire asset table**

- [ ] **Step 4: Move Blade asset views**

Move `resources/views/components/section/*` to Block Library and update component namespaces.

- [ ] **Step 5: Run focused UI tests**

Expected: moved UI tests pass.

### Task 6: Remove Section Ownership From LayoutBuilder

**Files:**

- Modify: `packages/layout-builder/src/Providers/LayoutBuilderServiceProvider.php`
- Modify: `packages/layout-builder/src/Enums/LayoutTypeEnum.php`
- Modify: `packages/layout-builder/src/Enums/ResourceEnum.php`
- Modify: `packages/layout-builder/src/Enums/TypeEnum.php`
- Modify: `packages/layout-builder/src/Enums/AssetEnum.php`
- Modify: `packages/layout-builder/src/Enums/ConfiguratorTypeEnum.php`
- Modify: `packages/layout-builder/src/Enums/LivewireComponentsEnum.php`
- Modify: `packages/layout-builder/src/Support/LayoutModelRegistrar.php`
- Modify: `packages/layout-builder/src/Support/Loader/LayoutLoader.php`
- Modify: LayoutBuilder creators/demo commands that currently create sections.

- [ ] **Step 1: Strip provider section registration**

Remove section resource, section asset, section model, section relationships, section events, and section workspace registration from LayoutBuilder.

- [ ] **Step 2: Keep only widget/layout type ownership**

Update LayoutBuilder enums so LayoutBuilder owns Widget/Layout concepts only. Section/content-block type data comes from Block Library.

- [ ] **Step 3: Make demos conditional**

Move content block demo creation into Block Library or guard LayoutBuilder demo paths so missing Block Library does not fail package boot.

- [ ] **Step 4: Run LayoutBuilder package tests**

Run: `vendor/bin/pest packages/layout-builder/tests`

Expected: LayoutBuilder tests pass after section tests are moved or rewritten.

### Task 7: Move and Rewrite Tests

**Files:**

- Modify: `tests/AbstractTestCase.php`

- [ ] **Step 1: Move section tests**

Move tests under `packages/layout-builder/tests/Feature/Filament/Resources/Section`, section model tests, and section asset Livewire tests into Block Library, updating namespaces/imports.

- [ ] **Step 2: Add arch boundary tests**

- [ ] **Step 3: Add optional boot test**

Test LayoutBuilder provider registration without Block Library bridge registration and assert no section class is required.

- [ ] **Step 4: Add bridge integration test**

- [ ] **Step 5: Run package test slices**

Expected: Block Library and LayoutBuilder tests pass together.

### Task 8: Final Verification

**Files:**

- All changed package, test, and composer files.

- [ ] **Step 1: Dump autoload**

Run: `COMPOSER=composer.local.json composer dump-autoload --no-scripts`

Expected: autoload generation succeeds.

- [ ] **Step 2: Run focused test suite**

Expected: focused tests pass.

- [ ] **Step 3: Run static checks for boundary leaks**

Expected: only explicitly approved optional bridge file references, or no matches.

Expected: no matches.

- [ ] **Step 4: Run formatting**

Run: `composer lint`

Expected: Pint passes or formats only touched files.
