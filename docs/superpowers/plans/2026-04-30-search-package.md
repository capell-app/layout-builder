# Search Package Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extract search from `themes-core` into a dedicated `capell-app/search` package with a frontend search page, header render hook, optional search logging, and admin insights widgets.

**Architecture:** The new package owns the search contract, result data, database and Scout drivers, frontend route/controller/views, settings, logging model, insights actions, and Filament dashboard widgets. `themes-core` no longer defines or imports search classes.

**Tech Stack:** PHP 8.2, Laravel package tools, Lorisleiva Actions, Spatie Laravel Data, Filament widgets, Capell render hooks, Pest

---

## File Structure

**Create package files:**

- `packages/search/composer.json`
- `packages/search/config/capell-search.php`
- `packages/search/src/Providers/SearchServiceProvider.php`
- `packages/search/src/Providers/AdminServiceProvider.php`
- `packages/search/src/Contracts/Search.php`
- `packages/search/src/Data/SearchRequestData.php`
- `packages/search/src/Data/SearchResultData.php`
- `packages/search/src/Data/SearchInsightsWindowData.php`
- `packages/search/src/Data/SearchTermSummaryData.php`
- `packages/search/src/Enums/SearchDriver.php`
- `packages/search/src/Drivers/DatabaseSearch.php`
- `packages/search/src/Drivers/ScoutSearch.php`
- `packages/search/src/Actions/NormalizeSearchQueryAction.php`
- `packages/search/src/Actions/RunSearchAction.php`
- `packages/search/src/Actions/RecordSearchAction.php`
- `packages/search/src/Actions/RecordSearchResultClickAction.php`
- `packages/search/src/Actions/BuildTopSearchesQueryAction.php`
- `packages/search/src/Actions/BuildTrendingSearchesQueryAction.php`
- `packages/search/src/Actions/BuildZeroResultSearchesQueryAction.php`
- `packages/search/src/Actions/PurgeSearchLogsAction.php`
- `packages/search/src/Http/Controllers/SearchController.php`
- `packages/search/src/Models/SearchLog.php`
- `packages/search/database/factories/SearchLogFactory.php`
- `packages/search/database/migrations/create_search_logs_table.php`
- `packages/search/database/settings/add_search_settings.php`
- `packages/search/resources/views/components/form.blade.php`
- `packages/search/resources/views/components/results.blade.php`
- `packages/search/resources/views/pages/search.blade.php`
- `packages/search/resources/views/filament/widgets/search-overview-stats.blade.php`
- `packages/search/resources/lang/en/actions.php`
- `packages/search/resources/lang/en/button.php`
- `packages/search/resources/lang/en/dashboard.php`
- `packages/search/resources/lang/en/generic.php`
- `packages/search/resources/lang/en/package.php`
- `packages/search/resources/lang/en/settings.php`
- `packages/search/routes/web.php`
- `packages/search/src/Settings/SearchSettings.php`
- `packages/search/src/Filament/Settings/SearchSettingsSchema.php`
- `packages/search/src/Filament/Settings/Contributors/SearchDashboardSettingsContributor.php`
- `packages/search/src/Filament/Widgets/SearchOverviewStatsWidget.php`
- `packages/search/src/Filament/Widgets/TopSearchesWidget.php`
- `packages/search/src/Filament/Widgets/TrendingSearchesWidget.php`
- `packages/search/src/Filament/Widgets/ZeroResultSearchesWidget.php`
- `packages/search/src/Support/RenderHooks/RegisterHeaderSearchHook.php`
- `packages/search/src/Console/Commands/PurgeSearchLogsCommand.php`
- `packages/search/tests/SearchTestCase.php`
- `packages/search/tests/Unit/Search/SearchResultDataTest.php`
- `packages/search/tests/Unit/Search/DatabaseSearchTest.php`
- `packages/search/tests/Unit/Search/ScoutSearchTest.php`
- `packages/search/tests/Unit/Actions/NormalizeSearchQueryActionTest.php`
- `packages/search/tests/Feature/Actions/RecordSearchActionTest.php`
- `packages/search/tests/Feature/Actions/SearchInsightsActionsTest.php`
- `packages/search/tests/Feature/Http/SearchControllerTest.php`
- `packages/search/tests/Feature/Providers/SearchServiceProviderTest.php`
- `packages/search/tests/Feature/Widgets/SearchWidgetsTest.php`

**Modify existing files:**

- `composer.json`
- `packages/theme-studio/themes-core/composer.json`
- `packages/theme-studio/themes-core/src/ThemesCoreServiceProvider.php` only if it references the deleted view namespace during implementation

