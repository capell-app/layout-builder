# Package Isolation Arch Tests Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add thorough Pest arch tests for every package in this monorepo, ensuring self-containment boundaries are enforced and cross-package dependency violations are caught automatically.

**Architecture:** Two complementary test patterns are used: `toOnlyBeUsedIn` for leaf packages (nothing else in the monorepo should import from them), and `not->toUse` for shared packages (they must not reverse-import their dependents). Both patterns use Pest's `arch()` macro, which scans the source paths declared in `phpunit.xml` (`packages/*/src` and `packages/themes/*/src`).

**Tech Stack:** PHP 8.2, Pest arch() macro, phpunit.xml source path config

---

## Background: What Exists vs. What's Needed

### Test pattern reference

```php
// Leaf package — nothing outside this namespace may import from it:
arch()->expect('Capell\Forms')->toOnlyBeUsedIn('Capell\Forms');

// Shared package — it must not import from packages that depend on it:
arch()->expect('Capell\Navigation')->not->toUse('Capell\Blog');

// Quality check for all:
arch()->expect('Capell\X')->classes()->toUseStrictEquality();
```

### Current state

| Package      | Namespace             | Self-contained test? | Notes                                      |
| ------------ | --------------------- | -------------------- | ------------------------------------------ |
| address      | `Capell\Address`      | ✅ `toOnlyBeUsedIn`  | complete                                   |
| assistant    | `Capell\Assistant`    | ✅ `toOnlyBeUsedIn`  | complete                                   |
| blog         | `Capell\Blog`         | ✅ `toOnlyBeUsedIn`  | complete                                   |
| mosaic       | `Capell\Mosaic`       | ❌ broken            | uses `Capell\Layout` (old namespace)       |
| tags         | `Capell\Tags`         | ❌ incomplete        | only smoke tests (class_exists), no arch() |
| workspaces   | `Capell\Workspaces`   | ⚠️ partial           | only core→workspaces direction             |
| forms        | `Capell\Forms`        | ❌ missing           | no arch test at all                        |
| media        | `Capell\Media`        | ❌ missing           | no arch test at all                        |
| navigation   | `Capell\Navigation`   | ❌ missing           | no arch test at all                        |
| plugins      | `Capell\Plugins`      | ❌ missing           | no arch test at all                        |
| seo-tools    | `Capell\SeoTools`     | ❌ missing           | no arch test at all                        |
| themes-admin | `Capell\Themes\Admin` | ❌ missing           | no arch test at all                        |
| themes-core  | `Capell\Themes\Core`  | ❌ missing           | no arch test at all                        |

### Known violations (discovered via research, verified before writing this plan)

| File                                                              | Violation                                                                                           | Resolution                                                                                    |
| ----------------------------------------------------------------- | --------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------- |
| `packages/blog/src/Support/Sitemap/*.php`                         | imports `Capell\SeoTools` but blog's `composer.json` does not declare `capell-app/seo-tools`        | Add dep to composer.json                                                                      |
| `packages/themes-core/src/Console/GenerateSitemapCommand.php`     | imports `Capell\SeoTools` but themes-core's `composer.json` does not declare `capell-app/seo-tools` | Add dep to composer.json                                                                      |
| `packages/blog/src/Models/Article.php`                            | imports `Capell\Workspaces\BelongsToWorkspace` but blog does not declare `capell-app/workspaces`    | Add dep to blog's composer.json                                                               |
| `packages/tags/src/Providers/TagsServiceProvider.php`             | imports `Capell\Workspaces\WorkspaceRegistry` but tags does not declare `capell-app/workspaces`     | Add dep to tags' composer.json                                                                |
| `packages/mosaic/src/Console/Commands/Hero/DemoCommand.php`       | imports `Capell\Blog` but mosaic does not depend on blog (blog depends on mosaic)                   | `ignoring(DemoCommand::class)` — demo-only tool                                               |
| `packages/workspaces/src/Providers/WorkspacesServiceProvider.php` | imports `Capell\Blog\Models\Article` and `Capell\Mosaic\Models\*`                                   | Intentional by design (see comment in file). Use `ignoring(WorkspacesServiceProvider::class)` |

---

## File Structure

