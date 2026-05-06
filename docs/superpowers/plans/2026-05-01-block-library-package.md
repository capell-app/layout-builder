# Block Library Package Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extract LayoutBuilder sections into optional `capell-app/block-library` while preserving the existing section workflow when Block Library and LayoutBuilder are both installed.

**Architecture:** Block Library owns the former Section domain and registers reusable content as a first-class asset. LayoutBuilder owns layouts/widgets and exposes an adapter registry so optional packages can contribute layout assets without LayoutBuilder importing them directly.

**Tech Stack:** PHP 8.2, Laravel package tools, Filament 4/5, Livewire, Pest, Capell core/admin/frontend extension points.

---

## File Structure

- Create `packages/block-library/composer.json`, `capell.json`, language files, migrations, factories, service provider, model, observer, enums, actions, Filament resources/configurators, Livewire selector, and tests.
- Create LayoutBuilder bridge contracts in `packages/layout-builder/src/Contracts` and support registry in `packages/layout-builder/src/Support`.
- Modify LayoutBuilder provider/enums/models/form-builder/loaders to remove direct Section ownership and use the bridge registry for optional content block assets.
- Modify root `composer.json`, `composer.local.json`, package manifest arch tests, and shared test bootstrap autoload/morph map entries.

### Task 1: Register the New Package Shell

**Files:**

- Create: `packages/block-library/composer.json`
- Create: `packages/block-library/capell.json`
- Create: `packages/block-library/src/Providers/BlockLibraryServiceProvider.php`
- Modify: `composer.json`
- Modify: `composer.local.json`
- Modify: `tests/Packages/Arch/ProductGroupManifestTest.php`

- [ ] **Step 1: Write the manifest/autoload expectation**

Add `foundation/block-library/capell.json` to the expected foundation bundle list in `tests/Packages/Arch/ProductGroupManifestTest.php`.

- [ ] **Step 2: Create package manifests**

Create Composer package `capell-app/block-library` with PSR-4 namespaces:

```json
"Capell\\BlockLibrary\\": "src/",
"Capell\\BlockLibrary\\Database\\Factories\\": "database/factories"
```

Create `capell.json` requiring `capell-app/core`, `capell-app/admin`, and `capell-app/frontend`, with provider `Capell\\BlockLibrary\\Providers\\BlockLibraryServiceProvider`.

- [ ] **Step 3: Register monorepo autoload**

Add `Capell\\BlockLibrary\\`, factory, and test namespaces to both root composer files.

- [ ] **Step 4: Verify shell registration**

Run: `vendor/bin/pest tests/Packages/Arch/ProductGroupManifestTest.php tests/Packages/Arch/ManifestProviderClassExistsTest.php`

Expected: package manifest tests pass.

### Task 2: Move Section Domain Into Block Library

**Files:**

- Move/create: `packages/block-library/src/Models/ContentBlock.php`
- Move/create: `packages/block-library/src/Observers/ContentBlockObserver.php`
- Move/create: `packages/block-library/database/factories/ContentBlockFactory.php`
- Move/create: `packages/block-library/database/migrations/create_block_library_table.php`
- Move/create: `packages/block-library/resources/lang/en/*.php`
- Modify: `packages/layout-builder/src/Models/Widget.php`
- Modify: `packages/layout-builder/src/Models/WidgetAsset.php`

- [ ] **Step 1: Add ContentBlock model by renaming Section**

Move `Capell\LayoutBuilder\Models\Section` to `Capell\BlockLibrary\Models\ContentBlock`, setting `$table = 'block_library'`, the existing fillable/casts/relations, and morph relation behavior. Update docblocks to use `ContentBlock`.

- [ ] **Step 2: Move observer and factory**

Move `SectionObserver` to `ContentBlockObserver` and `SectionFactory` to `ContentBlockFactory`. Preserve current behavior that assigns the default section/content-block type and nested set housekeeping.

- [ ] **Step 3: Move migration and language strings**

Move `create_block_library_table.php` into Block Library for first-pass data continuity. Copy section-related language keys from LayoutBuilder to `capell-block-library`.

- [ ] **Step 4: Remove direct Section relationship from Widget**

Delete the hard-coded `sections()` relation from `Capell\LayoutBuilder\Models\Widget`. Replace downstream callers with the bridge registry in later tasks.

- [ ] **Step 5: Run focused model tests**

Run: `vendor/bin/pest packages/block-library/tests/Integration/Models`

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

Update LayoutBuilder asset selector/rendering paths to read bridge assets from the registry in addition to core page assets. Do not import `Capell\BlockLibrary`.

- [ ] **Step 5: Verify LayoutBuilder boots without Block Library**

Run: `vendor/bin/pest packages/layout-builder/tests/Arch/LayoutPackageTest.php`

Expected: no Block Library classes are required for LayoutBuilder package loading.

### Task 4: Register Block Library With Core/Admin/Frontend and LayoutBuilder Bridge

**Files:**

