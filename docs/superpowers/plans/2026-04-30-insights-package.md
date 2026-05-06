# Insights Package Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build `capell-app/insights` as a first-party insights package with consent-aware page views, journeys, click/action events, reporting, settings, and retention.

**Architecture:** Use a package-local service provider, Actions for domain behavior, Spatie Data objects at request and model boundaries, backed enums for persisted values, and thin controllers/render hooks. Version 1 stores first-party insights events and consent decisions in Capell tables, with optional future forwarding to GA4 left out of scope.

**Tech Stack:** Laravel 11/12 compatible package, PHP 8.2, Pest, Lorisleiva Actions, Spatie Laravel Data, Spatie Laravel Settings, Spatie Laravel Package Tools, Filament settings/widgets, Capell frontend render hooks.

---

## Ground Rules

- Work in `/Users/ben/Sites/packages/capell/capell-packages-4`.
- Preserve all existing dirty worktree changes. Only stage and commit files touched by the current task.
- Do not run `php artisan`; use `vendor/bin/pest` directly.
- Every PHP file must use `declare(strict_types=1);`.
- Closures must declare parameter and return types.
- Use descriptive variable names. Do not introduce single-letter variables.
- User-facing strings must use `__('capell-insights::...')`.
- When modifying root `composer.json`, read the current file first and merge only the insights namespace entries.

## File Structure

Create these package files unless a task states otherwise:

- `packages/insights/composer.json`: package metadata and provider discovery.
- `packages/insights/capell.json`: Capell package manifest.
- `packages/insights/config/capell-insights.php`: defaults for route prefix, tracking, consent, ignored paths/selectors, retention, hashing, and table names.
- `packages/insights/resources/lang/en/package.php`: package registry description.
- `packages/insights/resources/lang/en/settings.php`: settings labels and helper text.
- `packages/insights/resources/lang/en/consent.php`: consent modal copy and validation text.
- `packages/insights/resources/lang/en/widgets.php`: widget labels.
- `packages/insights/resources/views/tracker.blade.php`: script/bootstrap render hook view.
- `packages/insights/resources/js/capell-insights.js`: beacon tracker.
- `packages/insights/routes/web.php`: beacon and consent endpoints, with CSRF middleware excluded because `navigator.sendBeacon()` cannot reliably attach Laravel's CSRF token during unload events.
- `packages/insights/src/Providers/InsightsServiceProvider.php`: package registration, config, views, translations, migrations, routes, settings, models, protected tables, frontend hook.
- `packages/insights/src/Providers/AdminServiceProvider.php`: dashboard widgets, settings contributor, retention schedule, command registration.
- `packages/insights/src/Settings/InsightsSettings.php`: settings object.
- `packages/insights/src/Filament/Settings/InsightsSettingsSchema.php`: settings schema.
- `packages/insights/src/Filament/Settings/Contributors/InsightsDashboardSettingsContributor.php`: dashboard settings contributor.
- `packages/insights/src/Console/Commands/PurgeInsightsDataCommand.php`: retention command.
- `packages/insights/src/Enums/*.php`: event, consent category, consent region, consent status enums.
- `packages/insights/src/Data/*.php`: beacon, consent, event, visit, summary, journey, and window data.
- `packages/insights/src/Models/*.php`: `InsightsVisit`, `InsightsConsent`, `InsightsEvent`.
- `packages/insights/src/Actions/*.php`: consent, event recording, reporting, journey, and purge actions.
- `packages/insights/src/Http/Controllers/*.php`: beacon and consent controllers.
- `packages/insights/src/Support/RenderHooks/RegisterInsightsTrackerHook.php`: frontend BodyEnd render hook.
- `packages/insights/src/Support/Consent/ConsentRegionResolver.php`: server-side region resolver.
- `packages/insights/src/Filament/Widgets/*.php`: overview, popular, trending, journeys, and actions widgets.
- `packages/insights/database/migrations/*.php`: insights table migrations.
- `packages/insights/database/settings/create_insights_settings.php`: settings migration.
- `packages/insights/database/factories/*.php`: model factories.
- `packages/insights/tests/InsightsTestCase.php`: package test case.
- `packages/insights/tests/Pest.php`: Pest setup.
- `packages/insights/tests/Feature` and `packages/insights/tests/Unit`: focused tests.
- Modify `composer.json`: add `Capell\\Insights\\` and `Capell\\Insights\\Database\\Factories\\` autoload entries.

## Task 1: Package Skeleton And Registration

**Files:**

- Create: `packages/insights/composer.json`
- Create: `packages/insights/capell.json`
- Create: `packages/insights/config/capell-insights.php`
- Create: `packages/insights/resources/lang/en/package.php`
- Create: `packages/insights/resources/lang/en/settings.php`
- Create: `packages/insights/resources/lang/en/consent.php`
- Create: `packages/insights/resources/lang/en/widgets.php`
- Create: `packages/insights/src/Providers/InsightsServiceProvider.php`
- Create: `packages/insights/src/Providers/AdminServiceProvider.php`
- Create: `packages/insights/tests/InsightsTestCase.php`
- Create: `packages/insights/tests/Pest.php`
- Create: `packages/insights/tests/Unit/Providers/InsightsServiceProviderTest.php`
- Modify: `composer.json`

- [ ] **Step 1: Write provider smoke tests**