**New test files:**

- `tests/src/Forms/Arch/FormsIsolationTest.php`
- `tests/src/Media/Arch/MediaIsolationTest.php`
- `tests/src/Navigation/Arch/NavigationBoundaryTest.php`
- `tests/src/Plugins/Arch/PluginsIsolationTest.php`
- `tests/src/SeoTools/Arch/SeoToolsBoundaryTest.php`
- `tests/src/ThemesAdmin/Arch/ThemesAdminIsolationTest.php`
- `tests/src/ThemesCore/Arch/ThemesCoreIsolationTest.php`

**Modified test files:**

- `tests/src/Mosaic/Arch/LayoutPackageTest.php` — fix wrong namespace, add no-reverse-import
- `tests/src/Tags/Arch/TagsBoundaryTest.php` — add arch() macro tests alongside existing smoke tests
- `tests/src/Workspaces/Arch/WorkspacesIsolationTest.php` — add toOnlyBeUsedIn + not->toUse for blog/mosaic

**Modified production files (fix violations):**

- `packages/blog/composer.json` — add `capell-app/seo-tools` and `capell-app/workspaces`
- `packages/tags/composer.json` — add `capell-app/workspaces`
- `packages/themes-core/composer.json` — add `capell-app/seo-tools`

---

## Task 1: Fix the broken Mosaic arch test

The existing test in `tests/src/Mosaic/Arch/LayoutPackageTest.php` references `Capell\Layout` — a namespace that does not exist in the codebase. The Mosaic package uses `Capell\Mosaic`. The test also needs a no-reverse-import assertion because blog imports from mosaic (not the reverse).

**Files:**

- Modify: `tests/src/Mosaic/Arch/LayoutPackageTest.php`

- [ ] **Step 1: Run the existing (broken) test to confirm it produces no useful signal**

```bash
vendor/bin/pest tests/src/Mosaic/Arch/LayoutPackageTest.php --no-coverage
```

Expected: test passes trivially because `Capell\Layout` has zero classes — the test asserts nothing real.

- [ ] **Step 2: Rewrite the test with the correct namespace and meaningful assertions**

Replace the entire contents of `tests/src/Mosaic/Arch/LayoutPackageTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\Mosaic\Console\Commands\Hero\DemoCommand;

arch('mosaic does not import blog (blog depends on mosaic, not the reverse)')
    ->expect('Capell\Mosaic')
    ->not->toUse('Capell\Blog')
    ->ignoring([
        // DemoCommand seeds demo blog content — acceptable for a dev-only command
        DemoCommand::class,
    ]);

arch()
    ->expect('Capell\Mosaic')
    ->classes()
    ->toUseStrictEquality();
```

- [ ] **Step 3: Run the test to verify it passes**

```bash
vendor/bin/pest tests/src/Mosaic/Arch/LayoutPackageTest.php --no-coverage
```

Expected: both `arch()` tests pass.

- [ ] **Step 4: Commit**

```bash
git add tests/src/Mosaic/Arch/LayoutPackageTest.php
git commit -m "fix(arch): correct Mosaic arch test namespace from Capell\\Layout to Capell\\Mosaic"
```

---

## Task 2: Add isolation tests for clean leaf packages (Forms, Media, Plugins)

These three packages have no cross-imports to or from other packages — the tests should pass immediately and serve as regression guards.

**Files:**

- Create: `tests/src/Forms/Arch/FormsIsolationTest.php`
- Create: `tests/src/Media/Arch/MediaIsolationTest.php`
- Create: `tests/src/Plugins/Arch/PluginsIsolationTest.php`

- [ ] **Step 1: Create Forms isolation test**

```php
<?php

declare(strict_types=1);

arch()
    ->expect('Capell\Forms')
    ->toOnlyBeUsedIn('Capell\Forms');

arch()
    ->expect('Capell\Forms')
    ->classes()
    ->toUseStrictEquality();
```

- [ ] **Step 2: Create Media isolation test**

```php
<?php

declare(strict_types=1);

arch()
    ->expect('Capell\Media')
    ->toOnlyBeUsedIn('Capell\Media');

arch()
    ->expect('Capell\Media')
    ->classes()
    ->toUseStrictEquality();
```

