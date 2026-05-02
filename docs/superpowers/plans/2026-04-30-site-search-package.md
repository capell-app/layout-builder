# Site Search Package Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extract search from `themes-core` into a dedicated `capell-app/site-search` package with a frontend search page, header render hook, optional search logging, and admin analytics widgets.

**Architecture:** The new package owns the search contract, result data, database and Scout drivers, frontend route/controller/views, settings, logging model, analytics actions, and Filament dashboard widgets. `themes-core` no longer defines or imports search classes.

**Tech Stack:** PHP 8.2, Laravel package tools, Lorisleiva Actions, Spatie Laravel Data, Filament widgets, Capell render hooks, Pest

---

## File Structure

**Create package files:**

- `packages/site-search/composer.json`
- `packages/site-search/config/capell-site-search.php`
- `packages/site-search/src/Providers/SiteSearchServiceProvider.php`
- `packages/site-search/src/Providers/AdminServiceProvider.php`
- `packages/site-search/src/Contracts/SiteSearch.php`
- `packages/site-search/src/Data/SearchRequestData.php`
- `packages/site-search/src/Data/SearchResultData.php`
- `packages/site-search/src/Data/SearchAnalyticsWindowData.php`
- `packages/site-search/src/Data/SearchTermSummaryData.php`
- `packages/site-search/src/Enums/SearchDriver.php`
- `packages/site-search/src/Drivers/DatabaseSiteSearch.php`
- `packages/site-search/src/Drivers/ScoutSiteSearch.php`
- `packages/site-search/src/Actions/NormalizeSearchQueryAction.php`
- `packages/site-search/src/Actions/RunSiteSearchAction.php`
- `packages/site-search/src/Actions/RecordSiteSearchAction.php`
- `packages/site-search/src/Actions/RecordSearchResultClickAction.php`
- `packages/site-search/src/Actions/BuildTopSearchesQueryAction.php`
- `packages/site-search/src/Actions/BuildTrendingSearchesQueryAction.php`
- `packages/site-search/src/Actions/BuildZeroResultSearchesQueryAction.php`
- `packages/site-search/src/Actions/PurgeSiteSearchLogsAction.php`
- `packages/site-search/src/Http/Controllers/SearchController.php`
- `packages/site-search/src/Models/SiteSearchLog.php`
- `packages/site-search/database/factories/SiteSearchLogFactory.php`
- `packages/site-search/database/migrations/create_site_search_logs_table.php`
- `packages/site-search/database/settings/add_site_search_settings.php`
- `packages/site-search/resources/views/components/form.blade.php`
- `packages/site-search/resources/views/components/results.blade.php`
- `packages/site-search/resources/views/pages/search.blade.php`
- `packages/site-search/resources/views/filament/widgets/search-overview-stats.blade.php`
- `packages/site-search/resources/lang/en/actions.php`
- `packages/site-search/resources/lang/en/button.php`
- `packages/site-search/resources/lang/en/dashboard.php`
- `packages/site-search/resources/lang/en/generic.php`
- `packages/site-search/resources/lang/en/package.php`
- `packages/site-search/resources/lang/en/settings.php`
- `packages/site-search/routes/web.php`
- `packages/site-search/src/Settings/SiteSearchSettings.php`
- `packages/site-search/src/Filament/Settings/SiteSearchSettingsSchema.php`
- `packages/site-search/src/Filament/Settings/Contributors/SiteSearchDashboardSettingsContributor.php`
- `packages/site-search/src/Filament/Widgets/SearchOverviewStatsWidget.php`
- `packages/site-search/src/Filament/Widgets/TopSearchesWidget.php`
- `packages/site-search/src/Filament/Widgets/TrendingSearchesWidget.php`
- `packages/site-search/src/Filament/Widgets/ZeroResultSearchesWidget.php`
- `packages/site-search/src/Support/RenderHooks/RegisterHeaderSearchHook.php`
- `packages/site-search/src/Console/Commands/PurgeSiteSearchLogsCommand.php`
- `packages/site-search/tests/SiteSearchTestCase.php`
- `packages/site-search/tests/Unit/Search/SearchResultDataTest.php`
- `packages/site-search/tests/Unit/Search/DatabaseSiteSearchTest.php`
- `packages/site-search/tests/Unit/Search/ScoutSiteSearchTest.php`
- `packages/site-search/tests/Unit/Actions/NormalizeSearchQueryActionTest.php`
- `packages/site-search/tests/Feature/Actions/RecordSiteSearchActionTest.php`
- `packages/site-search/tests/Feature/Actions/SearchAnalyticsActionsTest.php`
- `packages/site-search/tests/Feature/Http/SearchControllerTest.php`
- `packages/site-search/tests/Feature/Providers/SiteSearchServiceProviderTest.php`
- `packages/site-search/tests/Feature/Widgets/SiteSearchWidgetsTest.php`

**Modify existing files:**

- `composer.json`
- `packages/theme-studio/themes-core/composer.json`
- `packages/theme-studio/themes-core/src/ThemesCoreServiceProvider.php` only if it references the deleted view namespace during implementation

**Delete or move existing files:**

- `packages/theme-studio/themes-core/src/Search/SiteSearch.php`
- `packages/theme-studio/themes-core/src/Search/SearchResult.php`
- `packages/theme-studio/themes-core/src/Search/DatabaseSiteSearch.php`
- `packages/theme-studio/themes-core/src/Search/ScoutSiteSearch.php`
- `packages/theme-studio/themes-core/resources/views/components/search-results.blade.php`
- `packages/theme-studio/themes-core/tests/Unit/Search/DatabaseSiteSearchTest.php`
- `packages/theme-studio/themes-core/tests/Unit/Search/ScoutSiteSearchTest.php`
- `packages/theme-studio/themes-core/tests/Unit/Search/SearchResultTest.php`

---

## Task 1: Create the Package Skeleton

**Files:**

- Create: `packages/site-search/composer.json`
- Create: `packages/site-search/src/Providers/SiteSearchServiceProvider.php`
- Create: `packages/site-search/src/Providers/AdminServiceProvider.php`
- Create: `packages/site-search/resources/lang/en/package.php`
- Modify: `composer.json`

- [ ] **Step 1: Add package composer.json**

Create `packages/site-search/composer.json`:

```json
{
    "name": "capell-app/site-search",
    "description": "Site search for Capell with frontend search, optional logging, and admin analytics",
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
            "Capell\\SiteSearch\\": "src/",
            "Capell\\SiteSearch\\Database\\Factories\\": "database/factories"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Capell\\SiteSearch\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Capell\\SiteSearch\\Providers\\SiteSearchServiceProvider"
            ]
        }
    },
    "config": {
        "sort-packages": true
    },
    "prefer-stable": true
}
```

