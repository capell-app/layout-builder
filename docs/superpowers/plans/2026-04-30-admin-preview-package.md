# Admin Preview Package Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move Admin Preview integration into an optional `capell-app/admin-preview` package that previews PublishingStudio draft websites in an iframe modal.

**Architecture:** PublishingStudio owns draft preview URLs and exposes a small tagged table-action contributor contract. The new Admin Preview package owns `pboivin/admin-preview`, registers its panel plugin via `AdminPanelExtender`, and contributes a PublishingStudio modal preview action that opens Peek's iframe modal with the existing signed workspace preview URL.

**Tech Stack:** Laravel 11/12/13 package development, Filament 4/5 actions and panels, `pboivin/admin-preview:^4.1`, Capell package registry, Pest.

---

## File Map

- `packages/publishing-studio/src/Contracts/WorkspaceTableActionContributor.php` — new optional action extension contract for PublishingStudio tables.
- `packages/publishing-studio/src/Filament/Resources/PublishingStudio/Tables/PublishingStudioTable.php` — append tagged contributor actions after the existing new-tab preview action.
- `packages/publishing-studio/tests/Unit/WorkspaceTableActionContributorTest.php` — verifies the contract tag and table action merge without Peek.
- `packages/publishing-studio/tests/PublishingStudioTestCase.php` — remove direct `AdminPreviewServiceProvider` registration.
- `tests/AbstractTestCase.php` — remove direct `AdminPreviewServiceProvider` registration once the root test harness no longer needs it.
- `packages/admin-preview/composer.json` — new optional package manifest.
- `packages/admin-preview/src/Providers/AdminPreviewServiceProvider.php` — register package metadata, translations, admin provider.
- `packages/admin-preview/src/Providers/AdminServiceProvider.php` — tag the admin panel extender and PublishingStudio action contributor.
- `packages/admin-preview/src/Filament/Extenders/AdminPreviewAdminPanelExtender.php` — registers `Pboivin\AdminPreview\AdminPreviewPlugin` on the panel.
- `packages/admin-preview/src/Filament/Resources/PublishingStudio/Actions/WorkspacePeekPreviewAction.php` — action that dispatches Peek's iframe modal event with a PublishingStudio preview URL.
- `packages/admin-preview/src/PublishingStudio/WorkspacePeekPreviewActionContributor.php` — returns the modal preview action when required packages are installed.
- `packages/admin-preview/resources/lang/en/workspace.php` — labels for the modal preview action.
- `packages/admin-preview/tests/...` — focused unit and feature tests for provider registration, action contribution, and URL generation.
- `composer.json` — add package path autoload entries and move `pboivin/admin-preview` out of root/global require if possible.
- Companion admin repo: `vendor/capell-app/admin/composer.json` and `vendor/capell-app/admin/src/Providers/Filament/AdminPanelProvider.php` — remove hard Admin Preview dependency and direct plugin registration.

## Task 1: Add PublishingStudio Table Action Extension Point

**Files:**

- Create: `packages/publishing-studio/src/Contracts/WorkspaceTableActionContributor.php`
- Modify: `packages/publishing-studio/src/Filament/Resources/PublishingStudio/Tables/PublishingStudioTable.php`
- Test: `packages/publishing-studio/tests/Unit/WorkspaceTableActionContributorTest.php`
- Modify: `packages/publishing-studio/tests/PublishingStudioTestCase.php`

- [ ] **Step 1: Write the contract test**

Create `packages/publishing-studio/tests/Unit/WorkspaceTableActionContributorTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\PublishingStudio\Contracts\WorkspaceTableActionContributor;

it('defines the workspace table action contributor tag', function (): void {
    expect(WorkspaceTableActionContributor::TAG)
        ->toBe('capell.publishing-studio.table_action_contributors');
});
```

- [ ] **Step 2: Run the test and verify it fails**

Run:

```bash
vendor/bin/pest packages/publishing-studio/tests/Unit/WorkspaceTableActionContributorTest.php --no-coverage
```

Expected: fail because `Capell\PublishingStudio\Contracts\WorkspaceTableActionContributor` does not exist.

- [ ] **Step 3: Create the contract**