- [ ] **Step 3: Create Plugins isolation test**

```php
<?php

declare(strict_types=1);

arch()
    ->expect('Capell\Plugins')
    ->toOnlyBeUsedIn('Capell\Plugins');

arch()
    ->expect('Capell\Plugins')
    ->classes()
    ->toUseStrictEquality();
```

- [ ] **Step 4: Run all three tests to verify they pass**

```bash
vendor/bin/pest tests/src/Forms/Arch tests/src/Media/Arch tests/src/Plugins/Arch --no-coverage
```

Expected: all pass.

- [ ] **Step 5: Commit**

```bash
git add tests/src/Forms/Arch/FormsIsolationTest.php \
        tests/src/Media/Arch/MediaIsolationTest.php \
        tests/src/Plugins/Arch/PluginsIsolationTest.php
git commit -m "test(arch): add isolation tests for Forms, Media, and Plugins packages"
```

---

## Task 3: Add boundary tests for shared packages (Navigation, SeoTools)

Navigation and SeoTools are shared utilities — other packages legitimately import from them. The right test is the reverse: they must not import from packages that depend on them. These tests should pass immediately.

**Files:**

- Create: `tests/src/Navigation/Arch/NavigationBoundaryTest.php`
- Create: `tests/src/SeoTools/Arch/SeoToolsBoundaryTest.php`

- [ ] **Step 1: Create Navigation boundary test**

Navigation declares only `capell-app/admin` and `capell-app/frontend` as Capell dependencies. It must not import from any package that depends on it.

```php
<?php

declare(strict_types=1);

arch('navigation does not import packages that depend on it')
    ->expect('Capell\Navigation')
    ->not->toUse([
        'Capell\Address',
        'Capell\Blog',
        'Capell\Forms',
        'Capell\Media',
        'Capell\Mosaic',
        'Capell\Plugins',
        'Capell\SeoTools',
        'Capell\Tags',
        'Capell\Themes',
        'Capell\Workspaces',
    ]);

arch()
    ->expect('Capell\Navigation')
    ->classes()
    ->toUseStrictEquality();
```

- [ ] **Step 2: Create SeoTools boundary test**

SeoTools declares only `capell-app/admin` and `capell-app/frontend`. It must not import from blog, themes, or other consumers.

```php
<?php

declare(strict_types=1);

arch('seo-tools does not import packages that depend on it')
    ->expect('Capell\SeoTools')
    ->not->toUse([
        'Capell\Address',
        'Capell\Blog',
        'Capell\Forms',
        'Capell\Media',
        'Capell\Mosaic',
        'Capell\Navigation',
        'Capell\Plugins',
        'Capell\Tags',
        'Capell\Themes',
        'Capell\Workspaces',
    ]);

arch()
    ->expect('Capell\SeoTools')
    ->classes()
    ->toUseStrictEquality();
```

- [ ] **Step 3: Run both tests**

```bash
vendor/bin/pest tests/src/Navigation/Arch tests/src/SeoTools/Arch --no-coverage
```

Expected: all pass.

- [ ] **Step 4: Commit**

```bash
git add tests/src/Navigation/Arch/NavigationBoundaryTest.php \
        tests/src/SeoTools/Arch/SeoToolsBoundaryTest.php
git commit -m "test(arch): add boundary tests for Navigation and SeoTools packages"
```

---

## Task 4: Fix missing SeoTools dep in blog and themes-core

Blog and themes-core both import from `Capell\SeoTools` but neither declares `capell-app/seo-tools` in their `composer.json`. This means the dependency exists at runtime (via the monorepo) but isn't declared — a maintenance hazard.

**Files:**

- Modify: `packages/blog/composer.json`
- Modify: `packages/themes-core/composer.json`

- [ ] **Step 1: Add seo-tools to blog's composer.json**

In `packages/blog/composer.json`, add `"capell-app/seo-tools": "*"` to the `require` object:

```json
{
    "name": "capell-app/blog",
    "require": {
        "php": "^8.2",
        "capell-app/admin": "*",
        "capell-app/frontend": "*",
        "capell-app/navigation": "*",
        "capell-app/seo-tools": "*",
        "capell-app/tags": "*"
    }
}
```