- [ ] **Step 2: Add package translation**

Create `packages/site-search/resources/lang/en/package.php`:

```php
<?php

declare(strict_types=1);

return [
    'description' => 'Adds public site search, optional query logging, and search analytics widgets.',
];
```

- [ ] **Step 3: Add the service provider**

Create `packages/site-search/src/Providers/SiteSearchServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Providers;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Capell\SiteSearch\Contracts\SiteSearch;
use Capell\SiteSearch\Drivers\DatabaseSiteSearch;
use Capell\SiteSearch\Drivers\ScoutSiteSearch;
use Capell\SiteSearch\Filament\Settings\SiteSearchSettingsSchema;
use Capell\SiteSearch\Models\SiteSearchLog;
use Capell\SiteSearch\Settings\SiteSearchSettings;
use Capell\SiteSearch\Support\RenderHooks\RegisterHeaderSearchHook;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Config;
use Spatie\LaravelPackageTools\Package;

final class SiteSearchServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-site-search';

    public static string $packageName = 'capell-app/site-search';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile('capell-site-search')
            ->hasTranslations()
            ->hasViews()
            ->hasRoute('web')
            ->hasMigrations([
                'create_site_search_logs_table',
            ]);
    }

    public function registeringPackage(): void
    {
        $this->app->register(AdminServiceProvider::class);

        $this->app->bind(SiteSearch::class, function (Application $app): SiteSearch {
            $driver = (string) config('capell-site-search.driver', 'database');

            return match ($driver) {
                'scout' => new ScoutSiteSearch(
                    modelClass: (string) config('capell-site-search.scout.model'),
                    urlColumn: (string) config('capell-site-search.scout.url_column', 'slug'),
                    typeColumn: (string) config('capell-site-search.scout.type_column', 'type'),
                    excerptLength: (int) config('capell-site-search.excerpt_length', 200),
                ),
                default => new DatabaseSiteSearch(
                    db: $app['db']->connection(),
                    table: (string) config('capell-site-search.database.table', 'pages'),
                    columns: (array) config('capell-site-search.database.columns', ['title', 'excerpt', 'body']),
                    urlColumn: (string) config('capell-site-search.database.url_column', 'slug'),
                    typeColumn: (string) config('capell-site-search.database.type_column', 'type'),
                    titleColumn: (string) config('capell-site-search.database.title_column', 'title'),
                    excerptColumn: (string) config('capell-site-search.database.excerpt_column', 'excerpt'),
                    bodyColumn: (string) config('capell-site-search.database.body_column', 'body'),
                ),
            };
        });
    }

    public function packageRegistered(): void
    {
        $this
            ->registerPackageMetadata()
            ->registerModels()
            ->registerSettings()
            ->registerProtectedTables();
    }

    public function packageBooted(): void
    {
        if (class_exists(RegisterHeaderSearchHook::class)) {
            $this->app->make(RegisterHeaderSearchHook::class)->register();
        }
    }

    private function registerPackageMetadata(): self
    {
        CapellCore::registerPackage(
            self::$packageName,
            type: self::getType(),
            serviceProviderClass: self::class,
            path: realpath(__DIR__ . '/../..'),
            version: CapellCore::getInstalledPrettyVersion(self::$packageName),
            description: fn (): string => __('capell-site-search::package.description'),
        );

        return $this;
    }

    private function registerModels(): self
    {
        CapellCore::registerModels([SiteSearchLog::class]);

        return $this;
    }

    private function registerSettings(): self
    {
        /** @var SettingsSchemaRegistry $registry */
        $registry = $this->app->make(SettingsSchemaRegistry::class);

        $registry->registerSettingsClass('site_search', SiteSearchSettings::class);
        $registry->register('site_search', SiteSearchSettingsSchema::class);

        return $this;
    }

    private function registerProtectedTables(): self
    {
        CapellCore::registerProtectedTable(fn (): string => config('capell-site-search.logs.table_name', 'site_search_logs'));

        return $this;
    }
}
```

- [ ] **Step 4: Add the admin provider**

Create `packages/site-search/src/Providers/AdminServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Providers;

use Capell\Admin\Contracts\DashboardSettingsContributor;
use Capell\Admin\Enums\DashboardEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\SiteSearch\Console\Commands\PurgeSiteSearchLogsCommand;
use Capell\SiteSearch\Filament\Settings\Contributors\SiteSearchDashboardSettingsContributor;
use Capell\SiteSearch\Filament\Widgets\SearchOverviewStatsWidget;
use Capell\SiteSearch\Filament\Widgets\TopSearchesWidget;
use Capell\SiteSearch\Filament\Widgets\TrendingSearchesWidget;
use Capell\SiteSearch\Filament\Widgets\ZeroResultSearchesWidget;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

final class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag([SiteSearchDashboardSettingsContributor::class], DashboardSettingsContributor::TAG);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([PurgeSiteSearchLogsCommand::class]);
        }

        CapellAdmin::registerDashboardWidget(SearchOverviewStatsWidget::class, DashboardEnum::Main);
        CapellAdmin::registerDashboardWidget(TopSearchesWidget::class, DashboardEnum::Main);
        CapellAdmin::registerDashboardWidget(TrendingSearchesWidget::class, DashboardEnum::Main);
        CapellAdmin::registerDashboardWidget(ZeroResultSearchesWidget::class, DashboardEnum::Main);

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('site-search:purge')->monthly();
        });
    }
}
```

- [ ] **Step 5: Add root Composer autoload mappings**

Modify the root `composer.json` autoload sections:

```json
"Capell\\SiteSearch\\": "packages/site-search/src",
"Capell\\SiteSearch\\Database\\Factories\\": "packages/site-search/database/factories",
```

and in `autoload-dev`:

```json
"Capell\\SiteSearch\\Tests\\": "packages/site-search/tests",
```

- [ ] **Step 6: Run Composer validation**

```bash
composer validate --no-check-publish
```

Expected: validation passes. Existing warnings about root package metadata are acceptable only if already present before this task.

- [ ] **Step 7: Commit**

```bash
git add composer.json packages/site-search/composer.json packages/site-search/resources/lang/en/package.php packages/site-search/src/Providers
git commit -m "feat(site-search): add package skeleton"
```

---

## Task 2: Move the Search Contract, Data, Drivers, and Tests

**Files:**