**Delete or move existing files:**

- `packages/theme-studio/themes-core/src/Search/Search.php`
- `packages/theme-studio/themes-core/src/Search/SearchResult.php`
- `packages/theme-studio/themes-core/src/Search/DatabaseSearch.php`
- `packages/theme-studio/themes-core/src/Search/ScoutSearch.php`
- `packages/theme-studio/themes-core/resources/views/components/search-results.blade.php`
- `packages/theme-studio/themes-core/tests/Unit/Search/DatabaseSearchTest.php`
- `packages/theme-studio/themes-core/tests/Unit/Search/ScoutSearchTest.php`
- `packages/theme-studio/themes-core/tests/Unit/Search/SearchResultTest.php`

---

## Task 1: Create the Package Skeleton

**Files:**

- Create: `packages/search/composer.json`
- Create: `packages/search/src/Providers/SearchServiceProvider.php`
- Create: `packages/search/src/Providers/AdminServiceProvider.php`
- Create: `packages/search/resources/lang/en/package.php`
- Modify: `composer.json`

- [ ] **Step 1: Add package composer.json**

Create `packages/search/composer.json`:

```json
{
    "name": "capell-app/search",
    "description": "Site search for Capell with frontend search, optional logging, and admin insights",
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
            "Capell\\Search\\": "src/",
            "Capell\\Search\\Database\\Factories\\": "database/factories"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Capell\\Search\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": ["Capell\\Search\\Providers\\SearchServiceProvider"]
        }
    },
    "config": {
        "sort-packages": true
    },
    "prefer-stable": true
}
```

- [ ] **Step 2: Add package translation**

Create `packages/search/resources/lang/en/package.php`:

```php
<?php

declare(strict_types=1);

return [
    'description' => 'Adds public site search, optional query logging, and search insights widgets.',
];
```

- [ ] **Step 3: Add the service provider**

Create `packages/search/src/Providers/SearchServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Search\Providers;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Capell\Search\Contracts\Search;
use Capell\Search\Drivers\DatabaseSearch;
use Capell\Search\Drivers\ScoutSearch;
use Capell\Search\Filament\Settings\SearchSettingsSchema;
use Capell\Search\Models\SearchLog;
use Capell\Search\Settings\SearchSettings;
use Capell\Search\Support\RenderHooks\RegisterHeaderSearchHook;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Config;
use Spatie\LaravelPackageTools\Package;

final class SearchServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-search';

    public static string $packageName = 'capell-app/search';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile('capell-search')
            ->hasTranslations()
            ->hasViews()
            ->hasRoute('web')
            ->hasMigrations([
                'create_search_logs_table',
            ]);
    }

    public function registeringPackage(): void
    {
        $this->app->register(AdminServiceProvider::class);

        $this->app->bind(Search::class, function (Application $app): Search {
            $driver = (string) config('capell-search.driver', 'database');

            return match ($driver) {
                'scout' => new ScoutSearch(
                    modelClass: (string) config('capell-search.scout.model'),
                    urlColumn: (string) config('capell-search.scout.url_column', 'slug'),
                    typeColumn: (string) config('capell-search.scout.type_column', 'type'),
                    excerptLength: (int) config('capell-search.excerpt_length', 200),
                ),
                default => new DatabaseSearch(
                    db: $app['db']->connection(),
                    table: (string) config('capell-search.database.table', 'pages'),
                    columns: (array) config('capell-search.database.columns', ['title', 'excerpt', 'body']),
                    urlColumn: (string) config('capell-search.database.url_column', 'slug'),
                    typeColumn: (string) config('capell-search.database.type_column', 'type'),
                    titleColumn: (string) config('capell-search.database.title_column', 'title'),
                    excerptColumn: (string) config('capell-search.database.excerpt_column', 'excerpt'),
                    bodyColumn: (string) config('capell-search.database.body_column', 'body'),
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
            description: fn (): string => __('capell-search::package.description'),
        );

        return $this;
    }

    private function registerModels(): self
    {
        CapellCore::registerModels([SearchLog::class]);

        return $this;
    }

    private function registerSettings(): self
    {
        /** @var SettingsSchemaRegistry $registry */
        $registry = $this->app->make(SettingsSchemaRegistry::class);

        $registry->registerSettingsClass('search', SearchSettings::class);
        $registry->register('search', SearchSettingsSchema::class);

        return $this;
    }

    private function registerProtectedTables(): self
    {
        CapellCore::registerProtectedTable(fn (): string => config('capell-search.logs.table_name', 'search_logs'));

        return $this;
    }
}
```

