# Analytics Package Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build `capell-app/analytics` as a first-party analytics package with consent-aware page views, journeys, click/action events, reporting, settings, and retention.

**Architecture:** Use a package-local service provider, Actions for domain behavior, Spatie Data objects at request and model boundaries, backed enums for persisted values, and thin controllers/render hooks. Version 1 stores first-party analytics events and consent decisions in Capell tables, with optional future forwarding to GA4 left out of scope.

**Tech Stack:** Laravel 11/12 compatible package, PHP 8.2, Pest, Lorisleiva Actions, Spatie Laravel Data, Spatie Laravel Settings, Spatie Laravel Package Tools, Filament settings/widgets, Capell frontend render hooks.

---

## Ground Rules

- Work in `/Users/ben/Sites/packages/capell/capell-packages-4`.
- Preserve all existing dirty worktree changes. Only stage and commit files touched by the current task.
- Do not run `php artisan`; use `vendor/bin/pest` directly.
- Every PHP file must use `declare(strict_types=1);`.
- Closures must declare parameter and return types.
- Use descriptive variable names. Do not introduce single-letter variables.
- User-facing strings must use `__('capell-analytics::...')`.
- When modifying root `composer.json`, read the current file first and merge only the analytics namespace entries.

## File Structure

Create these package files unless a task states otherwise:

- `packages/analytics/composer.json`: package metadata and provider discovery.
- `packages/analytics/capell.json`: Capell package manifest.
- `packages/analytics/config/capell-analytics.php`: defaults for route prefix, tracking, consent, ignored paths/selectors, retention, hashing, and table names.
- `packages/analytics/resources/lang/en/package.php`: package registry description.
- `packages/analytics/resources/lang/en/settings.php`: settings labels and helper text.
- `packages/analytics/resources/lang/en/consent.php`: consent modal copy and validation text.
- `packages/analytics/resources/lang/en/widgets.php`: widget labels.
- `packages/analytics/resources/views/tracker.blade.php`: script/bootstrap render hook view.
- `packages/analytics/resources/js/capell-analytics.js`: beacon tracker.
- `packages/analytics/routes/web.php`: beacon and consent endpoints, with CSRF middleware excluded because `navigator.sendBeacon()` cannot reliably attach Laravel's CSRF token during unload events.
- `packages/analytics/src/Providers/AnalyticsServiceProvider.php`: package registration, config, views, translations, migrations, routes, settings, models, protected tables, frontend hook.
- `packages/analytics/src/Providers/AdminServiceProvider.php`: dashboard widgets, settings contributor, retention schedule, command registration.
- `packages/analytics/src/Settings/AnalyticsSettings.php`: settings object.
- `packages/analytics/src/Filament/Settings/AnalyticsSettingsSchema.php`: settings schema.
- `packages/analytics/src/Filament/Settings/Contributors/AnalyticsDashboardSettingsContributor.php`: dashboard settings contributor.
- `packages/analytics/src/Console/Commands/PurgeAnalyticsDataCommand.php`: retention command.
- `packages/analytics/src/Enums/*.php`: event, consent category, consent region, consent status enums.
- `packages/analytics/src/Data/*.php`: beacon, consent, event, visit, summary, journey, and window data.
- `packages/analytics/src/Models/*.php`: `AnalyticsVisit`, `AnalyticsConsent`, `AnalyticsEvent`.
- `packages/analytics/src/Actions/*.php`: consent, event recording, reporting, journey, and purge actions.
- `packages/analytics/src/Http/Controllers/*.php`: beacon and consent controllers.
- `packages/analytics/src/Support/RenderHooks/RegisterAnalyticsTrackerHook.php`: frontend BodyEnd render hook.
- `packages/analytics/src/Support/Consent/ConsentRegionResolver.php`: server-side region resolver.
- `packages/analytics/src/Filament/Widgets/*.php`: overview, popular, trending, journeys, and actions widgets.
- `packages/analytics/database/migrations/*.php`: analytics table migrations.
- `packages/analytics/database/settings/create_analytics_settings.php`: settings migration.
- `packages/analytics/database/factories/*.php`: model factories.
- `packages/analytics/tests/AnalyticsTestCase.php`: package test case.
- `packages/analytics/tests/Pest.php`: Pest setup.
- `packages/analytics/tests/Feature` and `packages/analytics/tests/Unit`: focused tests.
- Modify `composer.json`: add `Capell\\Analytics\\` and `Capell\\Analytics\\Database\\Factories\\` autoload entries.

## Task 1: Package Skeleton And Registration

**Files:**