- Create: `packages/site-search/src/Contracts/SiteSearch.php`
- Create: `packages/site-search/src/Data/SearchResultData.php`
- Create: `packages/site-search/src/Drivers/DatabaseSiteSearch.php`
- Create: `packages/site-search/src/Drivers/ScoutSiteSearch.php`
- Create: `packages/site-search/tests/SiteSearchTestCase.php`
- Create: `packages/site-search/tests/Unit/Search/SearchResultDataTest.php`
- Create: `packages/site-search/tests/Unit/Search/DatabaseSiteSearchTest.php`
- Create: `packages/site-search/tests/Unit/Search/ScoutSiteSearchTest.php`
- Delete: `packages/theme-studio/themes-core/src/Search/*.php`
- Delete: `packages/theme-studio/themes-core/tests/Unit/Search/*.php`

- [ ] **Step 1: Create the search contract**

Create `packages/site-search/src/Contracts/SiteSearch.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SiteSearch
{
    /**
     * @return LengthAwarePaginator<int, \Capell\SiteSearch\Data\SearchResultData>
     */
    public function search(string $query, int $perPage = 10, int $page = 1): LengthAwarePaginator;

    public function highlight(string $text, string $query): string;
}
```

- [ ] **Step 2: Create the result data object**

Create `packages/site-search/src/Data/SearchResultData.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Data;

use Spatie\LaravelData\Data;

final class SearchResultData extends Data
{
    public function __construct(
        public string $title,
        public string $url,
        public string $excerpt,
        public string $type = 'page',
        public float $score = 0.0,
    ) {}
}
```

- [ ] **Step 3: Move the database driver**

Create `packages/site-search/src/Drivers/DatabaseSiteSearch.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Drivers;

use Capell\SiteSearch\Contracts\SiteSearch;
use Capell\SiteSearch\Data\SearchResultData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use stdClass;

final class DatabaseSiteSearch implements SiteSearch
{
    /**
     * @param list<string> $columns
     */
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly string $table = 'pages',
        private readonly array $columns = ['title', 'excerpt', 'body'],
        private readonly string $urlColumn = 'slug',
        private readonly string $typeColumn = 'type',
        private readonly string $titleColumn = 'title',
        private readonly string $excerptColumn = 'excerpt',
        private readonly string $bodyColumn = 'body',
    ) {}

    public function search(string $query, int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        $query = trim($query);

        if ($query === '') {
            return new Paginator([], 0, $perPage, $page);
        }

        $builder = $this->db->table($this->table);
        $builder->where(function (QueryBuilder $databaseQuery) use ($query): void {
            foreach ($this->columns as $column) {
                $databaseQuery->orWhere($column, 'like', '%' . $query . '%');
            }
        });

        $total = (clone $builder)->count();

        $rows = $builder
            ->forPage($page, $perPage)
            ->get();

        $results = (new Collection($rows))->map(fn (stdClass $row): SearchResultData => $this->mapRowToResult($row, $query));

        return new Paginator($results, $total, $perPage, $page);
    }

    public function highlight(string $text, string $query): string
    {
        $query = trim($query);

        if ($query === '') {
            return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        }

        $escaped = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        $pattern = '/(' . preg_quote($query, '/') . ')/i';

        return (string) preg_replace($pattern, '<mark>$1</mark>', $escaped);
    }

    private function mapRowToResult(stdClass $row, string $query): SearchResultData
    {
        $title = (string) ($row->{$this->titleColumn} ?? '');
        $excerptRaw = (string) ($row->{$this->excerptColumn} ?? $row->{$this->bodyColumn} ?? '');

        return new SearchResultData(
            title: $title,
            url: '/' . ltrim((string) ($row->{$this->urlColumn} ?? ''), '/'),
            excerpt: $this->truncate($excerptRaw, 200),
            type: (string) ($row->{$this->typeColumn} ?? 'page'),
            score: $this->score($title . ' ' . $excerptRaw, $query),
        );
    }

    private function truncate(string $text, int $length): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $length)) . '...';
    }

    private function score(string $haystack, string $needle): float
    {
        return (float) substr_count(mb_strtolower($haystack), mb_strtolower($needle));
    }
}
```

- [ ] **Step 4: Move the Scout driver**

Create `packages/site-search/src/Drivers/ScoutSiteSearch.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Drivers;

use Capell\SiteSearch\Contracts\SiteSearch;
use Capell\SiteSearch\Data\SearchResultData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;

final class ScoutSiteSearch implements SiteSearch
{
    /**
     * @param class-string<Model> $modelClass
     */
    public function __construct(
        private readonly string $modelClass,
        private readonly string $urlColumn = 'slug',
        private readonly string $typeColumn = 'type',
        private readonly int $excerptLength = 200,
    ) {}

    public function search(string $query, int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        $query = trim($query);

        if ($query === '') {
            return new Paginator([], 0, $perPage, $page);
        }

        /** @var LengthAwarePaginator $paginator */
        $paginator = ($this->modelClass)::search($query)->paginate(perPage: $perPage, page: $page);

        $results = (new Collection($paginator->items()))->map(function (Model $model) use ($query): SearchResultData {
            $row = $model->toArray();
            $title = (string) ($row['title'] ?? '');
            $excerptRaw = (string) ($row['excerpt'] ?? $row['body'] ?? '');

            return new SearchResultData(
                title: $title,
                url: '/' . ltrim((string) ($row[$this->urlColumn] ?? ''), '/'),
                excerpt: mb_strlen($excerptRaw) > $this->excerptLength
                    ? rtrim(mb_substr($excerptRaw, 0, $this->excerptLength)) . '...'
                    : $excerptRaw,
                type: (string) ($row[$this->typeColumn] ?? 'page'),
                score: (float) substr_count(mb_strtolower($title . ' ' . $excerptRaw), mb_strtolower($query)),
            );
        });

        return new Paginator($results, $paginator->total(), $perPage, $page);
    }

    public function highlight(string $text, string $query): string
    {
        $query = trim($query);

        if ($query === '') {
            return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        }

        $escaped = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        $pattern = '/(' . preg_quote($query, '/') . ')/i';

        return (string) preg_replace($pattern, '<mark>$1</mark>', $escaped);
    }
}
```

- [ ] **Step 5: Move and update tests**

Move the existing search tests from `packages/theme-studio/themes-core/tests/Unit/Search` to `packages/site-search/tests/Unit/Search` and update imports:

```php
use Capell\SiteSearch\Contracts\SiteSearch;
use Capell\SiteSearch\Data\SearchResultData;
use Capell\SiteSearch\Drivers\DatabaseSiteSearch;
use Capell\SiteSearch\Drivers\ScoutSiteSearch;
```

In the data test, assert the Spatie Data array output:

```php
$result = new SearchResultData('Hello', '/hello', 'World', 'post', 0.5);

expect($result->toArray())->toBe([
    'title' => 'Hello',
    'url' => '/hello',
    'excerpt' => 'World',
    'type' => 'post',
    'score' => 0.5,
]);
```

- [ ] **Step 6: Delete old themes-core search files**

Delete:

```text
packages/theme-studio/themes-core/src/Search/SiteSearch.php
packages/theme-studio/themes-core/src/Search/SearchResult.php
packages/theme-studio/themes-core/src/Search/DatabaseSiteSearch.php
packages/theme-studio/themes-core/src/Search/ScoutSiteSearch.php
packages/theme-studio/themes-core/tests/Unit/Search/DatabaseSiteSearchTest.php
packages/theme-studio/themes-core/tests/Unit/Search/ScoutSiteSearchTest.php
packages/theme-studio/themes-core/tests/Unit/Search/SearchResultTest.php
```

- [ ] **Step 7: Run moved search tests**

```bash
vendor/bin/pest packages/site-search/tests/Unit/Search --no-coverage
```

Expected: all moved search tests pass.

- [ ] **Step 8: Verify themes-core has no search namespace**

```bash
rg -F "Capell\\Themes\\Core\\Search" packages/theme-studio/themes-core packages/site-search tests
```

Expected: no output.

- [ ] **Step 9: Commit**

```bash
git add packages/site-search/src/Contracts packages/site-search/src/Data packages/site-search/src/Drivers packages/site-search/tests packages/theme-studio/themes-core/src packages/theme-studio/themes-core/tests
git commit -m "feat(site-search): move search core out of themes-core"
```

---

## Task 3: Add Config, Settings, and Query Data

**Files:**

- Create: `packages/site-search/config/capell-site-search.php`
- Create: `packages/site-search/src/Data/SearchRequestData.php`
- Create: `packages/site-search/src/Data/SearchAnalyticsWindowData.php`
- Create: `packages/site-search/src/Data/SearchTermSummaryData.php`
- Create: `packages/site-search/src/Enums/SearchDriver.php`
- Create: `packages/site-search/src/Settings/SiteSearchSettings.php`
- Create: `packages/site-search/src/Filament/Settings/SiteSearchSettingsSchema.php`
- Create: `packages/site-search/database/settings/add_site_search_settings.php`
- Create: `packages/site-search/resources/lang/en/settings.php`
- Create: `packages/site-search/tests/Feature/Providers/SiteSearchServiceProviderTest.php`

- [ ] **Step 1: Add package config**

Create `packages/site-search/config/capell-site-search.php`:

```php
<?php

declare(strict_types=1);

return [
    'enabled' => true,
    'driver' => env('CAPELL_SITE_SEARCH_DRIVER', 'database'),
    'route_path' => 'search',
    'results_per_page' => 10,
    'excerpt_length' => 200,
    'minimum_query_length' => 2,
    'database' => [
        'table' => 'pages',
        'columns' => ['title', 'excerpt', 'body'],
        'title_column' => 'title',
        'url_column' => 'slug',
        'excerpt_column' => 'excerpt',
        'body_column' => 'body',
        'type_column' => 'type',
    ],
    'scout' => [
        'model' => null,
        'url_column' => 'slug',
        'type_column' => 'type',
    ],
    'logs' => [
        'table_name' => 'site_search_logs',
        'retention_days' => 180,
    ],
    'dashboard' => [
        'default_days' => 30,
    ],
];
```

- [ ] **Step 2: Add request and analytics data**

Create `SearchRequestData`, `SearchAnalyticsWindowData`, and `SearchTermSummaryData` with strict typed constructors:

```php
<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Data;

use Spatie\LaravelData\Data;

final class SearchRequestData extends Data
{
    public function __construct(
        public string $query,
        public int $page = 1,
        public int $perPage = 10,
        public ?int $siteId = null,
        public ?int $languageId = null,
    ) {}
}
```

```php
<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Data;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

final class SearchAnalyticsWindowData extends Data
{
    public function __construct(
        public CarbonImmutable $start,
        public CarbonImmutable $end,
    ) {}
}
```

```php
<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Data;

use Spatie\LaravelData\Data;

final class SearchTermSummaryData extends Data
{
    public function __construct(
        public string $query,
        public string $normalizedQuery,
        public int $searches,
        public int $resultsCount,
        public float $trendPercentage = 0.0,
    ) {}
}
```

- [ ] **Step 3: Add driver enum**

Create `packages/site-search/src/Enums/SearchDriver.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Enums;

use Filament\Support\Contracts\HasLabel;

enum SearchDriver: string implements HasLabel
{
    case Database = 'database';
    case Scout = 'scout';

    public function getLabel(): string
    {
        return __("capell-site-search::settings.driver_options.{$this->value}");
    }
}
```

- [ ] **Step 4: Add settings**

Create `packages/site-search/src/Settings/SiteSearchSettings.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Settings;

use Capell\Core\Contracts\SettingsContract;
use Capell\SiteSearch\Enums\SearchDriver;
use Capell\SiteSearch\Filament\Settings\SiteSearchSettingsSchema;
use Spatie\LaravelSettings\Settings;

final class SiteSearchSettings extends Settings implements SettingsContract
{
    public bool $enabled = true;

    public bool $show_header_search = true;

    public int $results_per_page = 10;

    public SearchDriver $driver = SearchDriver::Database;

    public bool $record_search_logs = true;

    public int $log_retention_days = 180;

    public bool $hash_visitor_data = true;

    public int $minimum_query_length = 2;

    public static function group(): string
    {
        return 'site_search';
    }

    public static function schema(): string
    {
        return SiteSearchSettingsSchema::class;
    }
}
```

- [ ] **Step 5: Add settings schema and translations**