- [ ] **Step 2: Add seo-tools to themes-core's composer.json**

In `packages/themes-core/composer.json`, add `"capell-app/seo-tools": "*"` to the `require` object:

```json
{
    "name": "capell-app/themes-core",
    "require": {
        "php": "^8.2",
        "capell-app/seo-tools": "*",
        "spatie/laravel-data": "^4.0"
    }
}
```

- [ ] **Step 3: Run the full arch suite to confirm no regressions**

```bash
vendor/bin/pest tests/src/Blog/Arch tests/src/SeoTools/Arch --no-coverage
```

Expected: all pass.

- [ ] **Step 4: Commit**

```bash
git add packages/blog/composer.json packages/themes-core/composer.json
git commit -m "fix(deps): declare capell-app/seo-tools as explicit dependency in blog and themes-core"
```

---

## Task 5: Add isolation tests for Themes packages

ThemesAdmin is a leaf package (nothing else imports from `Capell\Themes\Admin`). ThemesCore is shared — themes-admin and the theme sub-packages all import from it, so it gets a boundary test instead of `toOnlyBeUsedIn`.

**Files:**

- Create: `tests/src/ThemesAdmin/Arch/ThemesAdminIsolationTest.php`
- Create: `tests/src/ThemesCore/Arch/ThemesCoreIsolationTest.php`

- [ ] **Step 1: Create ThemesAdmin isolation test**

```php
<?php

declare(strict_types=1);

arch()
    ->expect('Capell\Themes\Admin')
    ->toOnlyBeUsedIn('Capell\Themes\Admin');

arch()
    ->expect('Capell\Themes\Admin')
    ->classes()
    ->toUseStrictEquality();
```

- [ ] **Step 2: Create ThemesCore boundary test**

ThemesCore is legitimately imported by themes-admin and theme sub-packages (`Capell\Themes\Corporate`, `Capell\Themes\Agency`, `Capell\Themes\Saas`). The test enforces that themes-core itself doesn't import from those consumers.

```php
<?php

declare(strict_types=1);

arch('themes-core does not import packages that depend on it')
    ->expect('Capell\Themes\Core')
    ->not->toUse([
        'Capell\Address',
        'Capell\Blog',
        'Capell\Forms',
        'Capell\Media',
        'Capell\Mosaic',
        'Capell\Navigation',
        'Capell\Plugins',
        'Capell\Tags',
        'Capell\Themes\Admin',
        'Capell\Themes\Agency',
        'Capell\Themes\Corporate',
        'Capell\Themes\Saas',
        'Capell\Workspaces',
    ]);

arch()
    ->expect('Capell\Themes\Core')
    ->classes()
    ->toUseStrictEquality();
```

- [ ] **Step 3: Run both tests**

```bash
vendor/bin/pest tests/src/ThemesAdmin/Arch tests/src/ThemesCore/Arch --no-coverage
```

Expected: all pass. If `ThemesAdminIsolationTest` fails, examine the violation — it may indicate an admin command or another package importing themes-admin classes. Fix the violation before proceeding; only add `ignoring()` for exceptions that are truly justified.

- [ ] **Step 4: Commit**

```bash
git add tests/src/ThemesAdmin/Arch/ThemesAdminIsolationTest.php \
        tests/src/ThemesCore/Arch/ThemesCoreIsolationTest.php
git commit -m "test(arch): add isolation and boundary tests for ThemesAdmin and ThemesCore packages"
```

---

## Task 6: Fix missing Workspaces dep in blog and tags, then add Workspaces self-containment test

`packages/blog/src/Models/Article.php` uses `Capell\Workspaces\BelongsToWorkspace` and `packages/tags/src/Providers/TagsServiceProvider.php` uses `Capell\Workspaces\WorkspaceRegistry`. Neither package declares `capell-app/workspaces`. First fix the deps, then add the arch test that would catch regressions.

**Files:**

- Modify: `packages/blog/composer.json`
- Modify: `packages/tags/composer.json`
- Modify: `tests/src/Workspaces/Arch/WorkspacesIsolationTest.php`

- [ ] **Step 1: Add workspaces to blog's composer.json**

