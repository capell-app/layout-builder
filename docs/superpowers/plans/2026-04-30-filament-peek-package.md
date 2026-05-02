# Filament Peek Package Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move Filament Peek integration into an optional `capell-app/filament-peek` package that previews Workspaces draft websites in an iframe modal.

**Architecture:** Workspaces owns draft preview URLs and exposes a small tagged table-action contributor contract. The new Filament Peek package owns `pboivin/filament-peek`, registers its panel plugin via `AdminPanelExtender`, and contributes a Workspaces modal preview action that opens Peek's iframe modal with the existing signed workspace preview URL.

**Tech Stack:** Laravel 11/12/13 package development, Filament 4/5 actions and panels, `pboivin/filament-peek:^4.1`, Capell package registry, Pest.

---

## File Map

- `packages/workspaces/src/Contracts/WorkspaceTableActionContributor.php` — new optional action extension contract for Workspaces tables.
- `packages/workspaces/src/Filament/Resources/Workspaces/Tables/WorkspacesTable.php` — append tagged contributor actions after the existing new-tab preview action.
- `packages/workspaces/tests/Unit/WorkspaceTableActionContributorTest.php` — verifies the contract tag and table action merge without Peek.
- `packages/workspaces/tests/WorkspacesTestCase.php` — remove direct `FilamentPeekServiceProvider` registration.
- `tests/AbstractTestCase.php` — remove direct `FilamentPeekServiceProvider` registration once the root test harness no longer needs it.
- `packages/filament-peek/composer.json` — new optional package manifest.
- `packages/filament-peek/src/Providers/FilamentPeekServiceProvider.php` — register package metadata, translations, admin provider.
- `packages/filament-peek/src/Providers/AdminServiceProvider.php` — tag the admin panel extender and Workspaces action contributor.
- `packages/filament-peek/src/Filament/Extenders/FilamentPeekAdminPanelExtender.php` — registers `Pboivin\FilamentPeek\FilamentPeekPlugin` on the panel.
- `packages/filament-peek/src/Filament/Resources/Workspaces/Actions/WorkspacePeekPreviewAction.php` — action that dispatches Peek's iframe modal event with a Workspaces preview URL.
- `packages/filament-peek/src/Workspaces/WorkspacePeekPreviewActionContributor.php` — returns the modal preview action when required packages are installed.
- `packages/filament-peek/resources/lang/en/workspace.php` — labels for the modal preview action.
- `packages/filament-peek/tests/...` — focused unit and feature tests for provider registration, action contribution, and URL generation.
- `composer.json` — add package path autoload entries and move `pboivin/filament-peek` out of root/global require if possible.
- Companion admin repo: `vendor/capell-app/admin/composer.json` and `vendor/capell-app/admin/src/Providers/Filament/AdminPanelProvider.php` — remove hard Filament Peek dependency and direct plugin registration.

## Task 1: Add Workspaces Table Action Extension Point

**Files:**

- Create: `packages/workspaces/src/Contracts/WorkspaceTableActionContributor.php`
- Modify: `packages/workspaces/src/Filament/Resources/Workspaces/Tables/WorkspacesTable.php`
- Test: `packages/workspaces/tests/Unit/WorkspaceTableActionContributorTest.php`
- Modify: `packages/workspaces/tests/WorkspacesTestCase.php`

- [ ] **Step 1: Write the contract test**

Create `packages/workspaces/tests/Unit/WorkspaceTableActionContributorTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\Workspaces\Contracts\WorkspaceTableActionContributor;

it('defines the workspace table action contributor tag', function (): void {
    expect(WorkspaceTableActionContributor::TAG)
        ->toBe('capell.workspaces.table_action_contributors');
});
```

- [ ] **Step 2: Run the test and verify it fails**

Run:

```bash
vendor/bin/pest packages/workspaces/tests/Unit/WorkspaceTableActionContributorTest.php --no-coverage
```

Expected: fail because `Capell\Workspaces\Contracts\WorkspaceTableActionContributor` does not exist.

- [ ] **Step 3: Create the contract**