Create `packages/site-search/src/Filament/Settings/SiteSearchSettingsSchema.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Filament\Settings;

use Capell\Admin\Filament\Contracts\HasSchema;
use Capell\Admin\Filament\Support\HelperText;
use Capell\SiteSearch\Enums\SearchDriver;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;

final class SiteSearchSettingsSchema implements HasSchema
{
    public static function make(Schema $configurator): array
    {
        return [
            Fieldset::make(__('capell-site-search::settings.fieldset'))
                ->columnSpanFull()
                ->schema([
                    HelperText::apply(
                        Toggle::make('enabled')
                            ->label(__('capell-site-search::settings.enabled')),
                        'capell-site-search::settings.enabled_helper',
                    ),
                    HelperText::apply(
                        Toggle::make('show_header_search')
                            ->label(__('capell-site-search::settings.show_header_search')),
                        'capell-site-search::settings.show_header_search_helper',
                    ),
                    Select::make('driver')
                        ->label(__('capell-site-search::settings.driver'))
                        ->options(SearchDriver::class)
                        ->required(),
                    TextInput::make('results_per_page')
                        ->label(__('capell-site-search::settings.results_per_page'))
                        ->integer()
                        ->minValue(1)
                        ->maxValue(50),
                    HelperText::apply(
                        Toggle::make('record_search_logs')
                            ->label(__('capell-site-search::settings.record_search_logs')),
                        'capell-site-search::settings.record_search_logs_helper',
                    ),
                    TextInput::make('log_retention_days')
                        ->label(__('capell-site-search::settings.log_retention_days'))
                        ->integer()
                        ->minValue(1)
                        ->suffix(__('capell-admin::form.days')),
                    HelperText::apply(
                        Toggle::make('hash_visitor_data')
                            ->label(__('capell-site-search::settings.hash_visitor_data')),
                        'capell-site-search::settings.hash_visitor_data_helper',
                    ),
                    TextInput::make('minimum_query_length')
                        ->label(__('capell-site-search::settings.minimum_query_length'))
                        ->integer()
                        ->minValue(1)
                        ->maxValue(10),
                ]),
        ];
    }
}
```

Create `packages/site-search/resources/lang/en/settings.php`:

```php
<?php

declare(strict_types=1);

return [
    'fieldset' => 'Site search',
    'enabled' => 'Enable site search',
    'enabled_helper' => 'Allow visitors to search published site content.',
    'show_header_search' => 'Show search in the header',
    'show_header_search_helper' => 'Inject the compact search form into the frontend header render hook.',
    'results_per_page' => 'Results per page',
    'driver' => 'Search driver',
    'driver_options' => [
        'database' => 'Database',
        'scout' => 'Scout',
    ],
    'record_search_logs' => 'Record search logs',
    'record_search_logs_helper' => 'Store search terms and result counts for analytics widgets.',
    'log_retention_days' => 'Log retention',
    'hash_visitor_data' => 'Hash visitor data',
    'hash_visitor_data_helper' => 'Store hashes for IP address and user agent instead of raw visitor data.',
    'minimum_query_length' => 'Minimum query length',
];
```

- [ ] **Step 6: Add settings migration**

Create `packages/site-search/database/settings/add_site_search_settings.php`:

```php
<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $defaults = [
            'site_search.enabled' => true,
            'site_search.show_header_search' => true,
            'site_search.results_per_page' => 10,
            'site_search.driver' => 'database',
            'site_search.record_search_logs' => true,
            'site_search.log_retention_days' => 180,
            'site_search.hash_visitor_data' => true,
            'site_search.minimum_query_length' => 2,
        ];

        foreach ($defaults as $key => $value) {
            if (! $this->migrator->exists($key)) {
                $this->migrator->add($key, $value);
            }
        }
    }
};
```

- [ ] **Step 7: Test provider binding**

Create `packages/site-search/tests/Feature/Providers/SiteSearchServiceProviderTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\SiteSearch\Contracts\SiteSearch;
use Capell\SiteSearch\Drivers\DatabaseSiteSearch;

test('provider binds the configured database search driver', function (): void {
    config()->set('capell-site-search.driver', 'database');

    expect(app(SiteSearch::class))->toBeInstanceOf(DatabaseSiteSearch::class);
});
```

- [ ] **Step 8: Run config/settings tests**

```bash
vendor/bin/pest packages/site-search/tests/Feature/Providers --no-coverage
```

Expected: provider tests pass.

- [ ] **Step 9: Commit**

```bash
git add packages/site-search/config packages/site-search/src/Data packages/site-search/src/Settings packages/site-search/src/Filament/Settings packages/site-search/database/settings packages/site-search/resources/lang/en/settings.php packages/site-search/tests/Feature/Providers
git commit -m "feat(site-search): add config and settings"
```

---

## Task 4: Add the Frontend Search Page and Header Hook

**Files:**

- Create: `packages/site-search/routes/web.php`
- Create: `packages/site-search/src/Http/Controllers/SearchController.php`
- Create: `packages/site-search/src/Actions/NormalizeSearchQueryAction.php`
- Create: `packages/site-search/src/Actions/RunSiteSearchAction.php`
- Create: `packages/site-search/src/Support/RenderHooks/RegisterHeaderSearchHook.php`
- Create: `packages/site-search/resources/views/components/form.blade.php`
- Create: `packages/site-search/resources/views/components/results.blade.php`
- Create: `packages/site-search/resources/views/pages/search.blade.php`
- Create: `packages/site-search/resources/lang/en/generic.php`
- Create: `packages/site-search/resources/lang/en/button.php`
- Create: `packages/site-search/tests/Unit/Actions/NormalizeSearchQueryActionTest.php`
- Create: `packages/site-search/tests/Feature/Http/SearchControllerTest.php`

- [ ] **Step 1: Add the route**

Create `packages/site-search/routes/web.php`:

```php
<?php

declare(strict_types=1);

use Capell\SiteSearch\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::name('capell-frontend.')
    ->middleware(['web', 'frontend.resolve'])
    ->group(function (): void {
        Route::get(config('capell-site-search.route_path', 'search'), SearchController::class)
            ->name('search');
    });
```

- [ ] **Step 2: Add normalization action**

Create `NormalizeSearchQueryAction`:

```php
<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Actions;

use Lorisleiva\Actions\Concerns\AsAction;

final class NormalizeSearchQueryAction
{
    use AsAction;

    public function handle(string $query): string
    {
        return trim((string) preg_replace('/\s+/', ' ', mb_strtolower($query)));
    }
}
```

- [ ] **Step 3: Add run action**

Create `RunSiteSearchAction`:

```php
<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Actions;

use Capell\SiteSearch\Contracts\SiteSearch;
use Capell\SiteSearch\Data\SearchRequestData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Lorisleiva\Actions\Concerns\AsAction;

final class RunSiteSearchAction
{
    use AsAction;

    public function __construct(private readonly SiteSearch $search) {}

    public function handle(SearchRequestData $data): LengthAwarePaginator
    {
        $normalizedQuery = NormalizeSearchQueryAction::run($data->query);
        $minimumLength = (int) config('capell-site-search.minimum_query_length', 2);

        if ($normalizedQuery === '' || mb_strlen($normalizedQuery) < $minimumLength) {
            return new Paginator([], 0, $data->perPage, $data->page);
        }

        return $this->search->search($normalizedQuery, $data->perPage, $data->page);
    }
}
```