- Create: `packages/analytics/composer.json`
- Create: `packages/analytics/capell.json`
- Create: `packages/analytics/config/capell-analytics.php`
- Create: `packages/analytics/resources/lang/en/package.php`
- Create: `packages/analytics/resources/lang/en/settings.php`
- Create: `packages/analytics/resources/lang/en/consent.php`
- Create: `packages/analytics/resources/lang/en/widgets.php`
- Create: `packages/analytics/src/Providers/AnalyticsServiceProvider.php`
- Create: `packages/analytics/src/Providers/AdminServiceProvider.php`
- Create: `packages/analytics/tests/AnalyticsTestCase.php`
- Create: `packages/analytics/tests/Pest.php`
- Create: `packages/analytics/tests/Unit/Providers/AnalyticsServiceProviderTest.php`
- Modify: `composer.json`

- [ ] **Step 1: Write provider smoke tests**

Create `packages/analytics/tests/Pest.php`:

```php
<?php

declare(strict_types=1);

use Capell\Analytics\Tests\AnalyticsTestCase;

uses(AnalyticsTestCase::class)->in(__DIR__);
```

Create `packages/analytics/tests/AnalyticsTestCase.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Analytics\Tests;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Analytics\Providers\AnalyticsServiceProvider;
use Capell\Core\Facades\CapellCore;
use Capell\Frontend\Contracts\SettingsMigrationProviderInterface;
use Capell\Frontend\Providers\FrontendServiceProvider;
use Capell\Tests\AbstractTestCase;
use Illuminate\Foundation\Application;
use Livewire\LivewireServiceProvider;
use MichalOravec\PaginateRoute\PaginateRouteServiceProvider;
use Override;

class AnalyticsTestCase extends AbstractTestCase
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
        return 'capell-analytics';
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
            AnalyticsServiceProvider::class,
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
        CapellCore::forcePackageInstalled(AnalyticsServiceProvider::$packageName);
    }
}
```

Create `packages/analytics/tests/Unit/Providers/AnalyticsServiceProviderTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\Analytics\Providers\AnalyticsServiceProvider;
use Capell\Core\Facades\CapellCore;
use Illuminate\Support\Facades\Route;

it('registers the analytics package metadata', function (): void {
    $package = CapellCore::getPackage(AnalyticsServiceProvider::$packageName);

    expect($package->name)->toBe(AnalyticsServiceProvider::$packageName);
});

it('loads the analytics config', function (): void {
    expect(config('capell-analytics.enabled'))->toBeTrue()
        ->and(config('capell-analytics.route_prefix'))->toBe('capell/analytics');
});

it('registers analytics routes', function (): void {
    expect(Route::has('capell-analytics.events'))->toBeTrue()
        ->and(Route::has('capell-analytics.consent'))->toBeTrue();
});
```

- [ ] **Step 2: Run the provider tests and verify they fail**

Run:

```bash
vendor/bin/pest packages/analytics/tests/Unit/Providers/AnalyticsServiceProviderTest.php
```

Expected: FAIL because `Capell\Analytics\Providers\AnalyticsServiceProvider` and package routes do not exist yet.

- [ ] **Step 3: Add package metadata, config, translations, routes, and providers**

Create `packages/analytics/composer.json`:

```json
{
    "name": "capell-app/analytics",
    "description": "First-party analytics, visitor journeys, click tracking, and consent management for Capell CMS",
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
            "Capell\\Analytics\\": "src/",
            "Capell\\Analytics\\Database\\Factories\\": "database/factories"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Capell\\Analytics\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Capell\\Analytics\\Providers\\AnalyticsServiceProvider"
            ]
        }
    },
    "config": {
        "sort-packages": true
    },
    "prefer-stable": true
}
```

Create `packages/analytics/capell.json`:

```json
{
    "name": "capell-app/analytics",
    "description": "First-party analytics, visitor journeys, click tracking, and consent management.",
    "providers": {
        "shared": ["Capell\\Analytics\\Providers\\AnalyticsServiceProvider"],
        "admin": ["Capell\\Analytics\\Providers\\AdminServiceProvider"]
    }
}
```

Create `packages/analytics/config/capell-analytics.php`:

```php
<?php

declare(strict_types=1);

return [
    'enabled' => true,
    'route_prefix' => 'capell/analytics',
    'track_page_views' => true,
    'track_clicks' => true,
    'track_forms' => false,
    'automatic_click_tracking' => true,
    'require_consent_for_all_regions' => false,
    'default_consent_region' => null,
    'policy_version' => '1.0',
    'retention_days' => 365,
    'hash_visitor_data' => true,
    'hash_salt' => env('APP_KEY', 'capell-analytics'),
    'ignored_paths' => [
        '/admin*',
        '/livewire*',
        '/capell/analytics*',
    ],
    'ignored_selectors' => [
        '[data-capell-analytics-ignore]',
        '[wire\\:click]',
    ],
    'tables' => [
        'visits' => 'analytics_visits',
        'consents' => 'analytics_consents',
        'events' => 'analytics_events',
    ],
];
```

Create `packages/analytics/resources/lang/en/package.php`:

```php
<?php

declare(strict_types=1);

return [
    'description' => 'Adds first-party analytics, visitor journeys, click tracking, and consent management.',
];
```

Create minimal translation files now. Later tasks can add keys as UI grows:

```php
<?php

declare(strict_types=1);

return [];
```

Use that content for:

- `packages/analytics/resources/lang/en/settings.php`
- `packages/analytics/resources/lang/en/consent.php`
- `packages/analytics/resources/lang/en/widgets.php`

Create `packages/analytics/routes/web.php`:

```php
<?php

declare(strict_types=1);

use Capell\Analytics\Http\Controllers\AnalyticsBeaconController;
use Capell\Analytics\Http\Controllers\AnalyticsConsentController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

$routePrefix = trim((string) config('capell-analytics.route_prefix', 'capell/analytics'), '/');

Route::prefix($routePrefix)
    ->middleware(['web'])
    ->group(function (): void {
        Route::post('events', AnalyticsBeaconController::class)
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->name('capell-analytics.events');

        Route::post('consent', AnalyticsConsentController::class)
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->name('capell-analytics.consent');
    });
```

Create temporary no-content controllers so route registration can be tested before the event and consent actions exist:

```php
<?php

declare(strict_types=1);

namespace Capell\Analytics\Http\Controllers;

use Illuminate\Http\Response;

final class AnalyticsBeaconController
{
    public function __invoke(): Response
    {
        return response()->noContent();
    }
}
```

Use the same body for `AnalyticsConsentController`, changing only the class name.

Create `packages/analytics/src/Providers/AdminServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Analytics\Providers;

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

Create `packages/analytics/src/Providers/AnalyticsServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Analytics\Providers;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Spatie\LaravelPackageTools\Package;

final class AnalyticsServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-analytics';

    public static string $packageName = 'capell-app/analytics';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile('capell-analytics')
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
            description: fn (): string => __('capell-analytics::package.description'),
        );

        return $this;
    }
}
```

- [ ] **Step 4: Add root Composer autoload entries**

Modify root `composer.json` only in `autoload.psr-4` and `autoload-dev.psr-4`:

```json
"Capell\\Analytics\\": "packages/analytics/src",
"Capell\\Analytics\\Database\\Factories\\": "packages/analytics/database/factories",
```

and:

```json
"Capell\\Analytics\\Tests\\": "packages/analytics/tests",
```

Run:

```bash
composer dump-autoload
```

Expected: composer regenerates autoload files without package discovery errors.

- [ ] **Step 5: Run provider tests**

Run:

```bash
vendor/bin/pest packages/analytics/tests/Unit/Providers/AnalyticsServiceProviderTest.php
```

Expected: PASS.

- [ ] **Step 6: Commit the skeleton**

Run:

```bash
git add composer.json packages/analytics
git commit -m "feat: add analytics package skeleton" -- composer.json packages/analytics
```

## Task 2: Database Models, Enums, Data Objects, And Settings

**Files:**

- Create: `packages/analytics/src/Enums/AnalyticsEventType.php`
- Create: `packages/analytics/src/Enums/AnalyticsConsentCategory.php`
- Create: `packages/analytics/src/Enums/AnalyticsConsentRegion.php`
- Create: `packages/analytics/src/Enums/AnalyticsConsentStatus.php`
- Create: `packages/analytics/src/Data/AnalyticsConsentData.php`
- Create: `packages/analytics/src/Data/AnalyticsEventData.php`
- Create: `packages/analytics/src/Data/AnalyticsBeaconData.php`
- Create: `packages/analytics/src/Data/AnalyticsVisitData.php`
- Create: `packages/analytics/src/Data/AnalyticsPageSummaryData.php`
- Create: `packages/analytics/src/Data/AnalyticsJourneyStepData.php`
- Create: `packages/analytics/src/Data/AnalyticsWindowData.php`
- Create: `packages/analytics/src/Models/AnalyticsVisit.php`
- Create: `packages/analytics/src/Models/AnalyticsConsent.php`
- Create: `packages/analytics/src/Models/AnalyticsEvent.php`
- Create: `packages/analytics/database/migrations/create_analytics_visits_table.php`
- Create: `packages/analytics/database/migrations/create_analytics_consents_table.php`
- Create: `packages/analytics/database/migrations/create_analytics_events_table.php`
- Create: `packages/analytics/database/settings/create_analytics_settings.php`
- Create: `packages/analytics/src/Settings/AnalyticsSettings.php`
- Create: `packages/analytics/src/Filament/Settings/AnalyticsSettingsSchema.php`
- Modify: `packages/analytics/src/Providers/AnalyticsServiceProvider.php`
- Create: `packages/analytics/tests/Feature/Database/AnalyticsMigrationsTest.php`
- Create: `packages/analytics/tests/Unit/Data/AnalyticsDataTest.php`
- Create: `packages/analytics/tests/Feature/Settings/AnalyticsSettingsTest.php`

- [ ] **Step 1: Write failing migration, enum, data, and settings tests**

Create tests asserting:

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

it('loads analytics migrations', function (): void {
    expect(Schema::hasTable('analytics_visits'))->toBeTrue()
        ->and(Schema::hasTable('analytics_consents'))->toBeTrue()
        ->and(Schema::hasTable('analytics_events'))->toBeTrue()
        ->and(Schema::hasColumn('analytics_visits', 'uuid'))->toBeTrue()
        ->and(Schema::hasColumn('analytics_consents', 'categories'))->toBeTrue()
        ->and(Schema::hasColumn('analytics_events', 'document_y'))->toBeTrue();
});
```