Create `packages/workspaces/src/Contracts/WorkspaceTableActionContributor.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Workspaces\Contracts;

interface WorkspaceTableActionContributor
{
    public const TAG = 'capell.workspaces.table_action_contributors';

    /**
     * @return array<int, object>
     */
    public function actions(): array;
}
```

- [ ] **Step 4: Make WorkspacesTable append contributor actions**

In `packages/workspaces/src/Filament/Resources/Workspaces/Tables/WorkspacesTable.php`, import the contract:

```php
use Capell\Workspaces\Contracts\WorkspaceTableActionContributor;
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

- [ ] **Step 5: Remove direct Peek provider from Workspaces tests**

In `packages/workspaces/tests/WorkspacesTestCase.php`, delete:

```php
use Pboivin\FilamentPeek\FilamentPeekServiceProvider;
```

Remove this provider from `getPackageProviders()`:

```php
            FilamentPeekServiceProvider::class,
```

- [ ] **Step 6: Run focused Workspaces tests**

Run:

```bash
vendor/bin/pest packages/workspaces/tests/Unit/WorkspaceTableActionContributorTest.php packages/workspaces/tests/Feature/Actions/GenerateWorkspacePreviewUrlActionTest.php --no-coverage
```

Expected: pass.

- [ ] **Step 7: Commit**

```bash
git add packages/workspaces/src/Contracts/WorkspaceTableActionContributor.php \
        packages/workspaces/src/Filament/Resources/Workspaces/Tables/WorkspacesTable.php \
        packages/workspaces/tests/Unit/WorkspaceTableActionContributorTest.php \
        packages/workspaces/tests/WorkspacesTestCase.php
git commit -m "feat(workspaces): add table action contributors"
```

## Task 2: Scaffold Optional Filament Peek Package

**Files:**

- Create: `packages/filament-peek/composer.json`
- Create: `packages/filament-peek/src/Providers/FilamentPeekServiceProvider.php`
- Create: `packages/filament-peek/src/Providers/AdminServiceProvider.php`
- Create: `packages/filament-peek/resources/lang/en/package.php`
- Create: `packages/filament-peek/tests/FilamentPeekTestCase.php`
- Modify: `composer.json`
- Modify: `tests/Pest.php`
- Test: `packages/filament-peek/tests/Unit/Providers/FilamentPeekServiceProviderTest.php`

- [ ] **Step 1: Write provider registration test**

Create `packages/filament-peek/tests/Unit/Providers/FilamentPeekServiceProviderTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\FilamentPeek\Providers\FilamentPeekServiceProvider;

it('registers the package with Capell Core', function (): void {
    expect(CapellCore::getPackage(FilamentPeekServiceProvider::$packageName)->name)
        ->toBe('capell-app/filament-peek');
});
```

- [ ] **Step 2: Create package manifest**

Create `packages/filament-peek/composer.json`:

```json
{
    "name": "capell-app/filament-peek",
    "description": "Optional Filament Peek iframe previews for Capell admin and Workspaces drafts",
    "keywords": ["capell", "filament", "preview", "workspaces"],
    "license": "proprietary",
    "require": {
        "php": "^8.2",
        "capell-app/admin": "*",
        "capell-app/frontend": "*",
        "capell-app/workspaces": "*",
        "pboivin/filament-peek": "^4.1"
    },
    "autoload": {
        "psr-4": {
            "Capell\\FilamentPeek\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Capell\\FilamentPeek\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Capell\\FilamentPeek\\Providers\\FilamentPeekServiceProvider"
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
"Capell\\FilamentPeek\\": "packages/filament-peek/src",
```

Add to `autoload-dev.psr-4`:

```json
"Capell\\FilamentPeek\\Tests\\": "packages/filament-peek/tests",
```

Keep `pboivin/filament-peek` in root `require` until Task 5 removes direct admin usage. This avoids breaking the current symlinked admin package mid-plan.

- [ ] **Step 4: Create package test case and Pest mapping**

Create `packages/filament-peek/tests/FilamentPeekTestCase.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\FilamentPeek\Tests;

use Capell\Core\Facades\CapellCore;
use Capell\FilamentPeek\Providers\FilamentPeekServiceProvider;
use Capell\Tests\AbstractTestCase;
use Override;

abstract class FilamentPeekTestCase extends AbstractTestCase
{
    protected function getPackageServiceName(): string
    {
        return 'capell-filament-peek';
    }

    /**
     * @return class-string[]
     */
    #[Override]
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            FilamentPeekServiceProvider::class,
        ];
    }

    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(FilamentPeekServiceProvider::$packageName);
    }
}
```

In `tests/Pest.php`, add the import:

```php
use Capell\FilamentPeek\Tests\FilamentPeekTestCase;
```

Add the Pest mapping near the other package mappings:

```php
pest()->extend(FilamentPeekTestCase::class)->in('../packages/filament-peek/tests');
```

- [ ] **Step 5: Create service providers and translation**

Create `packages/filament-peek/src/Providers/FilamentPeekServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\FilamentPeek\Providers;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Spatie\LaravelPackageTools\Package;