- [ ] **Step 4: Add controller**

Create `SearchController`:

```php
<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Http\Controllers;

use Capell\SiteSearch\Actions\RecordSiteSearchAction;
use Capell\SiteSearch\Actions\RunSiteSearchAction;
use Capell\SiteSearch\Data\SearchRequestData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class SearchController
{
    public function __invoke(Request $request): View
    {
        $query = (string) $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $perPage = (int) config('capell-site-search.results_per_page', 10);

        $site = $request->attributes->get('site');
        $language = $request->attributes->get('language');

        $data = new SearchRequestData(
            query: $query,
            page: $page,
            perPage: $perPage,
            siteId: is_object($site) ? (int) data_get($site, 'id') : null,
            languageId: is_object($language) ? (int) data_get($language, 'id') : null,
        );

        $results = RunSiteSearchAction::run($data);

        RecordSiteSearchAction::run($data, $results->total(), $request);

        return view('capell-site-search::pages.search', [
            'query' => $query,
            'results' => $results,
        ]);
    }
}
```

- [ ] **Step 5: Add views**

Create the form component:

```blade
@props([
    'query' => '',
])

<form
    method="GET"
    action="{{ route('capell-frontend.search') }}"
    role="search"
    class="capell-site-search-form"
>
    <label
        class="sr-only"
        for="capell-site-search-query"
    >
        {{ __('capell-site-search::generic.search_label') }}
    </label>
    <input
        id="capell-site-search-query"
        type="search"
        name="q"
        value="{{ $query }}"
        placeholder="{{ __('capell-site-search::generic.search_placeholder') }}"
    />
    <button type="submit">
        {{ __('capell-site-search::button.search') }}
    </button>
</form>
```

Create `packages/site-search/resources/views/components/results.blade.php`:

```blade
@props([
    'results',
    'query' => '',
])

@php
    use Capell\SiteSearch\Contracts\SiteSearch;

    /** @var SiteSearch $search */
    $search = app(SiteSearch::class);
@endphp

<section
    class="capell-search-results"
    aria-label="{{ __('capell-site-search::generic.results_label') }}"
>
    @if ($query === '')
        <p class="text-gray-600">
            {{ __('capell-site-search::generic.empty_query') }}
        </p>
    @elseif ($results->isEmpty())
        <p class="text-gray-600">
            {{ __('capell-site-search::generic.no_results', ['query' => $query]) }}
        </p>
    @else
        <p class="mb-4 text-sm text-gray-500">
            {{
                trans_choice('capell-site-search::generic.results_count', $results->total(), [
                    'count' => $results->total(),
                    'query' => $query,
                ])
            }}
        </p>
        <ol
            class="space-y-4"
            role="list"
        >
            @foreach ($results as $result)
                <li class="rounded-lg border border-gray-100 p-4">
                    <h2 class="text-lg font-semibold">
                        <a
                            href="{{ $result->url }}"
                            class="hover:underline"
                        >
                            {!! $search->highlight($result->title, $query) !!}
                        </a>
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        {!! $search->highlight($result->excerpt, $query) !!}
                    </p>
                    <p class="mt-2 text-xs uppercase text-gray-400">
                        {{ $result->type }}
                    </p>
                </li>
            @endforeach
        </ol>
        <div class="mt-6">
            {{ $results->links() }}
        </div>
    @endif
</section>
```

Create the page:

```blade
<main class="capell-site-search-page">
    <h1>{{ __('capell-site-search::generic.page_title') }}</h1>

    <x-capell-site-search::form :query="$query" />

    <x-capell-site-search::results
        :query="$query"
        :results="$results"
    />
</main>
```

- [ ] **Step 6: Add render hook registration**

Create `RegisterHeaderSearchHook`:

```php
<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Support\RenderHooks;

use Capell\Frontend\Enums\RenderHookLocation;
use Capell\Frontend\Support\Render\RenderHookRegistry;

final class RegisterHeaderSearchHook
{
    public function __construct(private readonly RenderHookRegistry $registry) {}

    public function register(): void
    {
        if (! (bool) config('capell-site-search.enabled', true)) {
            return;
        }

        if (! (bool) config('capell-site-search.show_header_search', true)) {
            return;
        }

        $this->registry->register(
            RenderHookLocation::Header,
            static fn (): string => view('capell-site-search::components.form')->render(),
        );
    }
}
```

If `RenderHookLocation::Header` does not exist in the installed frontend package, stop this task and add the hook upstream first. Do not change the package to use `BodyEnd`.

- [ ] **Step 7: Add translations**

Create `generic.php` and `button.php`:

```php
<?php

declare(strict_types=1);

return [
    'page_title' => 'Search',
    'results_label' => 'Search results',
    'search_label' => 'Search this site',
    'search_placeholder' => 'Search',
    'empty_query' => 'Enter a keyword to search this site.',
    'results_count' => ':count result for :query|:count results for :query',
    'no_results' => 'No results for :query. Try another keyword.',
];
```

```php
<?php

declare(strict_types=1);

return [
    'search' => 'Search',
];
```

- [ ] **Step 8: Test frontend flow**

Write tests for blank query, valid query, and result rendering.

Run:

```bash
vendor/bin/pest packages/site-search/tests/Unit/Actions/NormalizeSearchQueryActionTest.php packages/site-search/tests/Feature/Http/SearchControllerTest.php --no-coverage
```

Expected: action and controller tests pass.

- [ ] **Step 9: Commit**

```bash
git add packages/site-search/routes packages/site-search/src/Http packages/site-search/src/Actions packages/site-search/src/Support packages/site-search/resources/views packages/site-search/resources/lang/en packages/site-search/tests
git commit -m "feat(site-search): add frontend search page"
```

---

## Task 5: Add Search Logging

**Files:**

- Create: `packages/site-search/database/migrations/create_site_search_logs_table.php`
- Create: `packages/site-search/src/Models/SiteSearchLog.php`
- Create: `packages/site-search/database/factories/SiteSearchLogFactory.php`
- Create: `packages/site-search/src/Actions/RecordSiteSearchAction.php`
- Create: `packages/site-search/src/Actions/RecordSearchResultClickAction.php`
- Create: `packages/site-search/tests/Feature/Actions/RecordSiteSearchActionTest.php`