Create `packages/insights/tests/Pest.php`:

```php
<?php

declare(strict_types=1);

use Capell\Insights\Tests\InsightsTestCase;

uses(InsightsTestCase::class)->in(__DIR__);
```

Create `packages/insights/tests/InsightsTestCase.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Insights\Tests;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Insights\Providers\InsightsServiceProvider;
use Capell\Core\Facades\CapellCore;
use Capell\Frontend\Contracts\SettingsMigrationProviderInterface;
use Capell\Frontend\Providers\FrontendServiceProvider;
use Capell\Tests\AbstractTestCase;
use Illuminate\Foundation\Application;
use Livewire\LivewireServiceProvider;
use MichalOravec\PaginateRoute\PaginateRouteServiceProvider;
use Override;

class InsightsTestCase extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->registerAndMigrateSettings(
            CapellCore::getSettingMigrations(),
            __DIR__ . '/../../../vendor/capell-app/core/database/settings',
        );

        $this->registerAndMigrateSettings(
            CapellAdmin::getSettingMigrations(),
            __DIR__ . '/../../../vendor/capell-app/admin/database/settings',
        );

        if ($this->app->bound(SettingsMigrationProviderInterface::class)) {
            $this->registerAndMigrateSettings(
                resolve(SettingsMigrationProviderInterface::class)->getSettingMigrations(),
                __DIR__ . '/../../../vendor/capell-app/frontend/database/settings',
            );
        }
    }

    protected function getPackageServiceName(): string
    {
        return 'capell-insights';
    }

    /**
     * @param  Application  $app
     * @return class-string[]
     */
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            AdminServiceProvider::class,
            FrontendServiceProvider::class,
            PaginateRouteServiceProvider::class,
            LivewireServiceProvider::class,
            InsightsServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(AdminServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(FrontendServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(InsightsServiceProvider::$packageName);
    }
}
```

Create `packages/insights/tests/Unit/Providers/InsightsServiceProviderTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\Insights\Providers\InsightsServiceProvider;
use Capell\Core\Facades\CapellCore;
use Illuminate\Support\Facades\Route;

it('registers the insights package metadata', function (): void {
    $package = CapellCore::getPackage(InsightsServiceProvider::$packageName);

    expect($package->name)->toBe(InsightsServiceProvider::$packageName);
});

it('loads the insights config', function (): void {
    expect(config('capell-insights.enabled'))->toBeTrue()
        ->and(config('capell-insights.route_prefix'))->toBe('capell/insights');
});

it('registers insights routes', function (): void {
    expect(Route::has('capell-insights.events'))->toBeTrue()
        ->and(Route::has('capell-insights.consent'))->toBeTrue();
});
```

- [ ] **Step 2: Run the provider tests and verify they fail**

Run:

```bash
vendor/bin/pest packages/insights/tests/Unit/Providers/InsightsServiceProviderTest.php
```

Expected: FAIL because `Capell\Insights\Providers\InsightsServiceProvider` and package routes do not exist yet.

- [ ] **Step 3: Add package metadata, config, translations, routes, and providers**

Create `packages/insights/composer.json`:

```json
{
    "name": "capell-app/insights",
    "description": "First-party insights, visitor journeys, click tracking, and consent management for Capell CMS",
    "type": "library",
    "license": "proprietary",
    "require": {
        "php": "^8.2",
        "capell-app/admin": "*",
        "capell-app/core": "*",
        "capell-app/frontend": "*",
        "lorisleiva/laravel-actions": "^2.8",
        "spatie/laravel-data": "^4.5",
        "spatie/laravel-package-tools": "^1.14.1"
    },
    "require-dev": {
        "orchestra/testbench": "^9.0",
        "pestphp/pest": "^3.0|^4.1",
        "pestphp/pest-plugin-laravel": "^3.0|^4.0"
    },
    "autoload": {
        "psr-4": {
            "Capell\\Insights\\": "src/",
            "Capell\\Insights\\Database\\Factories\\": "database/factories"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Capell\\Insights\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Capell\\Insights\\Providers\\InsightsServiceProvider"
            ]
        }
    },
    "config": {
        "sort-packages": true
    },
    "prefer-stable": true
}
```

Create `packages/insights/capell.json`:

```json
{
    "name": "capell-app/insights",
    "description": "First-party insights, visitor journeys, click tracking, and consent management.",
    "providers": {
        "shared": ["Capell\\Insights\\Providers\\InsightsServiceProvider"],
        "admin": ["Capell\\Insights\\Providers\\AdminServiceProvider"]
    }
}
```

Create `packages/insights/config/capell-insights.php`:

```php
<?php

declare(strict_types=1);

return [
    'enabled' => true,
    'route_prefix' => 'capell/insights',
    'track_page_views' => true,
    'track_clicks' => true,
    'track_form-builder' => false,
    'automatic_click_tracking' => true,
    'require_consent_for_all_regions' => false,
    'default_consent_region' => null,
    'policy_version' => '1.0',
    'retention_days' => 365,
    'hash_visitor_data' => true,
    'hash_salt' => env('APP_KEY', 'capell-insights'),
    'ignored_paths' => [
        '/admin*',
        '/livewire*',
        '/capell/insights*',
    ],
    'ignored_selectors' => [
        '[data-capell-insights-ignore]',
        '[wire\\:click]',
    ],
    'tables' => [
        'visits' => 'insights_visits',
        'consents' => 'insights_consents',
        'events' => 'insights_events',
    ],
];
```