final class FilamentPeekServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-filament-peek';

    public static string $packageName = 'capell-app/filament-peek';

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
            description: fn (): string => __('capell-filament-peek::package.description'),
        );
    }
}
```

Create `packages/filament-peek/src/Providers/AdminServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\FilamentPeek\Providers;

use Illuminate\Support\ServiceProvider;

final class AdminServiceProvider extends ServiceProvider
{
    public function register(): void {}
}
```

Create `packages/filament-peek/resources/lang/en/package.php`:

```php
<?php

declare(strict_types=1);

return [
    'description' => 'Optional iframe previews for Capell admin using Filament Peek.',
];
```

- [ ] **Step 6: Run composer dump-autoload and provider test**

Run:

```bash
composer dump-autoload
vendor/bin/pest packages/filament-peek/tests/Unit/Providers/FilamentPeekServiceProviderTest.php --no-coverage
```

Expected: pass.

- [ ] **Step 7: Commit**

```bash
git add composer.json tests/Pest.php packages/filament-peek
git commit -m "feat(filament-peek): add optional package skeleton"
```

## Task 3: Register Peek Plugin Through Admin Extender

**Files:**

- Create: `packages/filament-peek/src/Filament/Extenders/FilamentPeekAdminPanelExtender.php`
- Modify: `packages/filament-peek/src/Providers/AdminServiceProvider.php`
- Test: `packages/filament-peek/tests/Unit/Filament/Extenders/FilamentPeekAdminPanelExtenderTest.php`

- [ ] **Step 1: Write extender test**

Create `packages/filament-peek/tests/Unit/Filament/Extenders/FilamentPeekAdminPanelExtenderTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\Admin\Contracts\Extenders\AdminPanelExtender;
use Capell\FilamentPeek\Filament\Extenders\FilamentPeekAdminPanelExtender;

it('implements the admin panel extender contract', function (): void {
    expect(FilamentPeekAdminPanelExtender::class)
        ->toImplement(AdminPanelExtender::class);
});

it('is tagged as an admin panel extender', function (): void {
    $extenders = collect(app()->tagged(AdminPanelExtender::TAG))
        ->map(fn (object $extender): string => $extender::class)
        ->all();

    expect($extenders)->toContain(FilamentPeekAdminPanelExtender::class);
});
```

- [ ] **Step 2: Implement extender**

Create `packages/filament-peek/src/Filament/Extenders/FilamentPeekAdminPanelExtender.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\FilamentPeek\Filament\Extenders;

use Capell\Admin\Contracts\Extenders\AdminPanelExtender;
use Filament\Panel;
use Pboivin\FilamentPeek\FilamentPeekPlugin;

final class FilamentPeekAdminPanelExtender implements AdminPanelExtender
{
    public function extend(Panel $panel): void
    {
        if ($panel->hasPlugin(FilamentPeekPlugin::ID)) {
            return;
        }

        $panel->plugin(FilamentPeekPlugin::make());
    }
}
```

Modify `packages/filament-peek/src/Providers/AdminServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\FilamentPeek\Providers;