- [ ] **Step 4: Add the admin provider**

Create `packages/search/src/Providers/AdminServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Search\Providers;

use Capell\Admin\Contracts\DashboardSettingsContributor;
use Capell\Admin\Enums\DashboardEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Search\Console\Commands\PurgeSearchLogsCommand;
use Capell\Search\Filament\Settings\Contributors\SearchDashboardSettingsContributor;
use Capell\Search\Filament\Widgets\SearchOverviewStatsWidget;
use Capell\Search\Filament\Widgets\TopSearchesWidget;
use Capell\Search\Filament\Widgets\TrendingSearchesWidget;
use Capell\Search\Filament\Widgets\ZeroResultSearchesWidget;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

final class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag([SearchDashboardSettingsContributor::class], DashboardSettingsContributor::TAG);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([PurgeSearchLogsCommand::class]);
        }

        CapellAdmin::registerDashboardWidget(SearchOverviewStatsWidget::class, DashboardEnum::Main);
        CapellAdmin::registerDashboardWidget(TopSearchesWidget::class, DashboardEnum::Main);
        CapellAdmin::registerDashboardWidget(TrendingSearchesWidget::class, DashboardEnum::Main);
        CapellAdmin::registerDashboardWidget(ZeroResultSearchesWidget::class, DashboardEnum::Main);

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('search:purge')->monthly();
        });
    }
}
```

- [ ] **Step 5: Add root Composer autoload mappings**

Modify the root `composer.json` autoload sections:

```json
"Capell\\Search\\": "packages/search/src",
"Capell\\Search\\Database\\Factories\\": "packages/search/database/factories",
```

and in `autoload-dev`:

```json
"Capell\\Search\\Tests\\": "packages/search/tests",
```

- [ ] **Step 6: Run Composer validation**

```bash
composer validate --no-check-publish
```

Expected: validation passes. Existing warnings about root package metadata are acceptable only if already present before this task.

- [ ] **Step 7: Commit**

```bash
git add composer.json packages/search/composer.json packages/search/resources/lang/en/package.php packages/search/src/Providers
git commit -m "feat(search): add package skeleton"
```

---

## Task 2: Move the Search Contract, Data, Drivers, and Tests

**Files:**

- Create: `packages/search/src/Contracts/Search.php`
- Create: `packages/search/src/Data/SearchResultData.php`
- Create: `packages/search/src/Drivers/DatabaseSearch.php`
- Create: `packages/search/src/Drivers/ScoutSearch.php`
- Create: `packages/search/tests/SearchTestCase.php`
- Create: `packages/search/tests/Unit/Search/SearchResultDataTest.php`
- Create: `packages/search/tests/Unit/Search/DatabaseSearchTest.php`
- Create: `packages/search/tests/Unit/Search/ScoutSearchTest.php`
- Delete: `packages/theme-studio/themes-core/src/Search/*.php`
- Delete: `packages/theme-studio/themes-core/tests/Unit/Search/*.php`

- [ ] **Step 1: Create the search contract**

Create `packages/search/src/Contracts/Search.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Search\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface Search
{
    /**
     * @return LengthAwarePaginator<int, \Capell\Search\Data\SearchResultData>
     */
    public function search(string $query, int $perPage = 10, int $page = 1): LengthAwarePaginator;

    public function highlight(string $text, string $query): string;
}
```

- [ ] **Step 2: Create the result data object**

Create `packages/search/src/Data/SearchResultData.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Search\Data;

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

Create `packages/search/src/Drivers/DatabaseSearch.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Search\Drivers;

use Capell\Search\Contracts\Search;
use Capell\Search\Data\SearchResultData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use stdClass;

final class DatabaseSearch implements Search
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