```php
<?php

declare(strict_types=1);

use Capell\Analytics\Data\AnalyticsConsentData;
use Capell\Analytics\Data\AnalyticsEventData;
use Capell\Analytics\Enums\AnalyticsConsentCategory;
use Capell\Analytics\Enums\AnalyticsEventType;

it('serializes consent categories as data', function (): void {
    $data = AnalyticsConsentData::from([
        'essential' => true,
        'analytics' => true,
        'marketing' => false,
        'preferences' => false,
    ]);

    expect($data->enabledCategories())->toBe([
        AnalyticsConsentCategory::Essential,
        AnalyticsConsentCategory::Analytics,
    ]);
});

it('normalizes event data', function (): void {
    $data = AnalyticsEventData::from([
        'type' => 'click',
        'url' => 'https://example.test/path?token=secret',
        'title' => 'Example',
        'event_name' => 'cta_click',
        'label' => 'Book a demo',
        'location' => 'home.hero',
        'target_selector' => 'button[data-capell-analytics]',
        'viewport_x' => 10,
        'viewport_y' => 20,
        'document_x' => 10,
        'document_y' => 520,
        'metadata' => ['nearest_landmark' => 'main'],
    ]);

    expect($data->type)->toBe(AnalyticsEventType::Click)
        ->and($data->path())->toBe('/path');
});
```

```php
<?php

declare(strict_types=1);

use Capell\Analytics\Settings\AnalyticsSettings;
use Spatie\LaravelSettings\Migrations\SettingsMigrator;

it('loads analytics settings defaults', function (): void {
    /** @var SettingsMigrator $settingsMigrator */
    $settingsMigrator = app(SettingsMigrator::class);

    expect($settingsMigrator->exists('analytics.enabled'))->toBeTrue()
        ->and(app(AnalyticsSettings::class)->retention_days)->toBe(365);
});
```

- [ ] **Step 2: Run tests and verify they fail**

Run:

```bash
vendor/bin/pest packages/analytics/tests/Feature/Database/AnalyticsMigrationsTest.php packages/analytics/tests/Unit/Data/AnalyticsDataTest.php packages/analytics/tests/Feature/Settings/AnalyticsSettingsTest.php
```

Expected: FAIL because migrations, enums, data, models, and settings do not exist.

- [ ] **Step 3: Implement enums and data objects**

Use string-backed enums with `HasLabel` when labels appear in Filament settings. Event cases:

```php
enum AnalyticsEventType: string
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
enum AnalyticsConsentCategory: string
{
    case Essential = 'essential';
    case Analytics = 'analytics';
    case Marketing = 'marketing';
    case Preferences = 'preferences';
}
```

Consent region cases: `UkOrEurope`, `OutsideUkOrEurope`, `Unknown`.

Consent status cases: `Pending`, `AcceptedAll`, `RejectedNonEssential`, `Granular`.

Implement data classes with `Spatie\LaravelData\Data`. `AnalyticsConsentData` must expose:

```php
/** @return list<AnalyticsConsentCategory> */
public function enabledCategories(): array
{
    $categories = [AnalyticsConsentCategory::Essential];

    if ($this->analytics) {
        $categories[] = AnalyticsConsentCategory::Analytics;
    }

    if ($this->marketing) {
        $categories[] = AnalyticsConsentCategory::Marketing;
    }

    if ($this->preferences) {
        $categories[] = AnalyticsConsentCategory::Preferences;
    }

    return $categories;
}
```

`AnalyticsEventData::path()` must parse the URL with `parse_url($this->url, PHP_URL_PATH)` and return `'/'` for blank paths.

- [ ] **Step 4: Implement migrations, models, factories, and service provider registration**

Add migrations exactly matching the spec columns. Use config table names:

```php
$tableName = config('capell-analytics.tables.visits', 'analytics_visits');
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
    'categories' => AsData::class . ':' . AnalyticsConsentData::class,
    'terms_accepted_at' => 'immutable_datetime',
    'decided_at' => 'immutable_datetime',
];
```

Register in `AnalyticsServiceProvider`:

```php
->hasMigrations([
    'create_analytics_visits_table',
    'create_analytics_consents_table',
    'create_analytics_events_table',
])
```