Create `packages/publishing-studio/src/Contracts/WorkspaceTableActionContributor.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Contracts;

interface WorkspaceTableActionContributor
{
    public const TAG = 'capell.publishing-studio.table_action_contributors';

    /**
     * @return array<int, object>
     */
    public function actions(): array;
}
```

- [ ] **Step 4: Make PublishingStudioTable append contributor actions**

In `packages/publishing-studio/src/Filament/Resources/PublishingStudio/Tables/PublishingStudioTable.php`, import the contract:

```php
use Capell\PublishingStudio\Contracts\WorkspaceTableActionContributor;
```

Replace the direct `->recordActions([...])` call with:

```php
            ->recordActions(static::getRecordActions())
```

Add this method before `getTableColumns()`:

```php
    protected static function getRecordActions(): array
    {
        return [
            EditAction::make()
                ->modalWidth(Width::ScreenLarge)
                ->slideOver()
                ->hidden(fn (Workspace $record): bool => $record->trashed()),
            SaveAsDraftAction::make(),
            SubmitForApprovalAction::make(),
            ApproveAction::make(),
            RequestChangesAction::make(),
            RejectAction::make(),
            PublishAction::make(),
            ScheduleAction::make(),
            UnscheduleAction::make(),
            PreviewAction::make(),
            ...static::getContributorRecordActions(),
            ValidateAction::make(),
            CompareAction::make(),
            RollbackAction::make(),
            ActionGroup::make([
                DeleteAction::make(),
                RestoreAction::make(),
            ])
                ->color('gray'),
        ];
    }

    protected static function getContributorRecordActions(): array
    {
        /** @var iterable<WorkspaceTableActionContributor> $contributors */
        $contributors = app()->tagged(WorkspaceTableActionContributor::TAG);

        $actions = [];

        foreach ($contributors as $contributor) {
            array_push($actions, ...$contributor->actions());
        }

        return $actions;
    }
```

- [ ] **Step 5: Remove direct Peek provider from PublishingStudio tests**

In `packages/publishing-studio/tests/PublishingStudioTestCase.php`, delete:

```php
use Pboivin\AdminPreview\AdminPreviewServiceProvider;
```

Remove this provider from `getPackageProviders()`:

```php
            AdminPreviewServiceProvider::class,
```

- [ ] **Step 6: Run focused PublishingStudio tests**

Run:

```bash
vendor/bin/pest packages/publishing-studio/tests/Unit/WorkspaceTableActionContributorTest.php packages/publishing-studio/tests/Feature/Actions/GenerateWorkspacePreviewUrlActionTest.php --no-coverage
```

Expected: pass.

- [ ] **Step 7: Commit**

```bash
git add packages/publishing-studio/src/Contracts/WorkspaceTableActionContributor.php \
        packages/publishing-studio/src/Filament/Resources/PublishingStudio/Tables/PublishingStudioTable.php \
        packages/publishing-studio/tests/Unit/WorkspaceTableActionContributorTest.php \
        packages/publishing-studio/tests/PublishingStudioTestCase.php
git commit -m "feat(publishing-studio): add table action contributors"
```

## Task 2: Scaffold Optional Admin Preview Package

**Files:**

- Create: `packages/admin-preview/composer.json`
- Create: `packages/admin-preview/src/Providers/AdminPreviewServiceProvider.php`
- Create: `packages/admin-preview/src/Providers/AdminServiceProvider.php`
- Create: `packages/admin-preview/resources/lang/en/package.php`
- Create: `packages/admin-preview/tests/AdminPreviewTestCase.php`
- Modify: `composer.json`
- Modify: `tests/Pest.php`
- Test: `packages/admin-preview/tests/Unit/Providers/AdminPreviewServiceProviderTest.php`

- [ ] **Step 1: Write provider registration test**

Create `packages/admin-preview/tests/Unit/Providers/AdminPreviewServiceProviderTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\AdminPreview\Providers\AdminPreviewServiceProvider;

it('registers the package with Capell Core', function (): void {
    expect(CapellCore::getPackage(AdminPreviewServiceProvider::$packageName)->name)
        ->toBe('capell-app/admin-preview');
});
```

- [ ] **Step 2: Create package manifest**

Create `packages/admin-preview/composer.json`:

```json
{
    "name": "capell-app/admin-preview",
    "description": "Optional Admin Preview iframe previews for Capell admin and PublishingStudio drafts",
    "keywords": ["capell", "filament", "preview", "publishing-studio"],
    "license": "proprietary",
    "require": {
        "php": "^8.2",
        "capell-app/admin": "*",
        "capell-app/frontend": "*",
        "capell-app/publishing-studio": "*",
        "pboivin/admin-preview": "^4.1"
    },
    "autoload": {
        "psr-4": {
            "Capell\\AdminPreview\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Capell\\AdminPreview\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Capell\\AdminPreview\\Providers\\AdminPreviewServiceProvider"
            ]
        }
    },
    "config": {
        "sort-packages": true
    },
    "prefer-stable": true
}
```

- [ ] **Step 3: Add root autoload mappings**

In root `composer.json`, add to `autoload.psr-4`:

```json
"Capell\\AdminPreview\\": "packages/admin-preview/src",
```

Add to `autoload-dev.psr-4`:

```json
"Capell\\AdminPreview\\Tests\\": "packages/admin-preview/tests",
```

Keep `pboivin/admin-preview` in root `require` until Task 5 removes direct admin usage. This avoids breaking the current symlinked admin package mid-plan.

- [ ] **Step 4: Create package test case and Pest mapping**

Create `packages/admin-preview/tests/AdminPreviewTestCase.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\AdminPreview\Tests;

use Capell\Core\Facades\CapellCore;
use Capell\AdminPreview\Providers\AdminPreviewServiceProvider;
use Capell\Tests\AbstractTestCase;
use Override;

abstract class AdminPreviewTestCase extends AbstractTestCase
{
    protected function getPackageServiceName(): string
    {
        return 'capell-admin-preview';
    }

    /**
     * @return class-string[]
     */
    #[Override]
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            AdminPreviewServiceProvider::class,
        ];
    }

    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(AdminPreviewServiceProvider::$packageName);
    }
}
```

In `tests/Pest.php`, add the import:

```php
use Capell\AdminPreview\Tests\AdminPreviewTestCase;
```

Add the Pest mapping near the other package mappings:

```php
pest()->extend(AdminPreviewTestCase::class)->in('../packages/admin-preview/tests');
```

- [ ] **Step 5: Create service providers and translation**

Create `packages/admin-preview/src/Providers/AdminPreviewServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\AdminPreview\Providers;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Spatie\LaravelPackageTools\Package;

final class AdminPreviewServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-admin-preview';

    public static string $packageName = 'capell-app/admin-preview';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasTranslations();
    }

    public function registeringPackage(): void
    {
        $this->app->register(AdminServiceProvider::class);
    }

    public function packageRegistered(): void
    {
        CapellCore::registerPackage(
            self::$packageName,
            type: self::getType(),
            serviceProviderClass: self::class,
            path: realpath(__DIR__ . '/../..'),
            version: CapellCore::getInstalledPrettyVersion(self::$packageName),
            description: fn (): string => __('capell-admin-preview::package.description'),
        );
    }
}
```

Create `packages/admin-preview/src/Providers/AdminServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\AdminPreview\Providers;

use Illuminate\Support\ServiceProvider;

final class AdminServiceProvider extends ServiceProvider
{
    public function register(): void {}
}
```

Create `packages/admin-preview/resources/lang/en/package.php`:

```php
<?php

declare(strict_types=1);

return [
    'description' => 'Optional iframe previews for Capell admin using Admin Preview.',
];
```

- [ ] **Step 6: Run composer dump-autoload and provider test**

Run:

```bash
composer dump-autoload
vendor/bin/pest packages/admin-preview/tests/Unit/Providers/AdminPreviewServiceProviderTest.php --no-coverage
```

Expected: pass.

- [ ] **Step 7: Commit**

```bash
git add composer.json tests/Pest.php packages/admin-preview
git commit -m "feat(admin-preview): add optional package skeleton"
```

## Task 3: Register Peek Plugin Through Admin Extender

**Files:**

- Create: `packages/admin-preview/src/Filament/Extenders/AdminPreviewAdminPanelExtender.php`
- Modify: `packages/admin-preview/src/Providers/AdminServiceProvider.php`
- Test: `packages/admin-preview/tests/Unit/Filament/Extenders/AdminPreviewAdminPanelExtenderTest.php`

- [ ] **Step 1: Write extender test**