Create `packages/insights/resources/lang/en/package.php`:

```php
<?php

declare(strict_types=1);

return [
    'description' => 'Adds first-party insights, visitor journeys, click tracking, and consent management.',
];
```

Create minimal translation files now. Later tasks can add keys as UI grows:

```php
<?php

declare(strict_types=1);

return [];
```

Use that content for:

- `packages/insights/resources/lang/en/settings.php`
- `packages/insights/resources/lang/en/consent.php`
- `packages/insights/resources/lang/en/widgets.php`

Create `packages/insights/routes/web.php`:

```php
<?php

declare(strict_types=1);

use Capell\Insights\Http\Controllers\InsightsBeaconController;
use Capell\Insights\Http\Controllers\InsightsConsentController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

$routePrefix = trim((string) config('capell-insights.route_prefix', 'capell/insights'), '/');

Route::prefix($routePrefix)
    ->middleware(['web'])
    ->group(function (): void {
        Route::post('events', InsightsBeaconController::class)
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->name('capell-insights.events');

        Route::post('consent', InsightsConsentController::class)
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->name('capell-insights.consent');
    });
```

Create temporary no-content controllers so route registration can be tested before the event and consent actions exist:

```php
<?php

declare(strict_types=1);

namespace Capell\Insights\Http\Controllers;

use Illuminate\Http\Response;

final class InsightsBeaconController
{
    public function __invoke(): Response
    {
        return response()->noContent();
    }
}
```

Use the same body for `InsightsConsentController`, changing only the class name.

Create `packages/insights/src/Providers/AdminServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Insights\Providers;

use Illuminate\Support\ServiceProvider;

final class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
    }
}
```

Create `packages/insights/src/Providers/InsightsServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Insights\Providers;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Spatie\LaravelPackageTools\Package;

final class InsightsServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-insights';

    public static string $packageName = 'capell-app/insights';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile('capell-insights')
            ->hasTranslations()
            ->hasViews()
            ->hasRoute('web');
    }

    public function registeringPackage(): void
    {
        $this->app->register(AdminServiceProvider::class);
    }

    public function packageRegistered(): void
    {
        $this->registerPackageMetadata();
    }

    private function registerPackageMetadata(): self
    {
        CapellCore::registerPackage(
            self::$packageName,
            type: self::getType(),
            serviceProviderClass: self::class,
            path: realpath(__DIR__ . '/../..'),
            version: CapellCore::getInstalledPrettyVersion(self::$packageName),
            description: fn (): string => __('capell-insights::package.description'),
        );

        return $this;
    }
}
```

- [ ] **Step 4: Add root Composer autoload entries**

Modify root `composer.json` only in `autoload.psr-4` and `autoload-dev.psr-4`:

```json
"Capell\\Insights\\": "packages/insights/src",
"Capell\\Insights\\Database\\Factories\\": "packages/insights/database/factories",
```

and:

```json
"Capell\\Insights\\Tests\\": "packages/insights/tests",
```

Run:

```bash
composer dump-autoload
```

Expected: composer regenerates autoload files without package discovery errors.

- [ ] **Step 5: Run provider tests**

Run:

```bash
vendor/bin/pest packages/insights/tests/Unit/Providers/InsightsServiceProviderTest.php
```

Expected: PASS.

- [ ] **Step 6: Commit the skeleton**

Run:

```bash
git add composer.json packages/insights
git commit -m "feat: add insights package skeleton" -- composer.json packages/insights
```

## Task 2: Database Models, Enums, Data Objects, And Settings

**Files:**

- Create: `packages/insights/src/Enums/InsightsEventType.php`
- Create: `packages/insights/src/Enums/InsightsConsentCategory.php`
- Create: `packages/insights/src/Enums/InsightsConsentRegion.php`
- Create: `packages/insights/src/Enums/InsightsConsentStatus.php`
- Create: `packages/insights/src/Data/InsightsConsentData.php`
- Create: `packages/insights/src/Data/InsightsEventData.php`
- Create: `packages/insights/src/Data/InsightsBeaconData.php`
- Create: `packages/insights/src/Data/InsightsVisitData.php`
- Create: `packages/insights/src/Data/InsightsPageSummaryData.php`
- Create: `packages/insights/src/Data/InsightsJourneyStepData.php`
- Create: `packages/insights/src/Data/InsightsWindowData.php`
- Create: `packages/insights/src/Models/InsightsVisit.php`
- Create: `packages/insights/src/Models/InsightsConsent.php`
- Create: `packages/insights/src/Models/InsightsEvent.php`
- Create: `packages/insights/database/migrations/create_insights_visits_table.php`
- Create: `packages/insights/database/migrations/create_insights_consents_table.php`
- Create: `packages/insights/database/migrations/create_insights_events_table.php`
- Create: `packages/insights/database/settings/create_insights_settings.php`
- Create: `packages/insights/src/Settings/InsightsSettings.php`
- Create: `packages/insights/src/Filament/Settings/InsightsSettingsSchema.php`
- Modify: `packages/insights/src/Providers/InsightsServiceProvider.php`
- Create: `packages/insights/tests/Feature/Database/InsightsMigrationsTest.php`
- Create: `packages/insights/tests/Unit/Data/InsightsDataTest.php`
- Create: `packages/insights/tests/Feature/Settings/InsightsSettingsTest.php`