Add `registerModels()`, `registerSettings()`, and `registerProtectedTables()` chain methods following `AuthenticationLogServiceProvider`.

- [ ] **Step 5: Implement settings and settings schema**

`AnalyticsSettings` properties:

```php
public bool $enabled = true;
public bool $track_page_views = true;
public bool $track_clicks = true;
public bool $track_forms = false;
public bool $automatic_click_tracking = true;
public bool $require_consent_for_all_regions = false;
public ?string $default_consent_region = null;
public string $policy_version = '1.0';
public int $retention_days = 365;
public bool $hash_visitor_data = true;
public string $hash_salt = 'capell-analytics';
/** @var list<string> */
public array $ignored_paths = ['/admin*', '/livewire*', '/capell/analytics*'];
/** @var list<string> */
public array $ignored_selectors = ['[data-capell-analytics-ignore]', '[wire\\:click]'];
public string $route_prefix = 'capell/analytics';
```

Settings migration adds all keys under `analytics.*` using `exists()` checks.

Settings schema uses toggles, text inputs, and textarea fields with translation keys from `capell-analytics::settings`.

- [ ] **Step 6: Run focused tests**

Run:

```bash
vendor/bin/pest packages/analytics/tests/Feature/Database/AnalyticsMigrationsTest.php packages/analytics/tests/Unit/Data/AnalyticsDataTest.php packages/analytics/tests/Feature/Settings/AnalyticsSettingsTest.php
```

Expected: PASS.

- [ ] **Step 7: Commit the data layer**

Run:

```bash
git add packages/analytics
git commit -m "feat: add analytics data model" -- packages/analytics
```

## Task 3: Consent Region Resolution And Consent Recording

**Files:**

- Create: `packages/analytics/src/Support/Consent/ConsentRegionResolver.php`
- Create: `packages/analytics/src/Actions/ResolveConsentRegionAction.php`
- Create: `packages/analytics/src/Actions/CreateAnalyticsVisitAction.php`
- Create: `packages/analytics/src/Actions/UpdateAnalyticsConsentAction.php`
- Modify: `packages/analytics/src/Http/Controllers/AnalyticsConsentController.php`
- Create: `packages/analytics/tests/Unit/Actions/ResolveConsentRegionActionTest.php`
- Create: `packages/analytics/tests/Feature/Consent/AnalyticsConsentControllerTest.php`

- [ ] **Step 1: Write failing consent tests**

Test cases:

- forced config `uk_or_europe` returns `AnalyticsConsentRegion::UkOrEurope`
- forced config `outside_uk_or_europe` returns `AnalyticsConsentRegion::OutsideUkOrEurope`
- invalid or missing location returns `AnalyticsConsentRegion::Unknown`
- granular consent without `terms_accepted` returns HTTP 422
- UK/Europe granular consent with terms stores consent categories and visit row
- reject non-essential stores essential-only categories

Use POST route:

```php
$this->postJson(route('capell-analytics.consent'), [
    'region' => 'uk_or_europe',
    'status' => 'granular',
    'terms_accepted' => true,
    'categories' => [
        'analytics' => true,
        'marketing' => false,
        'preferences' => false,
    ],
]);
```

- [ ] **Step 2: Run tests and verify they fail**

Run:

```bash
vendor/bin/pest packages/analytics/tests/Unit/Actions/ResolveConsentRegionActionTest.php packages/analytics/tests/Feature/Consent/AnalyticsConsentControllerTest.php
```

Expected: FAIL because actions and controller behavior do not exist.

- [ ] **Step 3: Implement region resolver**

`ConsentRegionResolver` should:

- return configured `capell-analytics.default_consent_region` when set to a valid enum value
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

`CreateAnalyticsVisitAction::handle(Request $request, AnalyticsConsentRegion $region): AnalyticsVisit` should:

- generate UUID
- set landing URL, referrer, UTM values, region, pending status, started/last seen
- set IP and user agent hashes only when `hash_visitor_data` is true

`UpdateAnalyticsConsentAction::handle(Request $request, AnalyticsConsentData $data, AnalyticsConsentStatus $status, AnalyticsConsentRegion $region): AnalyticsConsent` should:

- require terms acceptance for `Granular`
- create or reuse visit by `capell_analytics_visit` cookie when present
- store consent row
- update visit consent status and region
- queue cookie with visit UUID for one year

- [ ] **Step 5: Implement consent controller**

Controller validation:

```php
$validated = $request->validate([
    'region' => ['required', Rule::enum(AnalyticsConsentRegion::class)],
    'status' => ['required', Rule::enum(AnalyticsConsentStatus::class)],
    'terms_accepted' => ['boolean'],
    'categories.analytics' => ['boolean'],
    'categories.marketing' => ['boolean'],
    'categories.preferences' => ['boolean'],
]);
```