The blog package already imports `Capell\Workspaces\BelongsToWorkspace` on its Article model. Declare it:

```json
{
    "name": "capell-app/blog",
    "require": {
        "php": "^8.2",
        "capell-app/admin": "*",
        "capell-app/frontend": "*",
        "capell-app/navigation": "*",
        "capell-app/seo-tools": "*",
        "capell-app/tags": "*",
        "capell-app/workspaces": "*"
    }
}
```

- [ ] **Step 2: Add workspaces to tags' composer.json**

The tags ServiceProvider calls `WorkspaceRegistry::register()` when workspaces is installed. Declare it:

```json
{
    "name": "capell-app/tags",
    "require": {
        "php": "^8.2",
        "capell-app/admin": "*",
        "capell-app/navigation": "*",
        "capell-app/workspaces": "*",
        "filament/spatie-laravel-tags-plugin": "^4.0|^5.0"
    }
}
```

- [ ] **Step 3: Write the self-containment test for Workspaces**

The existing test in `tests/src/Workspaces/Arch/WorkspacesIsolationTest.php` only checks the core→workspaces direction. Add two more assertions:

1. `toOnlyBeUsedIn` — nothing outside the approved list may import from workspaces.
2. `not->toUse('Capell\Blog')` and `not->toUse('Capell\Mosaic')` — workspaces must not import from dependents (the ServiceProvider does this intentionally via `class_exists` guards; that file gets an `ignoring()` exception).

Replace the full contents of `tests/src/Workspaces/Arch/WorkspacesIsolationTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\Blog\Models\Article;
use Capell\Core\Console\Commands\DoctorCommand;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Models\Page;
use Capell\Core\Observers\PageUrlObserver;
use Capell\Core\Upgrade\EnsureMorphMapUpgradeStep;
use Capell\Workspaces\Providers\WorkspacesServiceProvider;

arch('core does not reference Capell\\Workspaces namespace')
    ->expect('Capell\Core')
    ->not->toUse('Capell\Workspaces')
    ->ignoring([
        // Exchanger (core sub-module) works with workspace data directly
        'Capell\Core\Exchanger',
        // Page model uses the BelongsToWorkspace trait
        Page::class,
        // ModelEnum lists workspace model classes for morph-map registration
        ModelEnum::class,
        // PageUrlObserver needs WorkspaceContextScope for draft-aware URL queries
        PageUrlObserver::class,
        // Upgrade step and doctor command inspect workspace registry at runtime
        EnsureMorphMapUpgradeStep::class,
        DoctorCommand::class,
    ]);

arch('workspaces does not import Capell\\Blog')
    ->expect('Capell\Workspaces')
    ->not->toUse('Capell\Blog')
    ->ignoring([
        // WorkspacesServiceProvider conditionally registers Blog models via class_exists()
        // so that blog has no hard dependency on workspaces. This is intentional by design.
        WorkspacesServiceProvider::class,
    ]);

arch('workspaces does not import Capell\\Mosaic')
    ->expect('Capell\Workspaces')
    ->not->toUse('Capell\Mosaic')
    ->ignoring([
        // Same reasoning as above — ServiceProvider registers Mosaic models optionally.
        WorkspacesServiceProvider::class,
    ]);

arch('workspaces is only used by declared consumers')
    ->expect('Capell\Workspaces')
    ->toOnlyBeUsedIn([
        'Capell\Workspaces',
        // Core has a handful of workspace-aware classes (Page, PageUrlObserver, etc.)
        'Capell\Core',
        // Blog Article model uses the BelongsToWorkspace trait
        'Capell\Blog',
        // Tags ServiceProvider registers tags models in WorkspaceRegistry
        'Capell\Tags',
    ]);

arch()
    ->expect('Capell\Workspaces')
    ->classes()
    ->toUseStrictEquality();
```

- [ ] **Step 4: Run the Workspaces arch tests**

```bash
vendor/bin/pest tests/src/Workspaces/Arch/WorkspacesIsolationTest.php --no-coverage
```

Expected: all five assertions pass. If `toOnlyBeUsedIn` lists additional namespaces you did not include, examine each one: if the dependency is legitimate, declare it in that package's `composer.json` and add it to the `toOnlyBeUsedIn` array; if it is a violation, fix the import.