Create `packages/admin-preview/tests/Unit/Filament/Extenders/AdminPreviewAdminPanelExtenderTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\Admin\Contracts\Extenders\AdminPanelExtender;
use Capell\AdminPreview\Filament\Extenders\AdminPreviewAdminPanelExtender;

it('implements the admin panel extender contract', function (): void {
    expect(AdminPreviewAdminPanelExtender::class)
        ->toImplement(AdminPanelExtender::class);
});

it('is tagged as an admin panel extender', function (): void {
    $extenders = collect(app()->tagged(AdminPanelExtender::TAG))
        ->map(fn (object $extender): string => $extender::class)
        ->all();

    expect($extenders)->toContain(AdminPreviewAdminPanelExtender::class);
});
```

- [ ] **Step 2: Implement extender**

Create `packages/admin-preview/src/Filament/Extenders/AdminPreviewAdminPanelExtender.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\AdminPreview\Filament\Extenders;

use Capell\Admin\Contracts\Extenders\AdminPanelExtender;
use Filament\Panel;
use Pboivin\AdminPreview\AdminPreviewPlugin;

final class AdminPreviewAdminPanelExtender implements AdminPanelExtender
{
    public function extend(Panel $panel): void
    {
        if ($panel->hasPlugin(AdminPreviewPlugin::ID)) {
            return;
        }

        $panel->plugin(AdminPreviewPlugin::make());
    }
}
```

Modify `packages/admin-preview/src/Providers/AdminServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\AdminPreview\Providers;

use Capell\Admin\Contracts\Extenders\AdminPanelExtender;
use Capell\AdminPreview\Filament\Extenders\AdminPreviewAdminPanelExtender;
use Illuminate\Support\ServiceProvider;

final class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag([AdminPreviewAdminPanelExtender::class], AdminPanelExtender::TAG);
    }
}
```

- [ ] **Step 3: Run extender tests**

Run:

```bash
vendor/bin/pest packages/admin-preview/tests/Unit/Filament/Extenders/AdminPreviewAdminPanelExtenderTest.php --no-coverage
```

Expected: pass.

- [ ] **Step 4: Commit**

```bash
git add packages/admin-preview/src/Filament/Extenders \
        packages/admin-preview/src/Providers/AdminServiceProvider.php \
        packages/admin-preview/tests/Unit/Filament/Extenders
git commit -m "feat(admin-preview): register peek panel plugin"
```

## Task 4: Add PublishingStudio Modal Preview Action

**Files:**

- Create: `packages/admin-preview/src/Filament/Resources/PublishingStudio/Actions/WorkspacePeekPreviewAction.php`
- Create: `packages/admin-preview/src/PublishingStudio/WorkspacePeekPreviewActionContributor.php`
- Modify: `packages/admin-preview/src/Providers/AdminServiceProvider.php`
- Create: `packages/admin-preview/resources/lang/en/workspace.php`
- Test: `packages/admin-preview/tests/Unit/PublishingStudio/WorkspacePeekPreviewActionContributorTest.php`
- Test: `packages/admin-preview/tests/Feature/PublishingStudio/WorkspacePeekPreviewActionTest.php`

- [ ] **Step 1: Write contributor test**

Create `packages/admin-preview/tests/Unit/PublishingStudio/WorkspacePeekPreviewActionContributorTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\AdminPreview\Filament\Resources\PublishingStudio\Actions\WorkspacePeekPreviewAction;
use Capell\AdminPreview\PublishingStudio\WorkspacePeekPreviewActionContributor;
use Capell\PublishingStudio\Contracts\WorkspaceTableActionContributor;

it('implements the workspace table action contributor contract', function (): void {
    expect(WorkspacePeekPreviewActionContributor::class)
        ->toImplement(WorkspaceTableActionContributor::class);
});

it('contributes the workspace peek preview action', function (): void {
    $actions = (new WorkspacePeekPreviewActionContributor)->actions();

    expect($actions)->toHaveCount(1)
        ->and($actions[0])->toBeInstanceOf(WorkspacePeekPreviewAction::class);
});
```

- [ ] **Step 2: Implement contributor and translations**