For `accepted_all`, set all categories true. For `rejected_non_essential`, set all non-essential categories false. Return JSON with the visit UUID and enabled categories.

- [ ] **Step 6: Run consent tests**

Run:

```bash
vendor/bin/pest packages/analytics/tests/Unit/Actions/ResolveConsentRegionActionTest.php packages/analytics/tests/Feature/Consent/AnalyticsConsentControllerTest.php
```

Expected: PASS.

- [ ] **Step 7: Commit consent behavior**

Run:

```bash
git add packages/analytics
git commit -m "feat: add analytics consent tracking" -- packages/analytics
```

## Task 4: Beacon Event Recording

**Files:**

- Create: `packages/analytics/src/Actions/RecordAnalyticsEventAction.php`
- Create: `packages/analytics/src/Actions/RecordPageViewAction.php`
- Create: `packages/analytics/src/Actions/RecordClickAction.php`
- Create: `packages/analytics/src/Actions/RecordCustomActionAction.php`
- Modify: `packages/analytics/src/Http/Controllers/AnalyticsBeaconController.php`
- Create: `packages/analytics/tests/Feature/Events/AnalyticsBeaconControllerTest.php`
- Create: `packages/analytics/tests/Unit/Actions/RecordAnalyticsEventActionTest.php`

- [ ] **Step 1: Write failing event tests**

Cover:

- UK/Europe event without analytics consent is not stored
- page view after analytics consent is stored
- outside-region page view stores with default settings
- click stores location fields
- events on ignored paths are skipped
- invalid event type returns 422
- success returns 204
- POST requests without CSRF token do not return 419, matching the existing frontend toolbar beacon route pattern

Use route `capell-analytics.events` with payload:

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
            'target_selector' => 'button[data-capell-analytics]',
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
vendor/bin/pest packages/analytics/tests/Feature/Events/AnalyticsBeaconControllerTest.php packages/analytics/tests/Unit/Actions/RecordAnalyticsEventActionTest.php
```

Expected: FAIL because event actions are not implemented.

- [ ] **Step 3: Implement event recording gate**

`RecordAnalyticsEventAction` should:

- resolve visit by UUID
- skip if package disabled
- skip ignored paths using `Str::is($ignoredPath, $path)`
- skip non-essential events for `UkOrEurope` or `Unknown` unless visit has analytics consent
- allow outside-region events unless `require_consent_for_all_regions` is true
- assign next sequence using current max sequence for the visit
- create `AnalyticsEvent`

Consent check:

```php
$hasAnalyticsConsent = $visit->consent_status === AnalyticsConsentStatus::AcceptedAll
    || $visit->consents()
        ->latest('decided_at')
        ->get()
        ->contains(fn (AnalyticsConsent $consent): bool => $consent->categories->analytics);