- [ ] **Step 5: Commit**

```bash
git add packages/blog/composer.json \
        packages/tags/composer.json \
        tests/src/Workspaces/Arch/WorkspacesIsolationTest.php
git commit -m "fix(deps): declare capell-app/workspaces in blog and tags; add workspaces self-containment arch test"
```

---

## Task 7: Upgrade Tags arch test from smoke tests to arch() macro

`tests/src/Tags/Arch/TagsBoundaryTest.php` currently contains only `class_exists`/`trait_exists` assertions written during a namespace migration. These are still valid as regression guards, but they do not enforce architectural boundaries. Add proper `arch()` assertions.

**Files:**

- Modify: `tests/src/Tags/Arch/TagsBoundaryTest.php`

- [ ] **Step 1: Write the failing boundary test**

Append the following arch assertions to the end of `tests/src/Tags/Arch/TagsBoundaryTest.php` (keep the existing smoke tests unchanged above):

```php
arch('tags does not import Capell\\Blog (blog depends on tags, not the reverse)')
    ->expect('Capell\Tags')
    ->not->toUse('Capell\Blog');

arch()
    ->expect('Capell\Tags')
    ->classes()
    ->toUseStrictEquality();
```

- [ ] **Step 2: Run the test to verify it passes**

```bash
vendor/bin/pest tests/src/Tags/Arch/TagsBoundaryTest.php --no-coverage
```

Expected: all assertions pass including the two new ones.

- [ ] **Step 3: Commit**

```bash
git add tests/src/Tags/Arch/TagsBoundaryTest.php
git commit -m "test(arch): add arch() boundary assertions to Tags test alongside existing smoke tests"
```

---

## Task 8: Run the full Architecture test suite

Verify all arch tests pass together. This catches any interactions between tests (e.g., shared `ignoring()` exceptions that might mask a violation).

**Files:** none — run only

- [ ] **Step 1: Run the full Architecture suite**

```bash
vendor/bin/pest --testsuite=Architecture --no-coverage
```

Expected: all tests pass with zero failures.

- [ ] **Step 2: If any test fails, investigate and fix**

For each failure:

- If Pest reports "X uses Y" unexpectedly: check whether the import is legitimate. If yes, add it to `ignoring()`; if not, remove the import and update the source code.
- If Pest reports "X is used by Y" unexpectedly: check whether Y should declare X as a dep. If yes, update `composer.json` and add Y to the `toOnlyBeUsedIn` array. If the import is wrong, remove it from Y.

- [ ] **Step 3: Run the full test suite to confirm no regressions**

```bash
composer test
```

Expected: 100% pass.

- [ ] **Step 4: Final commit if any fixes were needed**

```bash
git add -p
git commit -m "fix(arch): resolve remaining boundary violations found during full Architecture suite run"
```

---

## Self-Review

### Spec coverage check

| Requirement                       | Task   |
| --------------------------------- | ------ |
| Fix broken Mosaic test            | Task 1 |
| Forms self-contained              | Task 2 |
| Media self-contained              | Task 2 |
| Plugins self-contained            | Task 2 |
| Navigation no reverse-imports     | Task 3 |
| SeoTools no reverse-imports       | Task 3 |
| Blog declares seo-tools dep       | Task 4 |
| ThemesCore declares seo-tools dep | Task 4 |
| ThemesAdmin self-contained        | Task 5 |
| ThemesCore no reverse-imports     | Task 5 |
| Blog declares workspaces dep      | Task 6 |
| Tags declares workspaces dep      | Task 6 |
| Workspaces self-contained         | Task 6 |
| Tags gets arch() boundary test    | Task 7 |
| Full suite validation             | Task 8 |

### Placeholder scan

No TBD, TODO, or vague steps present.

### Type/name consistency

- `WorkspacesServiceProvider::class` used in Task 6 matches the actual class at `packages/workspaces/src/Providers/WorkspacesServiceProvider.php`.
- `DemoCommand::class` in Task 1 refers to `Capell\Mosaic\Console\Commands\Hero\DemoCommand`, imported at the top of the test file.
- All namespace strings match confirmed source paths from exploration.