Create `packages/search/src/Drivers/ScoutSearch.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Search\Drivers;

use Capell\Search\Contracts\Search;
use Capell\Search\Data\SearchResultData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;

final class ScoutSearch implements Search
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

Move the existing search tests from `packages/theme-studio/themes-core/tests/Unit/Search` to `packages/search/tests/Unit/Search` and update imports:

```php
use Capell\Search\Contracts\Search;
use Capell\Search\Data\SearchResultData;
use Capell\Search\Drivers\DatabaseSearch;
use Capell\Search\Drivers\ScoutSearch;
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
packages/theme-studio/themes-core/src/Search/Search.php
packages/theme-studio/themes-core/src/Search/SearchResult.php
packages/theme-studio/themes-core/src/Search/DatabaseSearch.php
packages/theme-studio/themes-core/src/Search/ScoutSearch.php
packages/theme-studio/themes-core/tests/Unit/Search/DatabaseSearchTest.php
packages/theme-studio/themes-core/tests/Unit/Search/ScoutSearchTest.php
packages/theme-studio/themes-core/tests/Unit/Search/SearchResultTest.php
```

- [ ] **Step 7: Run moved search tests**

```bash
vendor/bin/pest packages/search/tests/Unit/Search --no-coverage
```

Expected: all moved search tests pass.

- [ ] **Step 8: Verify themes-core has no search namespace**

```bash
rg -F "Capell\\Themes\\Core\\Search" packages/theme-studio/themes-core packages/search tests
```

Expected: no output.

- [ ] **Step 9: Commit**

```bash
git add packages/search/src/Contracts packages/search/src/Data packages/search/src/Drivers packages/search/tests packages/theme-studio/themes-core/src packages/theme-studio/themes-core/tests
git commit -m "feat(search): move search core out of themes-core"
```

---

## Task 3: Add Config, Settings, and Query Data

**Files:**

- Create: `packages/search/config/capell-search.php`
- Create: `packages/search/src/Data/SearchRequestData.php`
- Create: `packages/search/src/Data/SearchInsightsWindowData.php`
- Create: `packages/search/src/Data/SearchTermSummaryData.php`
- Create: `packages/search/src/Enums/SearchDriver.php`
- Create: `packages/search/src/Settings/SearchSettings.php`
- Create: `packages/search/src/Filament/Settings/SearchSettingsSchema.php`
- Create: `packages/search/database/settings/add_search_settings.php`
- Create: `packages/search/resources/lang/en/settings.php`
- Create: `packages/search/tests/Feature/Providers/SearchServiceProviderTest.php`

- [ ] **Step 1: Add package config**

Create `packages/search/config/capell-search.php`:

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
        'table_name' => 'search_logs',
        'retention_days' => 180,
    ],
    'dashboard' => [
        'default_days' => 30,
    ],
];
```

- [ ] **Step 2: Add request and insights data**

Create `SearchRequestData`, `SearchInsightsWindowData`, and `SearchTermSummaryData` with strict typed constructors:

```php
<?php

declare(strict_types=1);

namespace Capell\Search\Data;

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

namespace Capell\Search\Data;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

final class SearchInsightsWindowData extends Data
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

namespace Capell\Search\Data;

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

Create `packages/search/src/Enums/SearchDriver.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Search\Enums;

use Filament\Support\Contracts\HasLabel;

enum SearchDriver: string implements HasLabel
{
    case Database = 'database';
    case Scout = 'scout';

    public function getLabel(): string
    {
        return __("capell-search::settings.driver_options.{$this->value}");
    }
}
```

- [ ] **Step 4: Add settings**

Create `packages/search/src/Settings/SearchSettings.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Search\Settings;

use Capell\Core\Contracts\SettingsContract;
use Capell\Search\Enums\SearchDriver;
use Capell\Search\Filament\Settings\SearchSettingsSchema;
use Spatie\LaravelSettings\Settings;

final class SearchSettings extends Settings implements SettingsContract
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
        return 'search';
    }

    public static function schema(): string
    {
        return SearchSettingsSchema::class;
    }
}
```

- [ ] **Step 5: Add settings schema and translations**

Create `packages/search/src/Filament/Settings/SearchSettingsSchema.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Search\Filament\Settings;

use Capell\Admin\Filament\Contracts\HasSchema;
use Capell\Admin\Filament\Support\HelperText;
use Capell\Search\Enums\SearchDriver;
use Filament\FormBuilder\Components\Select;
use Filament\FormBuilder\Components\TextInput;
use Filament\FormBuilder\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;

