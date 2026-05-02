# Theme Studio Runtime Split Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make `capell-app/theme-studio-core` own Theme Studio runtime settings so frontend rendering works without `capell-app/theme-studio-admin`, while keeping the admin package as an optional editor and publishing layer.

**Architecture:** Move `ThemeStudioSettings` and its published settings migration into `packages/theme-studio-core`, bind `ThemeRuntimeSettings` from the core service provider, and leave schema registration plus publishing UI in `packages/theme-studio-admin`. Then update admin consumers to import the moved settings class and add regression tests for both core-only and admin-enabled installs.

**Tech Stack:** PHP 8.2, Laravel package service providers, Spatie Laravel Settings, Pest, Filament, Livewire, Capell Actions + Data conventions

---

## File Map

- Create: `packages/theme-studio-core/src/Settings/ThemeStudioSettings.php`
- Create: `packages/theme-studio-core/database/settings/create_theme_studio_settings.php`
- Modify: `packages/theme-studio-core/src/ThemeStudioCoreServiceProvider.php`
- Modify: `packages/theme-studio-core/tests/ThemeStudioCoreTestCase.php`
- Modify: `packages/theme-studio-core/tests/Feature/FrontendRuntimeRenderingTest.php`
- Modify: `packages/theme-studio-admin/src/ThemeStudioAdminServiceProvider.php`
- Modify: `packages/theme-studio-admin/src/Actions/ActivateApprovedThemeDraftAction.php`
- Modify: `packages/theme-studio-admin/src/Actions/PublishThemeDraftAction.php`
- Modify: `packages/theme-studio-admin/src/Actions/ResolveThemePublishingReadinessAction.php`
- Modify: `packages/theme-studio-admin/src/Actions/StageThemeDraftAction.php`
- Modify: `packages/theme-studio-admin/src/Contracts/ThemeDraftPublisher.php`
- Modify: `packages/theme-studio-admin/src/Filament/Pages/ThemeStudioPage.php`
- Modify: `packages/theme-studio-admin/src/Publishing/StandaloneThemeDraftPublisher.php`
- Modify: `packages/theme-studio-admin/src/Publishing/WorkspaceThemeDraftPublisher.php`
- Modify: `packages/theme-studio-admin/src/Schemas/ThemeStudioSettingsSchema.php`
- Modify: `packages/theme-studio-admin/tests/ThemeStudioAdminTestCase.php`
- Modify: `packages/theme-studio-admin/tests/Feature/ThemeStudioAdminTest.php`
- Delete: `packages/theme-studio-admin/src/Settings/ThemeStudioSettings.php`
- Delete: `packages/theme-studio-admin/database/settings/create_theme_studio_settings.php`

### Task 1: Move runtime settings ownership into core

**Files:**

- Create: `packages/theme-studio-core/src/Settings/ThemeStudioSettings.php`
- Create: `packages/theme-studio-core/database/settings/create_theme_studio_settings.php`
- Modify: `packages/theme-studio-core/src/ThemeStudioCoreServiceProvider.php`
- Test: `packages/theme-studio-core/tests/Feature/FrontendRuntimeRenderingTest.php`

- [ ] **Step 1: Write the failing core runtime test that expects the core package to provide runtime settings**

Add this test near the existing runtime tests in [packages/theme-studio-core/tests/Feature/FrontendRuntimeRenderingTest.php](/Users/ben/Sites/packages/capell/capell-packages-4/packages/theme-studio-core/tests/Feature/FrontendRuntimeRenderingTest.php):

```php
use Capell\ThemeStudio\Core\Settings\ThemeStudioSettings;

it('binds theme runtime settings from the core package', function (): void {
    $settings = resolve(ThemeRuntimeSettings::class);

    expect($settings)->toBeInstanceOf(ThemeStudioSettings::class)
        ->and($settings->activeTheme())->toBe('corporate')
        ->and($settings->activePreset())->toBe('boardroom');
});
```

- [ ] **Step 2: Run the single core test file to verify it fails before the move**

Run:

```bash
vendor/bin/pest packages/theme-studio-core/tests/Feature/FrontendRuntimeRenderingTest.php
```

Expected: FAIL because `ThemeRuntimeSettings` is not bound when only the core package is loaded.

- [ ] **Step 3: Create the core-owned settings class with the existing runtime shape**