use Capell\Admin\Contracts\Extenders\AdminPanelExtender;
use Capell\FilamentPeek\Filament\Extenders\FilamentPeekAdminPanelExtender;
use Illuminate\Support\ServiceProvider;

final class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag([FilamentPeekAdminPanelExtender::class], AdminPanelExtender::TAG);
    }
}
```

- [ ] **Step 3: Run extender tests**

Run:

```bash
vendor/bin/pest packages/filament-peek/tests/Unit/Filament/Extenders/FilamentPeekAdminPanelExtenderTest.php --no-coverage
```

Expected: pass.

- [ ] **Step 4: Commit**

```bash
git add packages/filament-peek/src/Filament/Extenders \
        packages/filament-peek/src/Providers/AdminServiceProvider.php \
        packages/filament-peek/tests/Unit/Filament/Extenders
git commit -m "feat(filament-peek): register peek panel plugin"
```

## Task 4: Add Workspaces Modal Preview Action

**Files:**

- Create: `packages/filament-peek/src/Filament/Resources/Workspaces/Actions/WorkspacePeekPreviewAction.php`
- Create: `packages/filament-peek/src/Workspaces/WorkspacePeekPreviewActionContributor.php`
- Modify: `packages/filament-peek/src/Providers/AdminServiceProvider.php`
- Create: `packages/filament-peek/resources/lang/en/workspace.php`
- Test: `packages/filament-peek/tests/Unit/Workspaces/WorkspacePeekPreviewActionContributorTest.php`
- Test: `packages/filament-peek/tests/Feature/Workspaces/WorkspacePeekPreviewActionTest.php`

- [ ] **Step 1: Write contributor test**

Create `packages/filament-peek/tests/Unit/Workspaces/WorkspacePeekPreviewActionContributorTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\FilamentPeek\Filament\Resources\Workspaces\Actions\WorkspacePeekPreviewAction;
use Capell\FilamentPeek\Workspaces\WorkspacePeekPreviewActionContributor;
use Capell\Workspaces\Contracts\WorkspaceTableActionContributor;

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

Create `packages/filament-peek/src/Workspaces/WorkspacePeekPreviewActionContributor.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\FilamentPeek\Workspaces;

use Capell\FilamentPeek\Filament\Resources\Workspaces\Actions\WorkspacePeekPreviewAction;
use Capell\Workspaces\Contracts\WorkspaceTableActionContributor;

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

Create `packages/filament-peek/resources/lang/en/workspace.php`:

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

Modify `packages/filament-peek/src/Providers/AdminServiceProvider.php` to tag the contributor:

```php
use Capell\FilamentPeek\Workspaces\WorkspacePeekPreviewActionContributor;
use Capell\Workspaces\Contracts\WorkspaceTableActionContributor;
```

Inside `register()`:

```php
        $this->app->tag([WorkspacePeekPreviewActionContributor::class], WorkspaceTableActionContributor::TAG);
```

- [ ] **Step 3: Implement modal action**

Create `packages/filament-peek/src/Filament/Resources/Workspaces/Actions/WorkspacePeekPreviewAction.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\FilamentPeek\Filament\Resources\Workspaces\Actions;

use Capell\Core\Facades\CapellCore;
use Capell\Workspaces\Actions\GenerateWorkspacePreviewUrlAction;
use Capell\Workspaces\Models\Workspace;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Override;
use Pboivin\FilamentPeek\Facades\Peek;

final class WorkspacePeekPreviewAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('capell-filament-peek::workspace.actions.preview_modal'))
            ->tooltip(__('capell-filament-peek::workspace.actions.preview_modal_tooltip'))
            ->icon(Heroicon::OutlinedComputerDesktop)
            ->color('gray')
            ->authorize('view')
            ->visible(fn (): bool => CapellCore::isPackageInstalled('capell-app/frontend'))
            ->action(function (Workspace $record): void {
                Peek::ensurePluginIsLoaded();

                $this->dispatch(
                    'open-preview-modal',
                    modalTitle: __('capell-filament-peek::workspace.actions.preview_modal_title'),
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

Create `packages/filament-peek/tests/Feature/Workspaces/WorkspacePeekPreviewActionTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\Workspaces\Http\Middleware\ResolveWorkspaceContext;
use Capell\Workspaces\Models\PreviewLink;
use Capell\Workspaces\Models\Workspace;

it('generates a workspace draft preview link for the iframe modal', function (): void {
    $workspace = Workspace::factory()->create();

    $url = (new Capell\Workspaces\Actions\GenerateWorkspacePreviewUrlAction)->handle($workspace);

    expect($url)
        ->toContain(ResolveWorkspaceContext::QUERY_PARAM . '=' . $workspace->uuid)
        ->toContain(ResolveWorkspaceContext::TOKEN_PARAM . '=');

    expect(PreviewLink::query()->where('workspace_id', $workspace->id)->exists())->toBeTrue();
});
```

- [ ] **Step 5: Run package action tests**

Run:

```bash
vendor/bin/pest packages/filament-peek/tests/Unit/Workspaces/WorkspacePeekPreviewActionContributorTest.php packages/filament-peek/tests/Feature/Workspaces/WorkspacePeekPreviewActionTest.php --no-coverage
```

Expected: pass.

- [ ] **Step 6: Run Workspaces table test again**

Run:

```bash
vendor/bin/pest packages/workspaces/tests/Unit/WorkspaceTableActionContributorTest.php packages/filament-peek/tests --no-coverage
```

Expected: pass.

- [ ] **Step 7: Commit**

```bash
git add packages/filament-peek/src packages/filament-peek/resources packages/filament-peek/tests
git commit -m "feat(filament-peek): add workspace modal preview"
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
use Pboivin\FilamentPeek\FilamentPeekServiceProvider;
```

Remove:

```php
            FilamentPeekServiceProvider::class,
```

- [ ] **Step 2: Remove direct admin panel plugin registration in companion admin package**

In `vendor/capell-app/admin/src/Providers/Filament/AdminPanelProvider.php`, delete:

```php
use Pboivin\FilamentPeek\FilamentPeekPlugin;
```

Remove:

```php
            ->plugin(FilamentPeekPlugin::make())
```

The panel should still register:

```php
            ->plugin(CapellAdminPlugin::make()
                ->discoverConfigurators(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources'))
```

- [ ] **Step 3: Move Composer dependency**

In root `composer.json`, keep `pboivin/filament-peek` only if the monorepo needs it to run package tests before Composer path resolution includes `packages/filament-peek`. If Composer accepts the local package dependency, remove this root require line:

```json
"pboivin/filament-peek": "^4.0",
```

In `vendor/capell-app/admin/composer.json`, remove:

```json
"pboivin/filament-peek": "^4.0",
```

Do not remove it from `packages/filament-peek/composer.json`.

- [ ] **Step 4: Refresh autoload**

Run:

```bash
composer dump-autoload
```

Expected: autoload completes without class resolution errors.

- [ ] **Step 5: Run focused dependency tests**

Run:

```bash
vendor/bin/pest packages/workspaces/tests/Unit/WorkspaceTableActionContributorTest.php packages/filament-peek/tests --no-coverage
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
vendor/bin/pest packages/workspaces/tests packages/filament-peek/tests --no-coverage
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
rg -n "Pboivin\\\\FilamentPeek|FilamentPeekPlugin|FilamentPeekServiceProvider|pboivin/filament-peek" composer.json tests packages vendor/capell-app/admin/src vendor/capell-app/admin/composer.json --glob '!vendor/pboivin'
```

Expected:

- `packages/filament-peek` references Peek classes and Composer dependency.
- no Workspaces source or tests import Peek classes.
- no root shared test harness imports `FilamentPeekServiceProvider`.
- companion admin panel provider no longer registers `FilamentPeekPlugin` directly.

- [ ] **Step 4: Final commit if verification required small fixes**

If verification required fixes, commit only files from this plan:

```bash
git status --short
git add composer.json tests/AbstractTestCase.php packages/workspaces packages/filament-peek docs/superpowers/plans/2026-04-30-filament-peek-package.md
git commit -m "fix(filament-peek): complete optional preview integration"
```