final class SearchSettingsSchema implements HasSchema
{
    public static function make(Schema $configurator): array
    {
        return [
            Fieldset::make(__('capell-search::settings.fieldset'))
                ->columnSpanFull()
                ->schema([
                    HelperText::apply(
                        Toggle::make('enabled')
                            ->label(__('capell-search::settings.enabled')),
                        'capell-search::settings.enabled_helper',
                    ),
                    HelperText::apply(
                        Toggle::make('show_header_search')
                            ->label(__('capell-search::settings.show_header_search')),
                        'capell-search::settings.show_header_search_helper',
                    ),
                    Select::make('driver')
                        ->label(__('capell-search::settings.driver'))
                        ->options(SearchDriver::class)
                        ->required(),
                    TextInput::make('results_per_page')
                        ->label(__('capell-search::settings.results_per_page'))
                        ->integer()
                        ->minValue(1)
                        ->maxValue(50),
                    HelperText::apply(
                        Toggle::make('record_search_logs')
                            ->label(__('capell-search::settings.record_search_logs')),
                        'capell-search::settings.record_search_logs_helper',
                    ),
                    TextInput::make('log_retention_days')
                        ->label(__('capell-search::settings.log_retention_days'))
                        ->integer()
                        ->minValue(1)
                        ->suffix(__('capell-admin::form.days')),
                    HelperText::apply(
                        Toggle::make('hash_visitor_data')
                            ->label(__('capell-search::settings.hash_visitor_data')),
                        'capell-search::settings.hash_visitor_data_helper',
                    ),
                    TextInput::make('minimum_query_length')
                        ->label(__('capell-search::settings.minimum_query_length'))
                        ->integer()
                        ->minValue(1)
                        ->maxValue(10),
                ]),
        ];
    }
}
```

Create `packages/search/resources/lang/en/settings.php`:

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
    'record_search_logs_helper' => 'Store search terms and result counts for insights widgets.',
    'log_retention_days' => 'Log retention',
    'hash_visitor_data' => 'Hash visitor data',
    'hash_visitor_data_helper' => 'Store hashes for IP address and user agent instead of raw visitor data.',
    'minimum_query_length' => 'Minimum query length',
];
```

- [ ] **Step 6: Add settings migration**

Create `packages/search/database/settings/add_search_settings.php`:

```php
<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $defaults = [
            'search.enabled' => true,
            'search.show_header_search' => true,
            'search.results_per_page' => 10,
            'search.driver' => 'database',
            'search.record_search_logs' => true,
            'search.log_retention_days' => 180,
            'search.hash_visitor_data' => true,
            'search.minimum_query_length' => 2,
        ];

        foreach ($defaults as $key => $value) {
            if (! $this->migration->exists($key)) {
                $this->migration->add($key, $value);
            }
        }
    }
};
```

- [ ] **Step 7: Test provider binding**

Create `packages/search/tests/Feature/Providers/SearchServiceProviderTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\Search\Contracts\Search;
use Capell\Search\Drivers\DatabaseSearch;

test('provider binds the configured database search driver', function (): void {
    config()->set('capell-search.driver', 'database');

    expect(app(Search::class))->toBeInstanceOf(DatabaseSearch::class);
});
```

- [ ] **Step 8: Run config/settings tests**

```bash
vendor/bin/pest packages/search/tests/Feature/Providers --no-coverage
```

Expected: provider tests pass.

- [ ] **Step 9: Commit**

```bash
git add packages/search/config packages/search/src/Data packages/search/src/Settings packages/search/src/Filament/Settings packages/search/database/settings packages/search/resources/lang/en/settings.php packages/search/tests/Feature/Providers
git commit -m "feat(search): add config and settings"
```

---

## Task 4: Add the Frontend Search Page and Header Hook

**Files:**

- Create: `packages/search/routes/web.php`
- Create: `packages/search/src/Http/Controllers/SearchController.php`
- Create: `packages/search/src/Actions/NormalizeSearchQueryAction.php`
- Create: `packages/search/src/Actions/RunSearchAction.php`
- Create: `packages/search/src/Support/RenderHooks/RegisterHeaderSearchHook.php`
- Create: `packages/search/resources/views/components/form.blade.php`
- Create: `packages/search/resources/views/components/results.blade.php`
- Create: `packages/search/resources/views/pages/search.blade.php`
- Create: `packages/search/resources/lang/en/generic.php`
- Create: `packages/search/resources/lang/en/button.php`
- Create: `packages/search/tests/Unit/Actions/NormalizeSearchQueryActionTest.php`
- Create: `packages/search/tests/Feature/Http/SearchControllerTest.php`

- [ ] **Step 1: Add the route**

Create `packages/search/routes/web.php`:

```php
<?php

declare(strict_types=1);

use Capell\Search\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::name('capell-frontend.')
    ->middleware(['web', 'frontend.resolve'])
    ->group(function (): void {
        Route::get(config('capell-search.route_path', 'search'), SearchController::class)
            ->name('search');
    });
```

- [ ] **Step 2: Add normalization action**

Create `NormalizeSearchQueryAction`:

```php
<?php

declare(strict_types=1);

namespace Capell\Search\Actions;

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

Create `RunSearchAction`:

```php
<?php

declare(strict_types=1);

namespace Capell\Search\Actions;