- [ ] **Step 1: Add migration**

Create `create_site_search_logs_table.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('capell-site-search.logs.table_name', 'site_search_logs');

        if (Schema::hasTable($tableName)) {
            return;
        }

        Schema::create($tableName, function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->nullable()->index();
            $table->foreignId('language_id')->nullable()->index();
            $table->string('query');
            $table->string('normalized_query')->index();
            $table->unsignedInteger('results_count')->default(0);
            $table->string('clicked_result_url')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent_hash', 64)->nullable();
            $table->timestamp('searched_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('capell-site-search.logs.table_name', 'site_search_logs'));
    }
};
```

- [ ] **Step 2: Add model**

Create `SiteSearchLog` with immutable datetime casts and factory support:

```php
<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Models;

use Capell\SiteSearch\Database\Factories\SiteSearchLogFactory;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|null $site_id
 * @property int|null $language_id
 * @property string $query
 * @property string $normalized_query
 * @property int $results_count
 * @property string|null $clicked_result_url
 * @property string|null $ip_hash
 * @property string|null $user_agent_hash
 * @property CarbonImmutable $searched_at
 */
final class SiteSearchLog extends Model
{
    /** @use HasFactory<SiteSearchLogFactory> */
    use HasFactory;

    protected static string $factory = SiteSearchLogFactory::class;

    protected $guarded = [];

    public function getTable(): string
    {
        return config('capell-site-search.logs.table_name', 'site_search_logs');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'searched_at' => 'immutable_datetime',
        ];
    }
}
```

- [ ] **Step 3: Add record action**

Create `RecordSiteSearchAction`:

```php
<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Actions;

use Capell\SiteSearch\Data\SearchRequestData;
use Capell\SiteSearch\Models\SiteSearchLog;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsAction;

final class RecordSiteSearchAction
{
    use AsAction;

    public function handle(SearchRequestData $data, int $resultsCount, Request $request): ?SiteSearchLog
    {
        if (! (bool) config('capell-site-search.record_search_logs', true)) {
            return null;
        }

        $normalizedQuery = NormalizeSearchQueryAction::run($data->query);
        $minimumLength = (int) config('capell-site-search.minimum_query_length', 2);

        if ($normalizedQuery === '' || mb_strlen($normalizedQuery) < $minimumLength) {
            return null;
        }

        return SiteSearchLog::query()->create([
            'site_id' => $data->siteId,
            'language_id' => $data->languageId,
            'query' => $data->query,
            'normalized_query' => $normalizedQuery,
            'results_count' => $resultsCount,
            'ip_hash' => $this->hashValue($request->ip()),
            'user_agent_hash' => $this->hashValue($request->userAgent()),
            'searched_at' => now(),
        ]);
    }

    private function hashValue(?string $value): ?string
    {
        if (! (bool) config('capell-site-search.hash_visitor_data', true) || $value === null || $value === '') {
            return null;
        }

        return hash('sha256', $value . '|' . config('app.key'));
    }
}
```

- [ ] **Step 4: Add result click action**

Create `RecordSearchResultClickAction`:

```php
<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Actions;

use Capell\SiteSearch\Models\SiteSearchLog;
use Lorisleiva\Actions\Concerns\AsAction;

final class RecordSearchResultClickAction
{
    use AsAction;

    public function handle(SiteSearchLog $log, string $url): SiteSearchLog
    {
        $log->forceFill([
            'clicked_result_url' => $url,
        ]);

        $log->save();

        return $log;
    }
}
```

- [ ] **Step 5: Test logging behavior**

Test:

- logs valid queries
- skips blank queries
- skips too-short queries
- respects disabled logging
- hashes IP and user agent when enabled

Run:

```bash
vendor/bin/pest packages/site-search/tests/Feature/Actions/RecordSiteSearchActionTest.php --no-coverage
```

Expected: logging action tests pass.

- [ ] **Step 6: Commit**

```bash
git add packages/site-search/database/migrations packages/site-search/database/factories packages/site-search/src/Models packages/site-search/src/Actions/RecordSiteSearchAction.php packages/site-search/src/Actions/RecordSearchResultClickAction.php packages/site-search/tests/Feature/Actions
git commit -m "feat(site-search): record optional search logs"
```

---

## Task 6: Add Analytics Actions and Widgets

**Files:**

- Create: `packages/site-search/src/Actions/BuildTopSearchesQueryAction.php`
- Create: `packages/site-search/src/Actions/BuildTrendingSearchesQueryAction.php`
- Create: `packages/site-search/src/Actions/BuildZeroResultSearchesQueryAction.php`
- Create: `packages/site-search/src/Filament/Widgets/SearchOverviewStatsWidget.php`
- Create: `packages/site-search/src/Filament/Widgets/TopSearchesWidget.php`
- Create: `packages/site-search/src/Filament/Widgets/TrendingSearchesWidget.php`
- Create: `packages/site-search/src/Filament/Widgets/ZeroResultSearchesWidget.php`
- Create: `packages/site-search/src/Filament/Settings/Contributors/SiteSearchDashboardSettingsContributor.php`
- Create: `packages/site-search/resources/views/filament/widgets/search-overview-stats.blade.php`
- Create: `packages/site-search/resources/lang/en/dashboard.php`
- Create: `packages/site-search/tests/Feature/Actions/SearchAnalyticsActionsTest.php`
- Create: `packages/site-search/tests/Feature/Widgets/SiteSearchWidgetsTest.php`

- [ ] **Step 1: Add analytics query actions**

Each action should accept `SearchAnalyticsWindowData` and return either an Eloquent builder or collection of `SearchTermSummaryData`.

`BuildTopSearchesQueryAction` groups by `normalized_query`, counts rows, sums `results_count`, filters `searched_at` between the window, and orders by search count descending.

`BuildZeroResultSearchesQueryAction` is the same shape with `where('results_count', 0)`.

`BuildTrendingSearchesQueryAction` compares the current window against the previous same-length window and calculates percentage increase.

- [ ] **Step 2: Add dashboard settings contributor**

Create `SiteSearchDashboardSettingsContributor`:

```php
<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Filament\Settings\Contributors;

use Capell\Admin\Contracts\DashboardSettingsContributor;

final class SiteSearchDashboardSettingsContributor implements DashboardSettingsContributor
{
    /**
     * @return list<array{key: string, label: string, group: string}>
     */
    public function settingsKeys(): array
    {
        return [
            ['key' => 'site_search_overview', 'label' => 'Search overview', 'group' => 'Site search'],
            ['key' => 'top_searches', 'label' => 'Top searches', 'group' => 'Site search'],
            ['key' => 'trending_searches', 'label' => 'Trending searches', 'group' => 'Site search'],
            ['key' => 'zero_result_searches', 'label' => 'Zero result searches', 'group' => 'Site search'],
        ];
    }
}
```