Create [packages/theme-studio-core/src/Settings/ThemeStudioSettings.php](/Users/ben/Sites/packages/capell/capell-packages-4/packages/theme-studio-core/src/Settings/ThemeStudioSettings.php):

```php
<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Core\Settings;

use Capell\Core\Contracts\SettingsContract;
use Capell\ThemeStudio\Core\Contracts\ThemeRuntimeSettings;
use Capell\ThemeStudio\Core\Data\BrandProfileData;
use Spatie\LaravelSettings\Settings;

class ThemeStudioSettings extends Settings implements SettingsContract, ThemeRuntimeSettings
{
    public string $activeTheme = 'corporate';

    public string $activePreset = 'boardroom';

    public ?string $draftTheme = null;

    public ?string $draftPreset = null;

    public ?int $draftWorkspaceId = null;

    public array $brandProfile = [
        'primaryColor' => '#1a2d6d',
        'accentColor' => '#f59e0b',
        'neutralColor' => '#111827',
        'headingFont' => 'inter',
        'bodyFont' => 'inter',
        'spacing' => 'balanced',
        'alignment' => 'left',
        'cardStyle' => 'subtle',
        'navigationStyle' => 'standard',
        'layoutPresentation' => 'structured',
        'motionIntensity' => 'subtle',
        'mediaTreatment' => 'natural',
    ];

    public array $themeOverrides = [];

    public static function group(): string
    {
        return 'theme_studio';
    }

    public function activeTheme(): string
    {
        return $this->activeTheme;
    }

    public function activePreset(): string
    {
        return $this->activePreset;
    }

    public function brandProfile(): BrandProfileData
    {
        return BrandProfileData::from($this->brandProfile);
    }

    public function themeOverrides(): array
    {
        return $this->themeOverrides;
    }
}
```

- [ ] **Step 4: Create the core-owned settings migration with the existing defaults**

Create [packages/theme-studio-core/database/settings/create_theme_studio_settings.php](/Users/ben/Sites/packages/capell/capell-packages-4/packages/theme-studio-core/database/settings/create_theme_studio_settings.php):

```php
<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $defaults = [
            'theme_studio.activeTheme' => 'corporate',
            'theme_studio.activePreset' => 'boardroom',
            'theme_studio.draftTheme' => null,
            'theme_studio.draftPreset' => null,
            'theme_studio.draftWorkspaceId' => null,
            'theme_studio.brandProfile' => [
                'primaryColor' => '#1a2d6d',
                'accentColor' => '#f59e0b',
                'neutralColor' => '#111827',
                'headingFont' => 'inter',
                'bodyFont' => 'inter',
                'spacing' => 'balanced',
                'alignment' => 'left',
                'cardStyle' => 'subtle',
                'navigationStyle' => 'standard',
                'layoutPresentation' => 'structured',
                'motionIntensity' => 'subtle',
                'mediaTreatment' => 'natural',
            ],
            'theme_studio.themeOverrides' => [],
        ];

        foreach ($defaults as $key => $value) {
            if (! $this->migrator->exists($key)) {
                $this->migrator->add($key, $value);
            }
        }
    }
};
```

- [ ] **Step 5: Bind runtime settings and publish the migration from the core service provider**

Update [packages/theme-studio-core/src/ThemeStudioCoreServiceProvider.php](/Users/ben/Sites/packages/capell/capell-packages-4/packages/theme-studio-core/src/ThemeStudioCoreServiceProvider.php):

```php
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Capell\ThemeStudio\Core\Settings\ThemeStudioSettings;
```

Add these calls inside `register()`:

```php
$this->app->bind(ThemeRuntimeSettings::class, ThemeStudioSettings::class);

$this->app->afterResolving(
    SettingsSchemaRegistry::class,
    function (SettingsSchemaRegistry $registry): void {
        $registry->registerSettingsClass(ThemeStudioSettings::group(), ThemeStudioSettings::class);
    },
);
```

Add this `packageBooted()` method:

```php
public function packageBooted(): void
{
    if (! $this->app->runningInConsole()) {
        return;
    }

    $this->publishes([
        __DIR__ . '/../database/settings/create_theme_studio_settings.php' => database_path('settings/create_theme_studio_settings.php'),
    ], 'capell-theme-studio-core-settings');
}
```

- [ ] **Step 6: Run the core test file to verify the new binding passes**

Run:

```bash
vendor/bin/pest packages/theme-studio-core/tests/Feature/FrontendRuntimeRenderingTest.php
```