use Capell\Search\Contracts\Search;
use Capell\Search\Data\SearchRequestData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Lorisleiva\Actions\Concerns\AsAction;

final class RunSearchAction
{
    use AsAction;

    public function __construct(private readonly Search $search) {}

    public function handle(SearchRequestData $data): LengthAwarePaginator
    {
        $normalizedQuery = NormalizeSearchQueryAction::run($data->query);
        $minimumLength = (int) config('capell-search.minimum_query_length', 2);

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

namespace Capell\Search\Http\Controllers;

use Capell\Search\Actions\RecordSearchAction;
use Capell\Search\Actions\RunSearchAction;
use Capell\Search\Data\SearchRequestData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class SearchController
{
    public function __invoke(Request $request): View
    {
        $query = (string) $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $perPage = (int) config('capell-search.results_per_page', 10);

        $site = $request->attributes->get('site');
        $language = $request->attributes->get('language');

        $data = new SearchRequestData(
            query: $query,
            page: $page,
            perPage: $perPage,
            siteId: is_object($site) ? (int) data_get($site, 'id') : null,
            languageId: is_object($language) ? (int) data_get($language, 'id') : null,
        );

        $results = RunSearchAction::run($data);

        RecordSearchAction::run($data, $results->total(), $request);

        return view('capell-search::pages.search', [
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
    class="capell-search-form"
>
    <label
        class="sr-only"
        for="capell-search-query"
    >
        {{ __('capell-search::generic.search_label') }}
    </label>
    <input
        id="capell-search-query"
        type="search"
        name="q"
        value="{{ $query }}"
        placeholder="{{ __('capell-search::generic.search_placeholder') }}"
    />
    <button type="submit">
        {{ __('capell-search::button.search') }}
    </button>
</form>
```

Create `packages/search/resources/views/components/results.blade.php`:

```blade
@props([
    'results',
    'query' => '',
])

@php
    use Capell\Search\Contracts\Search;

    /** @var Search $search */
    $search = app(Search::class);
@endphp

<section
    class="capell-search-results"
    aria-label="{{ __('capell-search::generic.results_label') }}"
>
    @if ($query === '')
        <p class="text-gray-600">
            {{ __('capell-search::generic.empty_query') }}
        </p>
    @elseif ($results->isEmpty())
        <p class="text-gray-600">
            {{ __('capell-search::generic.no_results', ['query' => $query]) }}
        </p>
    @else
        <p class="mb-4 text-sm text-gray-500">
            {{
                trans_choice('capell-search::generic.results_count', $results->total(), [
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
<main class="capell-search-page">
    <h1>{{ __('capell-search::generic.page_title') }}</h1>

    <x-capell-search::form :query="$query" />

    <x-capell-search::results
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

namespace Capell\Search\Support\RenderHooks;

use Capell\Frontend\Enums\RenderHookLocation;
use Capell\Frontend\Support\Render\RenderHookRegistry;

final class RegisterHeaderSearchHook
{
    public function __construct(private readonly RenderHookRegistry $registry) {}

    public function register(): void
    {
        if (! (bool) config('capell-search.enabled', true)) {
            return;
        }

        if (! (bool) config('capell-search.show_header_search', true)) {
            return;
        }

        $this->registry->register(
            RenderHookLocation::Header,
            static fn (): string => view('capell-search::components.form')->render(),
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
vendor/bin/pest packages/search/tests/Unit/Actions/NormalizeSearchQueryActionTest.php packages/search/tests/Feature/Http/SearchControllerTest.php --no-coverage
```

Expected: action and controller tests pass.

- [ ] **Step 9: Commit**

```bash
git add packages/search/routes packages/search/src/Http packages/search/src/Actions packages/search/src/Support packages/search/resources/views packages/search/resources/lang/en packages/search/tests
git commit -m "feat(search): add frontend search page"
```

---

## Task 5: Add Search Logging

**Files:**

- Create: `packages/search/database/migrations/create_search_logs_table.php`
- Create: `packages/search/src/Models/SearchLog.php`
- Create: `packages/search/database/factories/SearchLogFactory.php`
- Create: `packages/search/src/Actions/RecordSearchAction.php`
- Create: `packages/search/src/Actions/RecordSearchResultClickAction.php`
- Create: `packages/search/tests/Feature/Actions/RecordSearchActionTest.php`

- [ ] **Step 1: Add migration**

Create `create_search_logs_table.php`:

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
        $tableName = config('capell-search.logs.table_name', 'search_logs');

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
        Schema::dropIfExists(config('capell-search.logs.table_name', 'search_logs'));
    }
};
```

- [ ] **Step 2: Add model**

Create `SearchLog` with immutable datetime casts and factory support:

```php
<?php

declare(strict_types=1);

namespace Capell\Search\Models;

use Capell\Search\Database\Factories\SearchLogFactory;
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
final class SearchLog extends Model
{
    /** @use HasFactory<SearchLogFactory> */
    use HasFactory;

    protected static string $factory = SearchLogFactory::class;

    protected $guarded = [];

    public function getTable(): string
    {
        return config('capell-search.logs.table_name', 'search_logs');
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

Create `RecordSearchAction`:

```php
<?php

declare(strict_types=1);

namespace Capell\Search\Actions;

use Capell\Search\Data\SearchRequestData;
use Capell\Search\Models\SearchLog;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsAction;

final class RecordSearchAction
{
    use AsAction;

    public function handle(SearchRequestData $data, int $resultsCount, Request $request): ?SearchLog
    {
        if (! (bool) config('capell-search.record_search_logs', true)) {
            return null;
        }

        $normalizedQuery = NormalizeSearchQueryAction::run($data->query);
        $minimumLength = (int) config('capell-search.minimum_query_length', 2);

        if ($normalizedQuery === '' || mb_strlen($normalizedQuery) < $minimumLength) {
            return null;
        }

        return SearchLog::query()->create([
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
        if (! (bool) config('capell-search.hash_visitor_data', true) || $value === null || $value === '') {
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

namespace Capell\Search\Actions;

use Capell\Search\Models\SearchLog;
use Lorisleiva\Actions\Concerns\AsAction;

final class RecordSearchResultClickAction
{
    use AsAction;

    public function handle(SearchLog $log, string $url): SearchLog
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
vendor/bin/pest packages/search/tests/Feature/Actions/RecordSearchActionTest.php --no-coverage
```

Expected: logging action tests pass.

- [ ] **Step 6: Commit**

```bash
git add packages/search/database/migrations packages/search/database/factories packages/search/src/Models packages/search/src/Actions/RecordSearchAction.php packages/search/src/Actions/RecordSearchResultClickAction.php packages/search/tests/Feature/Actions
git commit -m "feat(search): record optional search logs"
```

---

## Task 6: Add Insights Actions and Widgets

**Files:**

- Create: `packages/search/src/Actions/BuildTopSearchesQueryAction.php`
- Create: `packages/search/src/Actions/BuildTrendingSearchesQueryAction.php`
- Create: `packages/search/src/Actions/BuildZeroResultSearchesQueryAction.php`
- Create: `packages/search/src/Filament/Widgets/SearchOverviewStatsWidget.php`
- Create: `packages/search/src/Filament/Widgets/TopSearchesWidget.php`
- Create: `packages/search/src/Filament/Widgets/TrendingSearchesWidget.php`
- Create: `packages/search/src/Filament/Widgets/ZeroResultSearchesWidget.php`
- Create: `packages/search/src/Filament/Settings/Contributors/SearchDashboardSettingsContributor.php`
- Create: `packages/search/resources/views/filament/widgets/search-overview-stats.blade.php`
- Create: `packages/search/resources/lang/en/dashboard.php`
- Create: `packages/search/tests/Feature/Actions/SearchInsightsActionsTest.php`
- Create: `packages/search/tests/Feature/Widgets/SearchWidgetsTest.php`

- [ ] **Step 1: Add insights query actions**

Each action should accept `SearchInsightsWindowData` and return either an Eloquent builder or collection of `SearchTermSummaryData`.

`BuildTopSearchesQueryAction` groups by `normalized_query`, counts rows, sums `results_count`, filters `searched_at` between the window, and orders by search count descending.

`BuildZeroResultSearchesQueryAction` is the same shape with `where('results_count', 0)`.

`BuildTrendingSearchesQueryAction` compares the current window against the previous same-length window and calculates percentage increase.

- [ ] **Step 2: Add dashboard settings contributor**

Create `SearchDashboardSettingsContributor`:

```php
<?php

declare(strict_types=1);

namespace Capell\Search\Filament\Settings\Contributors;

use Capell\Admin\Contracts\DashboardSettingsContributor;

final class SearchDashboardSettingsContributor implements DashboardSettingsContributor
{
    /**
     * @return list<array{key: string, label: string, group: string}>
     */
    public function settingsKeys(): array
    {
        return [
            ['key' => 'search_overview', 'label' => 'Search overview', 'group' => 'Site search'],
            ['key' => 'top_searches', 'label' => 'Top searches', 'group' => 'Site search'],
            ['key' => 'trending_searches', 'label' => 'Trending searches', 'group' => 'Site search'],
            ['key' => 'zero_result_searches', 'label' => 'Zero result searches', 'group' => 'Site search'],
        ];
    }
}
```

- [ ] **Step 3: Add widgets**

Implement table widgets for top, trending, and zero-result searches. Follow `LoginAuditsWidget` for `GatedByRoleAndSettings`, `CapellWidgetContract`, `queryStringIdentifier`, headings, and column spans.

Use settings keys:

- `search_overview`
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

- [ ] **Step 5: Test insights and widget rendering**

Run:

```bash
vendor/bin/pest packages/search/tests/Feature/Actions/SearchInsightsActionsTest.php packages/search/tests/Feature/Widgets/SearchWidgetsTest.php --no-coverage
```

Expected: insights action and widget tests pass.

- [ ] **Step 6: Commit**

```bash
git add packages/search/src/Actions/Build*SearchesQueryAction.php packages/search/src/Filament packages/search/resources/views/filament packages/search/resources/lang/en/dashboard.php packages/search/tests/Feature
git commit -m "feat(search): add search insights widgets"
```

---

## Task 7: Add Log Retention

**Files:**

- Create: `packages/search/src/Actions/PurgeSearchLogsAction.php`
- Create: `packages/search/src/Console/Commands/PurgeSearchLogsCommand.php`
- Create: `packages/search/resources/lang/en/actions.php`

- [ ] **Step 1: Add purge action**

Create `PurgeSearchLogsAction`:

```php
<?php

declare(strict_types=1);

namespace Capell\Search\Actions;

use Capell\Search\Models\SearchLog;
use Lorisleiva\Actions\Concerns\AsAction;

final class PurgeSearchLogsAction
{
    use AsAction;

    public function handle(?int $retentionDays = null): int
    {
        $days = $retentionDays ?? (int) config('capell-search.logs.retention_days', 180);

        return SearchLog::query()
            ->where('searched_at', '<', now()->subDays($days))
            ->delete();
    }
}
```

- [ ] **Step 2: Add command**

Create `PurgeSearchLogsCommand`:

```php
<?php

declare(strict_types=1);

namespace Capell\Search\Console\Commands;

use Capell\Search\Actions\PurgeSearchLogsAction;
use Illuminate\Console\Command;

final class PurgeSearchLogsCommand extends Command
{
    protected $signature = 'search:purge {--days= : Override retention days}';

    protected $description = 'Delete old site search log records.';

    public function handle(): int
    {
        $daysOption = $this->option('days');
        $deleted = PurgeSearchLogsAction::run($daysOption === null ? null : (int) $daysOption);

        $this->info(__('capell-search::actions.purged_logs', ['count' => $deleted]));

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
vendor/bin/pest packages/search/tests/Feature/Actions --no-coverage
```

Expected: purge action tests pass.

- [ ] **Step 5: Commit**

```bash
git add packages/search/src/Actions/PurgeSearchLogsAction.php packages/search/src/Console packages/search/resources/lang/en/actions.php packages/search/tests/Feature/Actions
git commit -m "feat(search): purge old search logs"
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

- [ ] **Step 2: Confirm themes-core does not depend on search**

Inspect `packages/theme-studio/themes-core/composer.json`. It should not require `capell-app/search` unless a theme-core component explicitly consumes the package.

- [ ] **Step 3: Run affected tests**

```bash
vendor/bin/pest packages/search/tests packages/theme-studio/themes-core/tests --no-coverage
```

Expected: search tests pass and themes-core tests pass without the old search tests.

- [ ] **Step 4: Run static search for old namespace**

```bash
rg -F "Capell\\Themes\\Core\\Search" packages tests composer.json
```

Expected: no output.

- [ ] **Step 5: Commit**

```bash
git add packages/theme-studio/themes-core packages/search composer.json
git commit -m "chore(search): verify themes-core search extraction"
```

---

## Task 9: Final Verification

**Files:**

- Review all changed files.

- [ ] **Step 1: Run focused tests**

```bash
vendor/bin/pest packages/search/tests packages/theme-studio/themes-core/tests --no-coverage
```

Expected: all focused tests pass.

- [ ] **Step 2: Run package discovery**

```bash
composer prepare
```

Expected: package discovery succeeds and `capell-app/search` is discovered.

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
git add composer.json packages/search packages/theme-studio/themes-core
git commit -m "chore(search): pass package verification"
```