- Modify: `packages/block-library/src/Providers/BlockLibraryServiceProvider.php`
- Create: `packages/block-library/src/Enums/ContentBlockAssetEnum.php`
- Create: `packages/block-library/src/Enums/ContentBlockTypeEnum.php`
- Create: `packages/block-library/src/Support/LayoutBuilder/ContentBlockLayoutAssetBridge.php`

- [ ] **Step 1: Register package metadata**

Use `CapellCore::registerPackage()` with package name `capell-app/block-library`, install/setup command metadata when commands exist, and translations from `capell-block-library`.

- [ ] **Step 2: Register content block asset and page type**

Register a page/content type for the `content_block` type value and a core/admin/frontend asset with key `content_block` so existing widget asset rows still resolve.

- [ ] **Step 3: Register LayoutBuilder bridge conditionally**

In Block Library provider, check `class_exists(\Capell\LayoutBuilder\Support\LayoutAssetBridgeRegistry::class)`. If present, resolve the registry and register the `content_block` bridge data. This is the only integration point and must remain conditional.

- [ ] **Step 4: Register relationships**

Register `Page::contentBlocks()`, `Page::widgetAssets()`, `Site::contentBlocks()`, and `Type::contentBlocks()` from Block Library. Preserve old relation names only where tests or existing user code prove they are needed.

- [ ] **Step 5: Register publishing-studio conditionally**

If `Capell\PublishingStudio\WorkspaceRegistry` exists, register `ContentBlock::class`.

### Task 5: Move Filament and Livewire Section UI

**Files:**

- Move/create: `packages/block-library/src/Filament/Resources/BlockLibrary/*`
- Move/create: `packages/block-library/src/Filament/Configurators/BlockLibrary/*`
- Move/create: `packages/block-library/src/Livewire/Assets/Table/ContentBlockAssets.php`
- Move/create: `packages/block-library/resources/views/components/content-block/*.blade.php`
- Modify: moved namespaces and translations.

- [ ] **Step 1: Move Filament resource tree**

Move `Filament/Resources/Sections` to `Filament/Resources/BlockLibrary`, rename classes from `Section*` to `ContentBlock*`, and keep route slugs/labels compatible where practical.

- [ ] **Step 2: Move configurators**

Move `DefaultSectionConfigurator`, `HeroSectionConfigurator`, and `TestimonialSectionConfigurator` to Block Library. Rename enum to `ContentBlockConfiguratorEnum`.

- [ ] **Step 3: Move Livewire asset table**

Move `SectionAssets` to `ContentBlockAssets`, keeping the Livewire alias compatible through bridge registration.

- [ ] **Step 4: Move Blade asset views**

Move `resources/views/components/section/*` to Block Library and update component namespaces.

- [ ] **Step 5: Run focused UI tests**

Run: `vendor/bin/pest packages/block-library/tests/Feature/Filament packages/block-library/tests/Feature/Livewire`

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

- Move: LayoutBuilder section tests to `packages/block-library/tests`
- Create: `packages/block-library/tests/Arch/BlockLibraryPackageTest.php`
- Create: `packages/layout-builder/tests/Feature/BlockLibraryOptionalBootTest.php`
- Create: `packages/layout-builder/tests/Feature/BlockLibraryBridgeTest.php`
- Modify: `tests/AbstractTestCase.php`

- [ ] **Step 1: Move section tests**

Move tests under `packages/layout-builder/tests/Feature/Filament/Resources/Section`, section model tests, and section asset Livewire tests into Block Library, updating namespaces/imports.

- [ ] **Step 2: Add arch boundary tests**

Assert Block Library does not use `Capell\LayoutBuilder\` except optional bridge files if needed, and LayoutBuilder does not use `Capell\BlockLibrary\` in core classes.

- [ ] **Step 3: Add optional boot test**

Test LayoutBuilder provider registration without Block Library bridge registration and assert no section class is required.

- [ ] **Step 4: Add bridge integration test**

With both packages registered, create a `ContentBlock`, attach it to a LayoutBuilder widget asset through the bridge key `content_block`, and assert the existing render/selection path sees it.

- [ ] **Step 5: Run package test slices**

Run: `vendor/bin/pest packages/block-library/tests packages/layout-builder/tests`

Expected: Block Library and LayoutBuilder tests pass together.

### Task 8: Final Verification

**Files:**

- All changed package, test, and composer files.

- [ ] **Step 1: Dump autoload**

Run: `COMPOSER=composer.local.json composer dump-autoload --no-scripts`

Expected: autoload generation succeeds.

- [ ] **Step 2: Run focused test suite**

Run: `vendor/bin/pest packages/block-library/tests packages/layout-builder/tests tests/Packages/Arch`

Expected: focused tests pass.

- [ ] **Step 3: Run static checks for boundary leaks**

Run: `rg -n 'Capell\\\\LayoutBuilder' packages/block-library/src packages/block-library/tests`

Expected: only explicitly approved optional bridge file references, or no matches.

Run: `rg -n 'Capell\\\\BlockLibrary' packages/layout-builder/src`

Expected: no matches.

- [ ] **Step 4: Run formatting**

Run: `composer lint`

Expected: Pint passes or formats only touched files.