Expected: PASS, including the new assertion that `ThemeRuntimeSettings` resolves to the core settings class.

- [ ] **Step 7: Commit the core runtime ownership move**

```bash
git add packages/theme-studio-core/src/Settings/ThemeStudioSettings.php packages/theme-studio-core/database/settings/create_theme_studio_settings.php packages/theme-studio-core/src/ThemeStudioCoreServiceProvider.php packages/theme-studio-core/tests/Feature/FrontendRuntimeRenderingTest.php
git commit -m "refactor: move theme studio runtime settings to core"
```

### Task 2: Rewire the admin package to consume the core settings class

**Files:**

- Modify: `packages/theme-studio-admin/src/ThemeStudioAdminServiceProvider.php`
- Modify: `packages/theme-studio-admin/src/Actions/ActivateApprovedThemeDraftAction.php`
- Modify: `packages/theme-studio-admin/src/Actions/PublishThemeDraftAction.php`
- Modify: `packages/theme-studio-admin/src/Actions/ResolveThemePublishingReadinessAction.php`
- Modify: `packages/theme-studio-admin/src/Actions/StageThemeDraftAction.php`
- Modify: `packages/theme-studio-admin/src/Contracts/ThemeDraftPublisher.php`
- Modify: `packages/theme-studio-admin/src/Filament/Pages/ThemeStudioPage.php`
- Modify: `packages/theme-studio-admin/src/Publishing/StandaloneThemeDraftPublisher.php`
- Modify: `packages/theme-studio-admin/src/Publishing/WorkspaceThemeDraftPublisher.php`
- Modify: `packages/theme-studio-admin/src/Schemas/ThemeStudioSettingsSchema.php`
- Delete: `packages/theme-studio-admin/src/Settings/ThemeStudioSettings.php`
- Delete: `packages/theme-studio-admin/database/settings/create_theme_studio_settings.php`
- Test: `packages/theme-studio-admin/tests/Feature/ThemeStudioAdminTest.php`

- [ ] **Step 1: Write the failing admin imports test by switching the test file to the core namespace first**

In [packages/theme-studio-admin/tests/Feature/ThemeStudioAdminTest.php](/Users/ben/Sites/packages/capell/capell-packages-4/packages/theme-studio-admin/tests/Feature/ThemeStudioAdminTest.php), replace:

```php
use Capell\ThemeStudio\Admin\Settings\ThemeStudioSettings;
```

with:

```php
use Capell\ThemeStudio\Core\Settings\ThemeStudioSettings;
```

- [ ] **Step 2: Run the admin test file to verify imports still point at the old settings class**

Run:

```bash
vendor/bin/pest packages/theme-studio-admin/tests/Feature/ThemeStudioAdminTest.php
```

Expected: FAIL with import or type resolution errors until the admin package is rewired.

- [ ] **Step 3: Remove runtime registration from the admin service provider and keep only schema registration**

Update [packages/theme-studio-admin/src/ThemeStudioAdminServiceProvider.php](/Users/ben/Sites/packages/capell/capell-packages-4/packages/theme-studio-admin/src/ThemeStudioAdminServiceProvider.php):

```php
use Capell\ThemeStudio\Core\Settings\ThemeStudioSettings;
```

Remove:

```php
->registerRuntimeSettings()
```

Remove the whole method:

```php
private function registerRuntimeSettings(): self
{
    $this->app->bind(ThemeRuntimeSettings::class, ThemeStudioSettings::class);

    return $this;
}
```

Keep `registerSettings()` so the schema is still registered:

```php
$registry->registerSettingsClass(ThemeStudioSettings::group(), ThemeStudioSettings::class);
$registry->register(ThemeStudioSettings::group(), ThemeStudioSettingsSchema::class);
```

- [ ] **Step 4: Update every admin consumer to import the core settings class**

In each of the following files, replace the admin settings import with the core settings import:

- [packages/theme-studio-admin/src/Actions/ActivateApprovedThemeDraftAction.php](/Users/ben/Sites/packages/capell/capell-packages-4/packages/theme-studio-admin/src/Actions/ActivateApprovedThemeDraftAction.php)
- [packages/theme-studio-admin/src/Actions/PublishThemeDraftAction.php](/Users/ben/Sites/packages/capell/capell-packages-4/packages/theme-studio-admin/src/Actions/PublishThemeDraftAction.php)
- [packages/theme-studio-admin/src/Actions/ResolveThemePublishingReadinessAction.php](/Users/ben/Sites/packages/capell/capell-packages-4/packages/theme-studio-admin/src/Actions/ResolveThemePublishingReadinessAction.php)
- [packages/theme-studio-admin/src/Actions/StageThemeDraftAction.php](/Users/ben/Sites/packages/capell/capell-packages-4/packages/theme-studio-admin/src/Actions/StageThemeDraftAction.php)
- [packages/theme-studio-admin/src/Contracts/ThemeDraftPublisher.php](/Users/ben/Sites/packages/capell/capell-packages-4/packages/theme-studio-admin/src/Contracts/ThemeDraftPublisher.php)
- [packages/theme-studio-admin/src/Filament/Pages/ThemeStudioPage.php](/Users/ben/Sites/packages/capell/capell-packages-4/packages/theme-studio-admin/src/Filament/Pages/ThemeStudioPage.php)
- [packages/theme-studio-admin/src/Publishing/StandaloneThemeDraftPublisher.php](/Users/ben/Sites/packages/capell/capell-packages-4/packages/theme-studio-admin/src/Publishing/StandaloneThemeDraftPublisher.php)
- [packages/theme-studio-admin/src/Publishing/WorkspaceThemeDraftPublisher.php](/Users/ben/Sites/packages/capell/capell-packages-4/packages/theme-studio-admin/src/Publishing/WorkspaceThemeDraftPublisher.php)
- [packages/theme-studio-admin/src/Schemas/ThemeStudioSettingsSchema.php](/Users/ben/Sites/packages/capell/capell-packages-4/packages/theme-studio-admin/src/Schemas/ThemeStudioSettingsSchema.php) if it imports the settings class later during cleanup

The replacement import should be:

```php
use Capell\ThemeStudio\Core\Settings\ThemeStudioSettings;
```

- [ ] **Step 5: Delete the old admin-owned settings artifacts**

Remove:

```text
packages/theme-studio-admin/src/Settings/ThemeStudioSettings.php
packages/theme-studio-admin/database/settings/create_theme_studio_settings.php
```

- [ ] **Step 6: Run the admin feature tests to verify the page and publishing flow still work**

Run:

```bash
vendor/bin/pest packages/theme-studio-admin/tests/Feature/ThemeStudioAdminTest.php
```

Expected: PASS with the admin package reading and mutating the core-owned settings class.

- [ ] **Step 7: Commit the admin rewiring**

```bash
git add packages/theme-studio-admin/src packages/theme-studio-admin/tests
git rm packages/theme-studio-admin/src/Settings/ThemeStudioSettings.php packages/theme-studio-admin/database/settings/create_theme_studio_settings.php
git commit -m "refactor: decouple theme studio admin from runtime settings"
```

### Task 3: Update test bootstraps so core-only installs are explicitly covered

**Files:**

- Modify: `packages/theme-studio-core/tests/ThemeStudioCoreTestCase.php`
- Modify: `packages/theme-studio-admin/tests/ThemeStudioAdminTestCase.php`
- Modify: `packages/theme-studio-admin/tests/Feature/ThemeStudioAdminTest.php`
- Test: `packages/theme-studio-core/tests/Feature/FrontendRuntimeRenderingTest.php`
- Test: `packages/theme-studio-admin/tests/Feature/ThemeStudioAdminTest.php`

- [ ] **Step 1: Make the core test case load the moved settings migration**

Update [packages/theme-studio-core/tests/ThemeStudioCoreTestCase.php](/Users/ben/Sites/packages/capell/capell-packages-4/packages/theme-studio-core/tests/ThemeStudioCoreTestCase.php) to register the core settings migration during setup:

```php
protected function setUp(): void
{
    parent::setUp();

    $this->registerAndMigrateSettings(
        ['create_theme_studio_settings'],
        __DIR__ . '/../database/settings',
    );
}
```

- [ ] **Step 2: Create the test migration directory for core if it does not already exist**

Ensure this path exists and contains the moved migration:

```text
packages/theme-studio-core/database/settings/create_theme_studio_settings.php
```

No separate test-only migration copy is needed; reuse the package migration path above.

- [ ] **Step 3: Point the admin test case at the core migration path**