- [ ] **Step 1: Write failing migration, enum, data, and settings tests**

Create tests asserting:

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

it('loads insights migrations', function (): void {
    expect(Schema::hasTable('insights_visits'))->toBeTrue()
        ->and(Schema::hasTable('insights_consents'))->toBeTrue()
        ->and(Schema::hasTable('insights_events'))->toBeTrue()
        ->and(Schema::hasColumn('insights_visits', 'uuid'))->toBeTrue()
        ->and(Schema::hasColumn('insights_consents', 'categories'))->toBeTrue()
        ->and(Schema::hasColumn('insights_events', 'document_y'))->toBeTrue();
});
```

```php
<?php

declare(strict_types=1);

use Capell\Insights\Data\InsightsConsentData;
use Capell\Insights\Data\InsightsEventData;
use Capell\Insights\Enums\InsightsConsentCategory;
use Capell\Insights\Enums\InsightsEventType;

it('serializes consent categories as data', function (): void {
    $data = InsightsConsentData::from([
        'essential' => true,
        'insights' => true,
        'marketing' => false,
        'preferences' => false,
    ]);

    expect($data->enabledCategories())->toBe([
        InsightsConsentCategory::Essential,
        InsightsConsentCategory::Insights,
    ]);
});

it('normalizes event data', function (): void {
    $data = InsightsEventData::from([
        'type' => 'click',
        'url' => 'https://example.test/path?token=secret',
        'title' => 'Example',
        'event_name' => 'cta_click',
        'label' => 'Book a demo',
        'location' => 'home.hero',
        'target_selector' => 'button[data-capell-insights]',
        'viewport_x' => 10,
        'viewport_y' => 20,
        'document_x' => 10,
        'document_y' => 520,
        'metadata' => ['nearest_landmark' => 'main'],
    ]);

    expect($data->type)->toBe(InsightsEventType::Click)
        ->and($data->path())->toBe('/path');
});
```

```php
<?php

declare(strict_types=1);

use Capell\Insights\Settings\InsightsSettings;
use Spatie\LaravelSettings\Migrations\SettingsMigrationAssistant;

it('loads insights settings defaults', function (): void {
    /** @var SettingsMigrationAssistant $settingsMigrationAssistant */
    $settingsMigrationAssistant = app(SettingsMigrationAssistant::class);

    expect($settingsMigrationAssistant->exists('insights.enabled'))->toBeTrue()
        ->and(app(InsightsSettings::class)->retention_days)->toBe(365);
});
```

- [ ] **Step 2: Run tests and verify they fail**

Run:

```bash
vendor/bin/pest packages/insights/tests/Feature/Database/InsightsMigrationsTest.php packages/insights/tests/Unit/Data/InsightsDataTest.php packages/insights/tests/Feature/Settings/InsightsSettingsTest.php
```

Expected: FAIL because migrations, enums, data, models, and settings do not exist.

- [ ] **Step 3: Implement enums and data objects**

Use string-backed enums with `HasLabel` when labels appear in Filament settings. Event cases:

```php
enum InsightsEventType: string
{
    case PageView = 'page_view';
    case Click = 'click';
    case Form = 'form';
    case Custom = 'custom';
    case Consent = 'consent';
}
```

Consent category cases:

```php
enum InsightsConsentCategory: string
{
    case Essential = 'essential';
    case Insights = 'insights';
    case Marketing = 'marketing';
    case Preferences = 'preferences';
}
```

Consent region cases: `UkOrEurope`, `OutsideUkOrEurope`, `Unknown`.

Consent status cases: `Pending`, `AcceptedAll`, `RejectedNonEssential`, `Granular`.

Implement data classes with `Spatie\LaravelData\Data`. `InsightsConsentData` must expose:

```php
/** @return list<InsightsConsentCategory> */
public function enabledCategories(): array
{
    $categories = [InsightsConsentCategory::Essential];

    if ($this->insights) {
        $categories[] = InsightsConsentCategory::Insights;
    }

    if ($this->marketing) {
        $categories[] = InsightsConsentCategory::Marketing;
    }

    if ($this->preferences) {
        $categories[] = InsightsConsentCategory::Preferences;
    }

    return $categories;
}
```

`InsightsEventData::path()` must parse the URL with `parse_url($this->url, PHP_URL_PATH)` and return `'/'` for blank paths.

- [ ] **Step 4: Implement migrations, models, factories, and service provider registration**

Add migrations exactly matching the spec columns. Use config table names:

```php
$tableName = config('capell-insights.tables.visits', 'insights_visits');
```

Use foreign keys between events/consents and visits with nullable `visit_id` and `nullOnDelete()`.

Model casts:

```php
protected $casts = [
    'started_at' => 'immutable_datetime',
    'last_seen_at' => 'immutable_datetime',
];
```

For JSON data columns use Spatie data casts:

```php
use Spatie\LaravelData\Casts\AsData;