Create `packages/admin-preview/src/PublishingStudio/WorkspacePeekPreviewActionContributor.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\AdminPreview\PublishingStudio;

use Capell\AdminPreview\Filament\Resources\PublishingStudio\Actions\WorkspacePeekPreviewAction;
use Capell\PublishingStudio\Contracts\WorkspaceTableActionContributor;

final class WorkspacePeekPreviewActionContributor implements WorkspaceTableActionContributor
{
    public function actions(): array
    {
        return [
            WorkspacePeekPreviewAction::make(),
        ];
    }
}
```

Create `packages/admin-preview/resources/lang/en/workspace.php`:

```php
<?php

declare(strict_types=1);

return [
    'actions' => [
        'preview_modal' => 'Preview in modal',
        'preview_modal_tooltip' => 'Preview this workspace draft in an embedded website frame.',
        'preview_modal_title' => 'Workspace preview',
    ],
];
```

Modify `packages/admin-preview/src/Providers/AdminServiceProvider.php` to tag the contributor:

```php
use Capell\AdminPreview\PublishingStudio\WorkspacePeekPreviewActionContributor;
use Capell\PublishingStudio\Contracts\WorkspaceTableActionContributor;
```

Inside `register()`:

```php
        $this->app->tag([WorkspacePeekPreviewActionContributor::class], WorkspaceTableActionContributor::TAG);
```

- [ ] **Step 3: Implement modal action**

Create `packages/admin-preview/src/Filament/Resources/PublishingStudio/Actions/WorkspacePeekPreviewAction.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\AdminPreview\Filament\Resources\PublishingStudio\Actions;

use Capell\Core\Facades\CapellCore;
use Capell\PublishingStudio\Actions\GenerateWorkspacePreviewUrlAction;
use Capell\PublishingStudio\Models\Workspace;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Override;
use Pboivin\AdminPreview\Facades\Peek;

final class WorkspacePeekPreviewAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('capell-admin-preview::workspace.actions.preview_modal'))
            ->tooltip(__('capell-admin-preview::workspace.actions.preview_modal_tooltip'))
            ->icon(Heroicon::OutlinedComputerDesktop)
            ->color('gray')
            ->authorize('view')
            ->visible(fn (): bool => CapellCore::isPackageInstalled('capell-app/frontend'))
            ->action(function (Workspace $record): void {
                Peek::ensurePluginIsLoaded();

                $this->dispatch(
                    'open-preview-modal',
                    modalTitle: __('capell-admin-preview::workspace.actions.preview_modal_title'),
                    iframeUrl: (new GenerateWorkspacePreviewUrlAction)->handle($record),
                    iframeContent: null,
                );
            });

        Peek::registerPreviewModal();
    }

    public static function getDefaultName(): ?string
    {
        return 'workspacePeekPreview';
    }
}
```

- [ ] **Step 4: Write URL generation feature test**

Create `packages/admin-preview/tests/Feature/PublishingStudio/WorkspacePeekPreviewActionTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\PublishingStudio\Http\Middleware\ResolveWorkspaceContext;
use Capell\PublishingStudio\Models\PreviewLink;
use Capell\PublishingStudio\Models\Workspace;

it('generates a workspace draft preview link for the iframe modal', function (): void {
    $workspace = Workspace::factory()->create();

    $url = (new Capell\PublishingStudio\Actions\GenerateWorkspacePreviewUrlAction)->handle($workspace);

    expect($url)
        ->toContain(ResolveWorkspaceContext::QUERY_PARAM . '=' . $workspace->uuid)
        ->toContain(ResolveWorkspaceContext::TOKEN_PARAM . '=');

    expect(PreviewLink::query()->where('workspace_id', $workspace->id)->exists())->toBeTrue();
});
```

- [ ] **Step 5: Run package action tests**

Run:

```bash
vendor/bin/pest packages/admin-preview/tests/Unit/PublishingStudio/WorkspacePeekPreviewActionContributorTest.php packages/admin-preview/tests/Feature/PublishingStudio/WorkspacePeekPreviewActionTest.php --no-coverage
```

Expected: pass.

- [ ] **Step 6: Run PublishingStudio table test again**

Run:

```bash
vendor/bin/pest packages/publishing-studio/tests/Unit/WorkspaceTableActionContributorTest.php packages/admin-preview/tests --no-coverage
```

Expected: pass.

- [ ] **Step 7: Commit**

