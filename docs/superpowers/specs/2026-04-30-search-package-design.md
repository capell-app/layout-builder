# Search Package Design

## Goal

Build `capell-app/search` as the canonical Capell package for public site search, frontend search UI, optional query logging, and admin search insights.

The package fully extracts search from `themes-core`. Themes can render around search, but they should not own search contracts, drivers, result data, or default search result markup.

## Name

Use **Search**:

- Composer package: `capell-app/search`
- Namespace: `Capell\Search`
- Package directory: `packages/search`
- Translation namespace: `capell-search`
- Config key: `capell-search`
- Admin settings group: `search`

The name is explicit enough for the package registry and short enough for code.

## Extraction Scope

Move these existing search pieces out of `packages/theme-studio/themes-core`:

- `Capell\Themes\Core\Search\Search`
- `Capell\Themes\Core\Search\SearchResult`
- `Capell\Themes\Core\Search\DatabaseSearch`
- `Capell\Themes\Core\Search\ScoutSearch`
- `packages/theme-studio/themes-core/resources/views/components/search-results.blade.php`
- the related search tests under `packages/theme-studio/themes-core/tests/Unit/Search`

Their new homes should be:

- `Capell\Search\Contracts\Search`
- `Capell\Search\Data\SearchResultData`
- `Capell\Search\Drivers\DatabaseSearch`
- `Capell\Search\Drivers\ScoutSearch`
- `packages/search/resources/views/components/results.blade.php`
- `packages/search/tests/Unit/Search`

Do not keep compatibility aliases in `themes-core` for the first implementation. A full extraction should make search ownership obvious and let tests catch stale imports.

## Package Responsibilities

`search` owns:

- search backend contract and bundled drivers
- default database-backed search
- optional Scout-backed search
- dedicated public search page
- compact header search form injected by frontend render hook
- optional search log table
- settings for search behavior and logging
- dashboard widgets for search insights
- package translations, config, migrations, and tests

`search` does not own:

- theme-specific layout styling beyond minimal default Blade
- indexing queues or custom content extraction in version 1
- editor-managed search result boosting
- broad plugin APIs for external search providers

## Architecture

Domain logic belongs in actions:

- `NormalizeSearchQueryAction`
- `RunSearchAction`
- `RecordSearchAction`
- `RecordSearchResultClickAction`
- `BuildTopSearchesQueryAction`
- `BuildTrendingSearchesQueryAction`
- `BuildZeroResultSearchesQueryAction`

Structured data belongs in data objects:

- `SearchRequestData`
- `SearchResultData`
- `SearchInsightsWindowData`
- `SearchTermSummaryData`

Settings belong in `SearchSettings`, backed by a settings migration and `SearchSettingsSchema`.

Public frontend UI should remain small and replaceable:

- `SearchController` handles the dedicated search page.
- `RegisterHeaderSearchHook` registers the header search form.
- Blade views render the form, page, and results.

Admin widgets should be thin Filament widgets that call insights actions. They should not build insights queries inline.

## Search Backend

Bind `Search::class` to the configured driver in `SearchServiceProvider`.

Version 1 drivers:

- `database`: queries the configured table and columns using `LIKE`.
- `scout`: maps Scout results into `SearchResultData`.

The default is `database` because it works without external infrastructure.

The database driver should accept:

- connection
- table
- searchable columns
- title column
- URL column
- excerpt column
- type column
- optional site column
- optional language column

`RunSearchAction` should reject blank or too-short queries before calling the backend.

## Frontend

Register a GET route:

```php
Route::get('search', SearchController::class)
    ->middleware(['web', 'frontend.resolve'])
    ->name('capell-frontend.search');
```

The route path should be configurable, defaulting to `search`.

The search page should support:

- empty query state
- paginated results
- result count
- highlighted title and excerpt
- no-results state
- site and language context where available

The header render hook should inject a compact GET form pointing to the search route. The form should be disabled by settings when needed.

If the frontend package does not expose a header or navigation hook in the installed version, add that hook to `capell-app/frontend` first or target the nearest existing header hook. Do not register the search form in `BodyEnd` as a workaround.

## Logging

Search logging is optional and off only when settings disable it.

Create `search_logs` with:

- `id`
- `site_id` nullable index
- `language_id` nullable index
- `query`
- `normalized_query` index
- `results_count`
- `clicked_result_url` nullable
- `ip_hash` nullable
- `user_agent_hash` nullable
- `searched_at` index
- timestamps

Do not store raw IP addresses or raw user agents by default. When visitor data collection is enabled, store hashes.

Blank queries and queries shorter than the configured minimum length should not be recorded.

## Admin Widgets

Register widgets through `CapellAdmin::registerDashboardWidget(...)`, using the existing dashboard settings pattern.

Version 1 widgets:

- `TopSearchesWidget`: most searched terms in a selected date window.
- `TrendingSearchesWidget`: terms with the largest increase over the previous equivalent window.
- `ZeroResultSearchesWidget`: terms returning no results.
- `SearchOverviewStatsWidget`: total searches, unique terms, zero-result rate.

Widgets should be gated by roles and dashboard settings. Date windows should default to the last 30 days and be configurable per widget.

## Settings

`SearchSettings` should include:

- `enabled`
- `show_header_search`
- `results_per_page`
- `driver`
- `record_search_logs`
- `log_retention_days`
- `hash_visitor_data`
- `minimum_query_length`

All labels, helper text, page copy, button labels, and widget headings should use `__('capell-search::...')`.

## Database and Retention

`SearchServiceProvider` should register the search log table as protected when logging is installed.

Add a purge action or command for old logs:

- `PurgeSearchLogsAction`
- `search:purge`

Schedule it monthly from the admin provider, matching the authentication log package pattern.

## Package Dependencies

`capell-app/search` should require:

- `capell-app/admin`
- `capell-app/core`
- `capell-app/frontend`
- `lorisleiva/laravel-actions`
- `spatie/laravel-data`
- `spatie/laravel-package-tools`

It should not depend on `themes-core`.

After extraction, `themes-core` should not require `search` unless it has an explicit theme component that needs it. The preferred version 1 result is no dependency from themes-core to search.

## Testing

Tests should cover:

- moved data object serialization
- database driver search, pagination, scoring, and highlighting
- Scout driver blank-query and highlighting behavior
- service provider binding for configured driver
- search controller empty and results states
- search logging enabled and disabled
- no logging for blank or too-short queries
- top searches insights query
- trending searches insights query
- zero-result searches insights query
- dashboard widget smoke tests
- render hook registration
- `themes-core` no longer importing or defining search classes

Run the package tests with:

```bash
vendor/bin/pest packages/search/tests
```

Run affected extraction tests with:

```bash
vendor/bin/pest packages/search/tests packages/theme-studio/themes-core/tests
```

## Implementation Sequence

1. Create the new package skeleton and wire Composer autoloading.
2. Move the existing search contract, drivers, data, view, and tests.
3. Register the service provider and backend binding.
4. Add public search route, controller, actions, and page views.
5. Add settings and config.
6. Add optional logging model, migration, factory, and recording action.
7. Add insights actions and dashboard widgets.
8. Add purge command and retention behavior.
9. Remove stale `themes-core` search references.
10. Run focused package tests and affected `themes-core` tests.

This sequence keeps the extraction testable before adding insights and keeps the new package usable before the dashboard widgets arrive.