protected $casts = [
    'categories' => AsData::class . ':' . InsightsConsentData::class,
    'terms_accepted_at' => 'immutable_datetime',
    'decided_at' => 'immutable_datetime',
];
```

Register in `InsightsServiceProvider`:

```php
->hasMigrations([
    'create_insights_visits_table',
    'create_insights_consents_table',
    'create_insights_events_table',
])
```

Add `registerModels()`, `registerSettings()`, and `registerProtectedTables()` chain methods following `LoginAuditServiceProvider`.

- [ ] **Step 5: Implement settings and settings schema**

`InsightsSettings` properties:

```php
public bool $enabled = true;
public bool $track_page_views = true;
public bool $track_clicks = true;
public bool $track_form-builder = false;
public bool $automatic_click_tracking = true;
public bool $require_consent_for_all_regions = false;
public ?string $default_consent_region = null;
public string $policy_version = '1.0';
public int $retention_days = 365;
public bool $hash_visitor_data = true;
public string $hash_salt = 'capell-insights';
/** @var list<string> */
public array $ignored_paths = ['/admin*', '/livewire*', '/capell/insights*'];
/** @var list<string> */
public array $ignored_selectors = ['[data-capell-insights-ignore]', '[wire\\:click]'];
public string $route_prefix = 'capell/insights';
```

Settings migration adds all keys under `insights.*` using `exists()` checks.

Settings schema uses toggles, text inputs, and textarea fields with translation keys from `capell-insights::settings`.

- [ ] **Step 6: Run focused tests**

Run:

```bash
vendor/bin/pest packages/insights/tests/Feature/Database/InsightsMigrationsTest.php packages/insights/tests/Unit/Data/InsightsDataTest.php packages/insights/tests/Feature/Settings/InsightsSettingsTest.php
```

Expected: PASS.

- [ ] **Step 7: Commit the data layer**

Run:

```bash
git add packages/insights
git commit -m "feat: add insights data model" -- packages/insights
```

## Task 3: Consent Region Resolution And Consent Recording

**Files:**

- Create: `packages/insights/src/Support/Consent/ConsentRegionResolver.php`
- Create: `packages/insights/src/Actions/ResolveConsentRegionAction.php`
- Create: `packages/insights/src/Actions/CreateInsightsVisitAction.php`
- Create: `packages/insights/src/Actions/UpdateInsightsConsentAction.php`
- Modify: `packages/insights/src/Http/Controllers/InsightsConsentController.php`
- Create: `packages/insights/tests/Unit/Actions/ResolveConsentRegionActionTest.php`
- Create: `packages/insights/tests/Feature/Consent/InsightsConsentControllerTest.php`

- [ ] **Step 1: Write failing consent tests**

Test cases:

- forced config `uk_or_europe` returns `InsightsConsentRegion::UkOrEurope`
- forced config `outside_uk_or_europe` returns `InsightsConsentRegion::OutsideUkOrEurope`
- invalid or missing location returns `InsightsConsentRegion::Unknown`
- granular consent without `terms_accepted` returns HTTP 422
- UK/Europe granular consent with terms stores consent categories and visit row
- reject non-essential stores essential-only categories

Use POST route:

```php
$this->postJson(route('capell-insights.consent'), [
    'region' => 'uk_or_europe',
    'status' => 'granular',
    'terms_accepted' => true,
    'categories' => [
        'insights' => true,
        'marketing' => false,
        'preferences' => false,
    ],
]);
```

- [ ] **Step 2: Run tests and verify they fail**

Run:

```bash
vendor/bin/pest packages/insights/tests/Unit/Actions/ResolveConsentRegionActionTest.php packages/insights/tests/Feature/Consent/InsightsConsentControllerTest.php
```

Expected: FAIL because actions and controller behavior do not exist.

- [ ] **Step 3: Implement region resolver**

`ConsentRegionResolver` should:

- return configured `capell-insights.default_consent_region` when set to a valid enum value
- return `Unknown` if `geoip()` helper or service is unavailable
- when a location object/array has ISO code in UK/EU list, return `UkOrEurope`
- when ISO code exists outside the list, return `OutsideUkOrEurope`

Use a constant list of ISO codes for UK/Europe:

```php
private const UK_AND_EUROPE_COUNTRY_CODES = [
    'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE',
    'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT',
    'RO', 'SK', 'SI', 'ES', 'SE', 'GB', 'UK', 'IS', 'LI', 'NO', 'CH',
];
```

- [ ] **Step 4: Implement visit creation and consent update actions**

`CreateInsightsVisitAction::handle(Request $request, InsightsConsentRegion $region): InsightsVisit` should:

- generate UUID
- set landing URL, referrer, UTM values, region, pending status, started/last seen
- set IP and user agent hashes only when `hash_visitor_data` is true

`UpdateInsightsConsentAction::handle(Request $request, InsightsConsentData $data, InsightsConsentStatus $status, InsightsConsentRegion $region): InsightsConsent` should:

- require terms acceptance for `Granular`
- create or reuse visit by `capell_insights_visit` cookie when present
- store consent row
- update visit consent status and region
- queue cookie with visit UUID for one year

- [ ] **Step 5: Implement consent controller**

Controller validation:

```php
$validated = $request->validate([
    'region' => ['required', Rule::enum(InsightsConsentRegion::class)],
    'status' => ['required', Rule::enum(InsightsConsentStatus::class)],
    'terms_accepted' => ['boolean'],
    'categories.insights' => ['boolean'],
    'categories.marketing' => ['boolean'],
    'categories.preferences' => ['boolean'],
]);
```

For `accepted_all`, set all categories true. For `rejected_non_essential`, set all non-essential categories false. Return JSON with the visit UUID and enabled categories.

- [ ] **Step 6: Run consent tests**

Run:

```bash
vendor/bin/pest packages/insights/tests/Unit/Actions/ResolveConsentRegionActionTest.php packages/insights/tests/Feature/Consent/InsightsConsentControllerTest.php
```

Expected: PASS.

- [ ] **Step 7: Commit consent behavior**

Run:

```bash
git add packages/insights
git commit -m "feat: add insights consent tracking" -- packages/insights
```

## Task 4: Beacon Event Recording

**Files:**

- Create: `packages/insights/src/Actions/RecordInsightsEventAction.php`
- Create: `packages/insights/src/Actions/RecordPageViewAction.php`
- Create: `packages/insights/src/Actions/RecordClickAction.php`
- Create: `packages/insights/src/Actions/RecordCustomActionAction.php`
- Modify: `packages/insights/src/Http/Controllers/InsightsBeaconController.php`
- Create: `packages/insights/tests/Feature/Events/InsightsBeaconControllerTest.php`
- Create: `packages/insights/tests/Unit/Actions/RecordInsightsEventActionTest.php`

- [ ] **Step 1: Write failing event tests**

Cover:

- UK/Europe event without insights consent is not stored
- page view after insights consent is stored
- outside-region page view stores with default settings
- click stores location fields
- events on ignored paths are skipped
- invalid event type returns 422
- success returns 204
- POST requests without CSRF token do not return 419, matching the existing frontend toolbar beacon route pattern

Use route `capell-insights.events` with payload:

```php
[
    'visit_id' => $visit->uuid,
    'events' => [
        [
            'type' => 'click',
            'url' => 'https://example.test/',
            'title' => 'Home',
            'occurred_at' => now()->toIso8601String(),
            'event_name' => 'cta_click',
            'label' => 'Book a demo',
            'location' => 'home.hero',
            'target_selector' => 'button[data-capell-insights]',
            'viewport_x' => 24,
            'viewport_y' => 50,
            'document_x' => 24,
            'document_y' => 650,
            'metadata' => ['nearest_landmark' => 'main'],
        ],
    ],
]
```

- [ ] **Step 2: Run event tests and verify they fail**

Run:

```bash
vendor/bin/pest packages/insights/tests/Feature/Events/InsightsBeaconControllerTest.php packages/insights/tests/Unit/Actions/RecordInsightsEventActionTest.php
```

Expected: FAIL because event actions are not implemented.

- [ ] **Step 3: Implement event recording gate**

`RecordInsightsEventAction` should:

- resolve visit by UUID
- skip if package disabled
- skip ignored paths using `Str::is($ignoredPath, $path)`
- skip non-essential events for `UkOrEurope` or `Unknown` unless visit has insights consent
- allow outside-region events unless `require_consent_for_all_regions` is true
- assign next sequence using current max sequence for the visit
- create `InsightsEvent`

Consent check:

```php
$hasInsightsConsent = $visit->consent_status === InsightsConsentStatus::AcceptedAll
    || $visit->consents()
        ->latest('decided_at')
        ->get()
        ->contains(fn (InsightsConsent $consent): bool => $consent->categories->insights);