```bash
git add packages/admin-preview/src packages/admin-preview/resources packages/admin-preview/tests
git commit -m "feat(admin-preview): add workspace modal preview"
```

## Task 5: Remove Global Peek Registration

**Files:**

- Modify: `tests/AbstractTestCase.php`
- Modify: root `composer.json`
- Companion admin repo modify: `vendor/capell-app/admin/src/Providers/Filament/AdminPanelProvider.php`
- Companion admin repo modify: `vendor/capell-app/admin/composer.json`

- [ ] **Step 1: Remove root test harness Peek provider**

In `tests/AbstractTestCase.php`, delete:

```php
use Pboivin\AdminPreview\AdminPreviewServiceProvider;
```

Remove:

```php
            AdminPreviewServiceProvider::class,
```

- [ ] **Step 2: Remove direct admin panel plugin registration in companion admin package**

In `vendor/capell-app/admin/src/Providers/Filament/AdminPanelProvider.php`, delete:

```php
use Pboivin\AdminPreview\AdminPreviewPlugin;
```

Remove:

```php
            ->plugin(AdminPreviewPlugin::make())
```

The panel should still register:

```php
            ->plugin(CapellAdminPlugin::make()
                ->discoverConfigurators(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources'))
```

- [ ] **Step 3: Move Composer dependency**

In root `composer.json`, keep `pboivin/admin-preview` only if the monorepo needs it to run package tests before Composer path resolution includes `packages/admin-preview`. If Composer accepts the local package dependency, remove this root require line:

```json
"pboivin/admin-preview": "^4.0",
```

In `vendor/capell-app/admin/composer.json`, remove:

```json
"pboivin/admin-preview": "^4.0",
```

Do not remove it from `packages/admin-preview/composer.json`.

- [ ] **Step 4: Refresh autoload**

Run:

```bash
composer dump-autoload
```

Expected: autoload completes without class resolution errors.

- [ ] **Step 5: Run focused dependency tests**

Run:

```bash
vendor/bin/pest packages/publishing-studio/tests/Unit/WorkspaceTableActionContributorTest.php packages/admin-preview/tests --no-coverage
```

Expected: pass.

- [ ] **Step 6: Commit package repo cleanup**

```bash
git add composer.json tests/AbstractTestCase.php
git commit -m "chore: remove global filament peek test registration"
```

- [ ] **Step 7: Commit companion admin repo cleanup separately**

Run from the companion admin repository root if it is a separate git repo:

```bash
git -C vendor/capell-app/admin status --short
git -C vendor/capell-app/admin add composer.json src/Providers/Filament/AdminPanelProvider.php
git -C vendor/capell-app/admin commit -m "chore(admin): move filament peek to optional package"
```

If `vendor/capell-app/admin` is not a standalone git root, stage those files in its actual repository root instead.

## Task 6: Final Verification

**Files:**

- No planned code changes.

- [ ] **Step 1: Run focused package suites**

Run:

```bash
vendor/bin/pest packages/publishing-studio/tests packages/admin-preview/tests --no-coverage
```

Expected: pass.

- [ ] **Step 2: Run static checks**

Run:

```bash
composer preflight
```

Expected: pass. If unrelated existing worktree changes fail preflight, capture the failing files and confirm whether they are outside this task.

- [ ] **Step 3: Inspect dependency references**

Run:

```bash
rg -n "Pboivin\\\\AdminPreview|AdminPreviewPlugin|AdminPreviewServiceProvider|pboivin/admin-preview" composer.json tests packages vendor/capell-app/admin/src vendor/capell-app/admin/composer.json --glob '!vendor/pboivin'
```

Expected:

- `packages/admin-preview` references Peek classes and Composer dependency.
- no PublishingStudio source or tests import Peek classes.
- no root shared test harness imports `AdminPreviewServiceProvider`.
- companion admin panel provider no longer registers `AdminPreviewPlugin` directly.

- [ ] **Step 4: Final commit if verification required small fixes**

If verification required fixes, commit only files from this plan:

```bash
git status --short
git add composer.json tests/AbstractTestCase.php packages/publishing-studio packages/admin-preview docs/superpowers/plans/2026-04-30-admin-preview-package.md
git commit -m "fix(admin-preview): complete optional preview integration"
```