```

- [ ] **Step 4: Implement page, click, and custom wrappers**

`RecordPageViewAction`, `RecordClickAction`, and `RecordCustomActionAction` should accept an `AnalyticsEventData` and delegate to `RecordAnalyticsEventAction`, after checking type-specific requirements:

- page view must have URL
- click must have URL and one of `target_selector`, `label`, or `location`
- custom must have `event_name`

- [ ] **Step 5: Implement beacon controller**

Validation:

```php
$validated = $request->validate([
    'visit_id' => ['nullable', 'string', 'max:80'],
    'events' => ['required', 'array', 'max:25'],
    'events.*.type' => ['required', Rule::enum(AnalyticsEventType::class)],
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
vendor/bin/pest packages/analytics/tests/Feature/Events/AnalyticsBeaconControllerTest.php packages/analytics/tests/Unit/Actions/RecordAnalyticsEventActionTest.php
```

Expected: PASS.

- [ ] **Step 7: Commit beacon event recording**

Run:

```bash
git add packages/analytics
git commit -m "feat: record analytics beacon events" -- packages/analytics
```

## Task 5: Frontend Tracker And Render Hook

**Files:**

- Create: `packages/analytics/resources/views/tracker.blade.php`
- Create: `packages/analytics/resources/js/capell-analytics.js`
- Create: `packages/analytics/src/Support/RenderHooks/RegisterAnalyticsTrackerHook.php`
- Modify: `packages/analytics/src/Providers/AnalyticsServiceProvider.php`
- Create: `packages/analytics/tests/Feature/Frontend/AnalyticsRenderHookTest.php`
- Create: `packages/analytics/tests/Unit/Frontend/AnalyticsScriptTest.php`

- [ ] **Step 1: Write failing frontend tests**

Test render hook registration by resolving `RenderHookRegistry`, rendering `BodyEnd`, and asserting output includes:

- `data-capell-analytics-tracker`
- route URLs for `capell-analytics.events` and `capell-analytics.consent`
- ignored selectors JSON

Test JavaScript source contains:

- `navigator.sendBeacon`
- `keepalive: true`
- `data-capell-analytics-ignore`
- `data-capell-analytics-label`
- `data-capell-analytics-location`

- [ ] **Step 2: Run frontend tests and verify they fail**

Run:

```bash
vendor/bin/pest packages/analytics/tests/Feature/Frontend/AnalyticsRenderHookTest.php packages/analytics/tests/Unit/Frontend/AnalyticsScriptTest.php
```

Expected: FAIL because render hook and JS do not exist.

- [ ] **Step 3: Implement tracker Blade view**

`tracker.blade.php` should render one script tag with JSON config and one inline script that loads the JS source from the package view:

```blade
@php
    $analyticsConfig = [
        'eventsUrl' => route('capell-analytics.events'),
        'consentUrl' => route('capell-analytics.consent'),
        'trackPageViews' => (bool) config('capell-analytics.track_page_views', true),
        'trackClicks' => (bool) config('capell-analytics.track_clicks', true),
        'automaticClickTracking' => (bool) config('capell-analytics.automatic_click_tracking', true),
        'ignoredSelectors' => config('capell-analytics.ignored_selectors', []),
        'policyVersion' => config('capell-analytics.policy_version', '1.0'),
    ];
@endphp

<script
    type="application/json"
    data-capell-analytics-tracker
>
    {!! json_encode($analyticsConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) !!}
</script>
<script>
    {!! file_get_contents(__DIR__ . '/../js/capell-analytics.js') !!}
</script>
```

- [ ] **Step 4: Implement JavaScript tracker**

The script must:

- read config from `[data-capell-analytics-tracker]`
- send payloads using `navigator.sendBeacon(url, blob)`
- fall back to `fetch(url, { method: 'POST', body: json, headers: { 'Content-Type': 'application/json' }, keepalive: true })`
- avoid depending on CSRF headers because package routes explicitly exclude CSRF middleware for beacon compatibility
- send a `page_view` event on load when enabled
- listen for document clicks
- skip ignored selectors
- read explicit tracking from `data-capell-analytics`, `data-capell-analytics-label`, and `data-capell-analytics-location`
- auto-track anchors/buttons/submits only when enabled
- calculate viewport and document coordinates

Use no external JS dependencies.

- [ ] **Step 5: Register BodyEnd render hook**

Create `RegisterAnalyticsTrackerHook` following `RegisterSeoHeadHooks` and `WorkspacesServiceProvider::registerFrontendRenderHooks()`:

```php
$this->registry->register(
    RenderHookLocation::BodyEnd,
    static fn (): string => view('capell-analytics::tracker')->render(),
);
```

Call it from `AnalyticsServiceProvider::packageBooted()` only when package enabled and `RenderHookRegistry` is bound.

- [ ] **Step 6: Run frontend tests**

Run:

```bash
vendor/bin/pest packages/analytics/tests/Feature/Frontend/AnalyticsRenderHookTest.php packages/analytics/tests/Unit/Frontend/AnalyticsScriptTest.php
```

Expected: PASS.

- [ ] **Step 7: Commit frontend tracker**

Run:

```bash
git add packages/analytics
git commit -m "feat: inject analytics tracker" -- packages/analytics
```

## Task 6: Reporting, Journeys, Admin Widgets, And Retention

**Files:**

- Create: `packages/analytics/src/Actions/BuildPopularPagesQueryAction.php`
- Create: `packages/analytics/src/Actions/BuildTrendingPagesQueryAction.php`
- Create: `packages/analytics/src/Actions/BuildJourneyTimelineAction.php`
- Create: `packages/analytics/src/Actions/PurgeAnalyticsDataAction.php`
- Create: `packages/analytics/src/Console/Commands/PurgeAnalyticsDataCommand.php`
- Create: `packages/analytics/src/Filament/Widgets/AnalyticsOverviewStatsWidget.php`
- Create: `packages/analytics/src/Filament/Widgets/PopularPagesWidget.php`
- Create: `packages/analytics/src/Filament/Widgets/TrendingPagesWidget.php`
- Create: `packages/analytics/src/Filament/Widgets/RecentJourneysWidget.php`
- Create: `packages/analytics/src/Filament/Widgets/TopActionsWidget.php`
- Create: `packages/analytics/src/Filament/Settings/Contributors/AnalyticsDashboardSettingsContributor.php`
- Modify: `packages/analytics/src/Providers/AdminServiceProvider.php`
- Create: `packages/analytics/tests/Feature/Reports/AnalyticsReportsTest.php`
- Create: `packages/analytics/tests/Feature/Retention/PurgeAnalyticsDataActionTest.php`
- Create: `packages/analytics/tests/Feature/Filament/AnalyticsWidgetsTest.php`

- [ ] **Step 1: Write failing reporting and retention tests**

Seed analytics events with current and previous windows. Assert:

- popular pages sort by page view count descending
- trending pages compare current window to previous equivalent window
- journey timeline is ordered by sequence and includes time since previous step
- purge deletes events older than retention
- widgets instantiate without throwing

- [ ] **Step 2: Run tests and verify they fail**

Run:

```bash
vendor/bin/pest packages/analytics/tests/Feature/Reports/AnalyticsReportsTest.php packages/analytics/tests/Feature/Retention/PurgeAnalyticsDataActionTest.php packages/analytics/tests/Feature/Filament/AnalyticsWidgetsTest.php
```

Expected: FAIL because reporting, widgets, and retention are not implemented.

- [ ] **Step 3: Implement report actions**

`BuildPopularPagesQueryAction::handle(AnalyticsWindowData $window): Collection` should query `AnalyticsEvent` where type is `PageView` and `occurred_at` is inside the window, grouped by `path`, selecting:

- `path`
- `url`
- `page_views`
- `unique_visits`
- `clicks`

`BuildTrendingPagesQueryAction::handle(AnalyticsWindowData $window): Collection` should calculate previous window from the current duration and return `AnalyticsPageSummaryData` rows with current count, previous count, absolute change, and percentage change.

`BuildJourneyTimelineAction::handle(AnalyticsVisit $visit): Collection` should order by sequence and map to `AnalyticsJourneyStepData`.

- [ ] **Step 4: Implement purge action and command**

`PurgeAnalyticsDataAction::handle(?int $retentionDays = null): int` should delete records older than cutoff from events, consents, and visits. Delete events first, consents second, visits last.

Command signature:

```php
protected $signature = 'analytics:purge {--days= : Override analytics retention days}';
```

Command output:

```php
$this->info("Purged {$deletedRecords} analytics records.");
```

- [ ] **Step 5: Implement admin widgets and provider registration**

Register widgets in `AdminServiceProvider`:

```php
CapellAdmin::registerDashboardWidget(AnalyticsOverviewStatsWidget::class, DashboardEnum::Main);
CapellAdmin::registerDashboardWidget(PopularPagesWidget::class, DashboardEnum::Main);
CapellAdmin::registerDashboardWidget(TrendingPagesWidget::class, DashboardEnum::Main);
CapellAdmin::registerDashboardWidget(RecentJourneysWidget::class, DashboardEnum::Main);
CapellAdmin::registerDashboardWidget(TopActionsWidget::class, DashboardEnum::Main);
```

Register schedule:

```php
$this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
    $schedule->command('analytics:purge')->monthly();
});
```

Widgets can be minimal version 1 Filament widgets that expose headings and call report actions. Keep all labels translated.

- [ ] **Step 6: Run reporting/admin tests**

Run:

```bash
vendor/bin/pest packages/analytics/tests/Feature/Reports/AnalyticsReportsTest.php packages/analytics/tests/Feature/Retention/PurgeAnalyticsDataActionTest.php packages/analytics/tests/Feature/Filament/AnalyticsWidgetsTest.php
```

Expected: PASS.

- [ ] **Step 7: Commit reporting and admin**

Run:

```bash
git add packages/analytics
git commit -m "feat: add analytics reporting widgets" -- packages/analytics
```

## Task 7: Integration Verification And Polish

**Files:**

- Modify only files required by failing tests or static analysis.
- Update: `packages/analytics/README.md` if absent.

- [ ] **Step 1: Add README**

Create `packages/analytics/README.md` with:

````markdown
# Capell Analytics

First-party analytics, visitor journeys, click tracking, and consent management for Capell CMS.

## Testing

Run:

```bash
vendor/bin/pest packages/analytics/tests
```
````

````

- [ ] **Step 2: Run package test suite**

Run:

```bash
vendor/bin/pest packages/analytics/tests
````

Expected: PASS.

- [ ] **Step 3: Run affected package tests**

Run:

```bash
vendor/bin/pest packages/analytics/tests packages/theme-studio/themes-core/tests
```

Expected: PASS or only unrelated existing failures. If there are failures, inspect and fix only analytics-caused failures.

- [ ] **Step 4: Run format/lint for touched files**

Run:

```bash
composer lint -- packages/analytics
```

If the project script does not accept a path argument, run:

```bash
vendor/bin/pint packages/analytics
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
git add packages/analytics composer.json
git commit -m "chore: verify analytics package" -- packages/analytics composer.json
```

Skip this commit if there are no file changes after verification.

## Self-Review Checklist

- Spec coverage: skeleton, consent, unknown-region handling, beacon events, click location, popular pages, trending pages, journeys, settings, admin widgets, retention, and tests are each mapped to tasks.
- Placeholder scan: no task contains `TBD`, `TODO`, `fill in details`, or unscoped "add tests" language.
- Type consistency: enum names, settings names, table names, route names, and action names match the design spec.
- Scope control: GA4 forwarding, heatmaps, replay, A/B testing, and personal profiling remain out of version 1.