Update [packages/theme-studio-admin/tests/ThemeStudioAdminTestCase.php](/Users/ben/Sites/packages/capell/capell-packages-4/packages/theme-studio-admin/tests/ThemeStudioAdminTestCase.php):

```php
$this->registerAndMigrateSettings(
    ['create_theme_studio_settings'],
    __DIR__ . '/../../core/database/settings',
);
```

- [ ] **Step 4: Tighten the admin test assertion so it no longer expects a schema method on the settings class**

Replace this assertion in [packages/theme-studio-admin/tests/Feature/ThemeStudioAdminTest.php](/Users/ben/Sites/packages/capell/capell-packages-4/packages/theme-studio-admin/tests/Feature/ThemeStudioAdminTest.php):

```php
expect(ThemeStudioSettings::group())->toBe('theme_studio')
    ->and(ThemeStudioSettings::schema())->toBe(ThemeStudioSettingsSchema::class)
    ->and($components)->not->toBeEmpty();
```

with:

```php
expect(ThemeStudioSettings::group())->toBe('theme_studio')
    ->and($components)->not->toBeEmpty();
```

This keeps the test aligned with the new ownership split: the settings class lives in core, and the editable schema remains registered by admin.

- [ ] **Step 5: Run the core and admin test files together**

Run:

```bash
vendor/bin/pest packages/theme-studio-core/tests/Feature/FrontendRuntimeRenderingTest.php packages/theme-studio-admin/tests/Feature/ThemeStudioAdminTest.php
```

Expected: PASS for both files, proving the core-only path and admin-enabled path both work.

- [ ] **Step 6: Commit the bootstrap and regression coverage updates**

```bash
git add packages/theme-studio-core/tests/ThemeStudioCoreTestCase.php packages/theme-studio-admin/tests/ThemeStudioAdminTestCase.php packages/theme-studio-admin/tests/Feature/ThemeStudioAdminTest.php
git commit -m "test: cover core-only theme studio runtime settings"
```

### Task 4: Run broader verification and clean up package metadata

**Files:**

- Modify: `packages/theme-studio-core/README.md`
- Modify: `packages/theme-studio-admin/README.md`
- Test: `packages/theme-studio-core/tests/Feature/FrontendRuntimeRenderingTest.php`
- Test: `packages/theme-studio-admin/tests/Feature/ThemeStudioAdminTest.php`

- [ ] **Step 1: Update the package READMEs to reflect the new ownership split**

In [packages/theme-studio-core/README.md](/Users/ben/Sites/packages/capell/capell-packages-4/packages/theme-studio-core/README.md), add or revise copy like:

```md
Theme Studio Core provides the runtime theme registry, preview context, portable page rendering, and persisted Theme Studio runtime settings used by frontend rendering.
```

In [packages/theme-studio-admin/README.md](/Users/ben/Sites/packages/capell/capell-packages-4/packages/theme-studio-admin/README.md), revise the admin surface description to make the dependency direction explicit:

```md
Theme Studio Admin adds the optional Filament Studio page for choosing, previewing, staging, and publishing themes on top of Theme Studio Core.
```

- [ ] **Step 2: Run the package-level core tests**

Run:

```bash
vendor/bin/pest packages/theme-studio-core/tests
```

Expected: PASS for all Theme Studio core tests.

- [ ] **Step 3: Run the package-level admin tests**

Run:

```bash
vendor/bin/pest packages/theme-studio-admin/tests
```

Expected: PASS for all Theme Studio admin tests.

- [ ] **Step 4: Run repo verification commands required by the package guidelines**

Run:

```bash
composer test
composer preflight
```

Expected:

- `composer test`: PASS
- `composer preflight`: PASS

- [ ] **Step 5: Commit the final metadata and verification pass**

```bash
git add packages/theme-studio-core/README.md packages/theme-studio-admin/README.md
git commit -m "docs: clarify theme studio core and admin roles"
```

## Self-Review

- Spec coverage:
    - runtime settings move to core: Task 1
    - admin becomes optional UI/publishing layer: Task 2
    - core-only regression coverage: Task 3
    - package messaging and final verification: Task 4
- Placeholder scan:
    - no `TODO`, `TBD`, or “similar to above” shortcuts remain
    - every task includes exact file paths and commands
- Type consistency:
    - `Capell\ThemeStudio\Core\Settings\ThemeStudioSettings` is the only target settings class throughout the plan
    - `ThemeRuntimeSettings` remains the runtime contract