- [ ] **Step 3: Add widgets**

Implement table widgets for top, trending, and zero-result searches. Follow `AuthenticationLogsWidget` for `GatedByRoleAndSettings`, `CapellWidgetContract`, `queryStringIdentifier`, headings, and column spans.

Use settings keys:

- `site_search_overview`
- `top_searches`
- `trending_searches`
- `zero_result_searches`

- [ ] **Step 4: Add translations**

Create `dashboard.php`:

```php
<?php

declare(strict_types=1);

return [
    'search_overview' => 'Search overview',
    'top_searches' => 'Top searches',
    'trending_searches' => 'Trending searches',
    'zero_result_searches' => 'Zero result searches',
    'query' => 'Query',
    'searches' => 'Searches',
    'results' => 'Results',
    'trend' => 'Trend',
    'zero_result_rate' => 'Zero-result rate',
];
```

- [ ] **Step 5: Test analytics and widget rendering**

Run:

```bash
vendor/bin/pest packages/site-search/tests/Feature/Actions/SearchAnalyticsActionsTest.php packages/site-search/tests/Feature/Widgets/SiteSearchWidgetsTest.php --no-coverage
```

Expected: analytics action and widget tests pass.

- [ ] **Step 6: Commit**

```bash
git add packages/site-search/src/Actions/Build*SearchesQueryAction.php packages/site-search/src/Filament packages/site-search/resources/views/filament packages/site-search/resources/lang/en/dashboard.php packages/site-search/tests/Feature
git commit -m "feat(site-search): add search analytics widgets"
```

---

## Task 7: Add Log Retention

**Files:**

- Create: `packages/site-search/src/Actions/PurgeSiteSearchLogsAction.php`
- Create: `packages/site-search/src/Console/Commands/PurgeSiteSearchLogsCommand.php`
- Create: `packages/site-search/resources/lang/en/actions.php`

- [ ] **Step 1: Add purge action**

Create `PurgeSiteSearchLogsAction`:

```php
<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Actions;

use Capell\SiteSearch\Models\SiteSearchLog;
use Lorisleiva\Actions\Concerns\AsAction;

final class PurgeSiteSearchLogsAction
{
    use AsAction;

    public function handle(?int $retentionDays = null): int
    {
        $days = $retentionDays ?? (int) config('capell-site-search.logs.retention_days', 180);

        return SiteSearchLog::query()
            ->where('searched_at', '<', now()->subDays($days))
            ->delete();
    }
}
```

- [ ] **Step 2: Add command**

Create `PurgeSiteSearchLogsCommand`:

```php
<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Console\Commands;

use Capell\SiteSearch\Actions\PurgeSiteSearchLogsAction;
use Illuminate\Console\Command;

final class PurgeSiteSearchLogsCommand extends Command
{
    protected $signature = 'site-search:purge {--days= : Override retention days}';

    protected $description = 'Delete old site search log records.';

    public function handle(): int
    {
        $daysOption = $this->option('days');
        $deleted = PurgeSiteSearchLogsAction::run($daysOption === null ? null : (int) $daysOption);

        $this->info(__('capell-site-search::actions.purged_logs', ['count' => $deleted]));

        return self::SUCCESS;
    }
}
```

- [ ] **Step 3: Add action translation**

```php
<?php

declare(strict_types=1);

return [
    'purged_logs' => 'Purged :count site search log records.',
];
```

- [ ] **Step 4: Test purge action**

Add a test that creates one old log and one recent log, runs the action, and asserts only the old log was deleted.

Run:

```bash
vendor/bin/pest packages/site-search/tests/Feature/Actions --no-coverage
```

Expected: purge action tests pass.

- [ ] **Step 5: Commit**

```bash
git add packages/site-search/src/Actions/PurgeSiteSearchLogsAction.php packages/site-search/src/Console packages/site-search/resources/lang/en/actions.php packages/site-search/tests/Feature/Actions
git commit -m "feat(site-search): purge old search logs"
```

---

## Task 8: Remove Themes-Core Search Ownership and Verify Extraction

**Files:**

- Modify: `packages/theme-studio/themes-core/composer.json`
- Modify: `packages/theme-studio/themes-core/src/ThemesCoreServiceProvider.php` only if needed

- [ ] **Step 1: Remove stale theme search references**

Run:

```bash
rg -F "Search" packages/theme-studio/themes-core/src packages/theme-studio/themes-core/resources packages/theme-studio/themes-core/tests
```

Expected: only unrelated words such as admin global search may remain. No `Capell\Themes\Core\Search` classes, imports, tests, or search result component should remain.

- [ ] **Step 2: Confirm themes-core does not depend on site-search**

Inspect `packages/theme-studio/themes-core/composer.json`. It should not require `capell-app/site-search` unless a theme-core component explicitly consumes the package.

- [ ] **Step 3: Run affected tests**

```bash
vendor/bin/pest packages/site-search/tests packages/theme-studio/themes-core/tests --no-coverage
```

Expected: site-search tests pass and themes-core tests pass without the old search tests.

- [ ] **Step 4: Run static search for old namespace**

```bash
rg -F "Capell\\Themes\\Core\\Search" packages tests composer.json
```

Expected: no output.

- [ ] **Step 5: Commit**

```bash
git add packages/theme-studio/themes-core packages/site-search composer.json
git commit -m "chore(site-search): verify themes-core search extraction"
```

---

## Task 9: Final Verification

**Files:**

- Review all changed files.

- [ ] **Step 1: Run focused tests**

```bash
vendor/bin/pest packages/site-search/tests packages/theme-studio/themes-core/tests --no-coverage
```

Expected: all focused tests pass.

- [ ] **Step 2: Run package discovery**

```bash
composer prepare
```

Expected: package discovery succeeds and `capell-app/site-search` is discovered.

- [ ] **Step 3: Run lint and analysis**

```bash
composer lint
composer analyze
```

Expected: Pint and PHPStan pass.

- [ ] **Step 4: Run full preflight when the branch is ready**

```bash
composer preflight
```

Expected: all preflight checks pass.

- [ ] **Step 5: Commit final fixes**

```bash
git add composer.json packages/site-search packages/theme-studio/themes-core
git commit -m "chore(site-search): pass package verification"
```