```

- [ ] **Step 4: Implement page, click, and custom wrappers**

`RecordPageViewAction`, `RecordClickAction`, and `RecordCustomActionAction` should accept an `InsightsEventData` and delegate to `RecordInsightsEventAction`, after checking type-specific requirements:

- page view must have URL
- click must have URL and one of `target_selector`, `label`, or `location`
- custom must have `event_name`

- [ ] **Step 5: Implement beacon controller**

Validation:

```php
$validated = $request->validate([
    'visit_id' => ['nullable', 'string', 'max:80'],
    'events' => ['required', 'array', 'max:25'],
    'events.*.type' => ['required', Rule::enum(InsightsEventType::class)],
    'events.*.url' => ['required', 'url', 'max:2048'],
    'events.*.title' => ['nullable', 'string', 'max:255'],
    'events.*.occurred_at' => ['nullable', 'date'],
    'events.*.event_name' => ['nullable', 'string', 'max:100'],
    'events.*.label' => ['nullable', 'string', 'max:255'],
    'events.*.location' => ['nullable', 'string', 'max:255'],
    'events.*.target_selector' => ['nullable', 'string', 'max:500'],
    'events.*.viewport_x' => ['nullable', 'integer'],
    'events.*.viewport_y' => ['nullable', 'integer'],
    'events.*.document_x' => ['nullable', 'integer'],
    'events.*.document_y' => ['nullable', 'integer'],
    'events.*.metadata' => ['nullable', 'array'],
]);
```

Dispatch by enum type. Return `response()->noContent()`.

- [ ] **Step 6: Run event tests**

Run:

```bash
vendor/bin/pest packages/insights/tests/Feature/Events/InsightsBeaconControllerTest.php packages/insights/tests/Unit/Actions/RecordInsightsEventActionTest.php
```

Expected: PASS.

- [ ] **Step 7: Commit beacon event recording**

Run:

```bash
git add packages/insights
git commit -m "feat: record insights beacon events" -- packages/insights
```

## Task 5: Frontend Tracker And Render Hook

**Files:**

- Create: `packages/insights/resources/views/tracker.blade.php`
- Create: `packages/insights/resources/js/capell-insights.js`
- Create: `packages/insights/src/Support/RenderHooks/RegisterInsightsTrackerHook.php`
- Modify: `packages/insights/src/Providers/InsightsServiceProvider.php`
- Create: `packages/insights/tests/Feature/Frontend/InsightsRenderHookTest.php`
- Create: `packages/insights/tests/Unit/Frontend/InsightsScriptTest.php`

- [ ] **Step 1: Write failing frontend tests**

Test render hook registration by resolving `RenderHookRegistry`, rendering `BodyEnd`, and asserting output includes:

- `data-capell-insights-tracker`
- route URLs for `capell-insights.events` and `capell-insights.consent`
- ignored selectors JSON

Test JavaScript source contains:

- `navigator.sendBeacon`
- `keepalive: true`
- `data-capell-insights-ignore`
- `data-capell-insights-label`
- `data-capell-insights-location`

- [ ] **Step 2: Run frontend tests and verify they fail**

Run:

```bash
vendor/bin/pest packages/insights/tests/Feature/Frontend/InsightsRenderHookTest.php packages/insights/tests/Unit/Frontend/InsightsScriptTest.php
```

Expected: FAIL because render hook and JS do not exist.

- [ ] **Step 3: Implement tracker Blade view**

`tracker.blade.php` should render one script tag with JSON config and one inline script that loads the JS source from the package view:

```blade
@php
    $insightsConfig = [
        'eventsUrl' => route('capell-insights.events'),
        'consentUrl' => route('capell-insights.consent'),
        'trackPageViews' => (bool) config('capell-insights.track_page_views', true),
        'trackClicks' => (bool) config('capell-insights.track_clicks', true),
        'automaticClickTracking' => (bool) config('capell-insights.automatic_click_tracking', true),
        'ignoredSelectors' => config('capell-insights.ignored_selectors', []),
        'policyVersion' => config('capell-insights.policy_version', '1.0'),
    ];
@endphp

<script
    type="application/json"
    data-capell-insights-tracker
>
    {!! json_encode($insightsConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) !!}
</script>
<script>
    {!! file_get_contents(__DIR__ . '/../js/capell-insights.js') !!}
</script>
```

- [ ] **Step 4: Implement JavaScript tracker**

The script must:

- read config from `[data-capell-insights-tracker]`
- send payloads using `navigator.sendBeacon(url, blob)`
- fall back to `fetch(url, { method: 'POST', body: json, headers: { 'Content-Type': 'application/json' }, keepalive: true })`
- avoid depending on CSRF headers because package routes explicitly exclude CSRF middleware for beacon compatibility
- send a `page_view` event on load when enabled
- listen for document clicks
- skip ignored selectors
- read explicit tracking from `data-capell-insights`, `data-capell-insights-label`, and `data-capell-insights-location`
- auto-track anchors/buttons/submits only when enabled
- calculate viewport and document coordinates

Use no external JS dependencies.

- [ ] **Step 5: Register BodyEnd render hook**

Create `RegisterInsightsTrackerHook` following `RegisterSeoHeadHooks` and `PublishingStudioServiceProvider::registerFrontendRenderHooks()`:

```php
$this->registry->register(
    RenderHookLocation::BodyEnd,
    static fn (): string => view('capell-insights::tracker')->render(),
);
```

Call it from `InsightsServiceProvider::packageBooted()` only when package enabled and `RenderHookRegistry` is bound.

- [ ] **Step 6: Run frontend tests**

Run:

```bash
vendor/bin/pest packages/insights/tests/Feature/Frontend/InsightsRenderHookTest.php packages/insights/tests/Unit/Frontend/InsightsScriptTest.php
```

Expected: PASS.

- [ ] **Step 7: Commit frontend tracker**

Run:

```bash
git add packages/insights
git commit -m "feat: inject insights tracker" -- packages/insights
```

## Task 6: Reporting, Journeys, Admin Widgets, And Retention

**Files:**

- Create: `packages/insights/src/Actions/BuildPopularPagesQueryAction.php`
- Create: `packages/insights/src/Actions/BuildTrendingPagesQueryAction.php`
- Create: `packages/insights/src/Actions/BuildJourneyTimelineAction.php`
- Create: `packages/insights/src/Actions/PurgeInsightsDataAction.php`
- Create: `packages/insights/src/Console/Commands/PurgeInsightsDataCommand.php`
- Create: `packages/insights/src/Filament/Widgets/InsightsOverviewStatsWidget.php`
- Create: `packages/insights/src/Filament/Widgets/PopularPagesWidget.php`
- Create: `packages/insights/src/Filament/Widgets/TrendingPagesWidget.php`
- Create: `packages/insights/src/Filament/Widgets/RecentJourneysWidget.php`
- Create: `packages/insights/src/Filament/Widgets/TopActionsWidget.php`
- Create: `packages/insights/src/Filament/Settings/Contributors/InsightsDashboardSettingsContributor.php`
- Modify: `packages/insights/src/Providers/AdminServiceProvider.php`
- Create: `packages/insights/tests/Feature/DashboardReports/InsightsDashboardReportsTest.php`
- Create: `packages/insights/tests/Feature/Retention/PurgeInsightsDataActionTest.php`
- Create: `packages/insights/tests/Feature/Filament/InsightsWidgetsTest.php`

- [ ] **Step 1: Write failing reporting and retention tests**

Seed insights events with current and previous windows. Assert:

- popular pages sort by page view count descending
- trending pages compare current window to previous equivalent window
- journey timeline is ordered by sequence and includes time since previous step
- purge deletes events older than retention
- widgets instantiate without throwing

- [ ] **Step 2: Run tests and verify they fail**

Run:

```bash
vendor/bin/pest packages/insights/tests/Feature/DashboardReports/InsightsDashboardReportsTest.php packages/insights/tests/Feature/Retention/PurgeInsightsDataActionTest.php packages/insights/tests/Feature/Filament/InsightsWidgetsTest.php
```

Expected: FAIL because reporting, widgets, and retention are not implemented.

- [ ] **Step 3: Implement report actions**

`BuildPopularPagesQueryAction::handle(InsightsWindowData $window): Collection` should query `InsightsEvent` where type is `PageView` and `occurred_at` is inside the window, grouped by `path`, selecting:

- `path`
- `url`
- `page_views`
- `unique_visits`
- `clicks`

`BuildTrendingPagesQueryAction::handle(InsightsWindowData $window): Collection` should calculate previous window from the current duration and return `InsightsPageSummaryData` rows with current count, previous count, absolute change, and percentage change.

`BuildJourneyTimelineAction::handle(InsightsVisit $visit): Collection` should order by sequence and map to `InsightsJourneyStepData`.

- [ ] **Step 4: Implement purge action and command**

`PurgeInsightsDataAction::handle(?int $retentionDays = null): int` should delete records older than cutoff from events, consents, and visits. Delete events first, consents second, visits last.

Command signature:

```php
protected $signature = 'insights:purge {--days= : Override insights retention days}';
```

Command output:

```php
$this->info("Purged {$deletedRecords} insights records.");
```

- [ ] **Step 5: Implement admin widgets and provider registration**

Register widgets in `AdminServiceProvider`:

```php
CapellAdmin::registerDashboardWidget(InsightsOverviewStatsWidget::class, DashboardEnum::Main);
CapellAdmin::registerDashboardWidget(PopularPagesWidget::class, DashboardEnum::Main);
CapellAdmin::registerDashboardWidget(TrendingPagesWidget::class, DashboardEnum::Main);
CapellAdmin::registerDashboardWidget(RecentJourneysWidget::class, DashboardEnum::Main);
CapellAdmin::registerDashboardWidget(TopActionsWidget::class, DashboardEnum::Main);
```

Register schedule:

```php
$this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
    $schedule->command('insights:purge')->monthly();
});
```

Widgets can be minimal version 1 Filament widgets that expose headings and call report actions. Keep all labels translated.

- [ ] **Step 6: Run reporting/admin tests**

Run:

```bash
vendor/bin/pest packages/insights/tests/Feature/DashboardReports/InsightsDashboardReportsTest.php packages/insights/tests/Feature/Retention/PurgeInsightsDataActionTest.php packages/insights/tests/Feature/Filament/InsightsWidgetsTest.php
```

Expected: PASS.

- [ ] **Step 7: Commit reporting and admin**

Run:

```bash
git add packages/insights
git commit -m "feat: add insights reporting widgets" -- packages/insights
```

## Task 7: Integration Verification And Polish

**Files:**

- Modify only files required by failing tests or static analysis.
- Update: `packages/insights/README.md` if absent.

- [ ] **Step 1: Add README**

Create `packages/insights/README.md` with:

````markdown
# Capell Insights

First-party insights, visitor journeys, click tracking, and consent management for Capell CMS.

## Testing

Run:

```bash
vendor/bin/pest packages/insights/tests
```
````

````

- [ ] **Step 2: Run package test suite**

Run:

```bash
vendor/bin/pest packages/insights/tests
````

Expected: PASS.

- [ ] **Step 3: Run affected package tests**

Run:

```bash
vendor/bin/pest packages/insights/tests packages/theme-studio/themes-core/tests
```

Expected: PASS or only unrelated existing failures. If there are failures, inspect and fix only insights-caused failures.

- [ ] **Step 4: Run format/lint for touched files**

Run:

```bash
composer lint -- packages/insights
```

If the project script does not accept a path argument, run:

```bash
vendor/bin/pint packages/insights
```

Expected: no formatting errors after Pint completes.

- [ ] **Step 5: Run static analysis for touched package if practical**

Run:

```bash
composer analyze
```

Expected: PASS or unrelated existing failures. Record any unrelated failures in the final summary.

- [ ] **Step 6: Commit final polish**

Run:

```bash
git add packages/insights composer.json
git commit -m "chore: verify insights package" -- packages/insights composer.json
```

Skip this commit if there are no file changes after verification.

## Self-Review Checklist

- Spec coverage: skeleton, consent, unknown-region handling, beacon events, click location, popular pages, trending pages, journeys, settings, admin widgets, retention, and tests are each mapped to tasks.
- Placeholder scan: no task contains `TBD`, `TODO`, `fill in details`, or unscoped "add tests" language.
- Type consistency: enum names, settings names, table names, route names, and action names match the design spec.
- Scope control: GA4 forwarding, heatmaps, replay, A/B testing, and personal profiling remain out of version 1.
