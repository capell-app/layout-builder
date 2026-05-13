# Search

Search adds a frontend search route, configurable search drivers, result click tracking, query logging, and admin search insights.

## At A Glance

- Package: `capell-app/search`
- Namespace: `Capell\Search\`
- Surfaces: Filament admin, console, HTTP, database
- Service providers: `packages/search/src/Providers/AdminServiceProvider.php`, `packages/search/src/Providers/SearchServiceProvider.php`
- Capell dependencies: `capell-app/admin`, `capell-app/core`, `capell-app/frontend`
- Third-party dependencies: `lorisleiva/laravel-actions`, `spatie/laravel-data`, `spatie/laravel-package-tools`

## What It Adds

Search adds a frontend search route, configurable search drivers, result click tracking, query logging, and admin search insights.

- Frontend search controller route.
- Database and Scout search drivers.
- Header search render hook.
- Search insights widgets.
- Settings schema and dashboard contributor.
- Actions for search, normalization, logging, click tracking, and purging logs.

## Why It Matters

**For developers:** Uses a Search contract and driver classes so search can start with database queries and move to Scout without changing the frontend surface.

**For teams:** Lets visitors search site content and lets operators review what people searched for, including zero-result terms.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Admin](https://github.com/capell-app/admin)
- [Capell Core](https://github.com/capell-app/core)
- [Capell Frontend](https://github.com/capell-app/frontend)

**Open-source packages used here**

- [Laravel Actions](https://github.com/lorisleiva/laravel-actions) - single-purpose action classes that keep package workflows out of controllers and Filament resources.
- [Spatie Laravel Data](https://github.com/spatie/laravel-data) - typed data objects for package boundaries, form state, settings, and structured results.
- [Spatie Laravel Package Tools](https://github.com/spatie/laravel-package-tools) - Laravel package bootstrapping for config, migrations, commands, translations, and service provider setup.

**Linked package previews**

[![Laravel Actions GitHub preview](https://opengraph.githubassets.com/capell-readme/lorisleiva/laravel-actions)](https://github.com/lorisleiva/laravel-actions)

[![Spatie Laravel Data GitHub preview](https://opengraph.githubassets.com/capell-readme/spatie/laravel-data)](https://github.com/spatie/laravel-data)

[![Spatie Laravel Package Tools GitHub preview](https://opengraph.githubassets.com/capell-readme/spatie/laravel-package-tools)](https://github.com/spatie/laravel-package-tools)

## Screens And Workflow

Screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment.

- Frontend search results page.
- Header search field.
- Top searches widget.
- Trending searches widget.
- Zero-result searches widget.
- Site search settings screen.

## Technical Shape

- SearchServiceProvider and AdminServiceProvider register the package.
- Config file: capell-search.php.
- Route: GET search by default, named capell-frontend.search.
- Model: SearchLog.
- Drivers: DatabaseSearch and ScoutSearch.
- Command: PurgeSearchLogsCommand.

## Code Map

| Area      | Path                            | Purpose                                                             |
| --------- | ------------------------------- | ------------------------------------------------------------------- |
| Actions   | `packages/search/src/Actions`   | Domain operations. Test these directly where possible.              |
| Data      | `packages/search/src/Data`      | Structured payloads, form state, view models, and integration data. |
| Enums     | `packages/search/src/Enums`     | Persisted states and Filament option values.                        |
| Models    | `packages/search/src/Models`    | Eloquent records owned by the package.                              |
| Filament  | `packages/search/src/Filament`  | Admin resources, pages, widgets, and settings UI.                   |
| HTTP      | `packages/search/src/Http`      | Controllers, middleware, and request handling.                      |
| Providers | `packages/search/src/Providers` | Registration, extension hooks, routes, migrations, and resources.   |
| Resources | `packages/search/resources`     | Views, translations, assets, and package resources.                 |
| Routes    | `packages/search/routes`        | Route files loaded by the service provider.                         |
| Config    | `packages/search/config`        | Package configuration and publishable config.                       |
| Database  | `packages/search/database`      | Migrations, seeders, and settings migrations.                       |
| Tests     | `packages/search/tests`         | Package-level Pest coverage.                                        |

## Admin Surface

- Widgets: `BuildsSearchInsightsWindow`, `SearchOverviewStatsWidget`, `TopSearchesWidget`, `TrendingSearchesWidget`, `ZeroResultSearchesWidget`.
- Settings: `SearchSettings`.

## Runtime Surface

- Controllers: `SearchController`.
- Routes: `packages/search/routes/web.php`.

## Commands

- `search:purge {--days= : Override retention days}` (packages/search/src/Console/Commands/PurgeSearchLogsCommand.php)

## Data And Persistence

- search_logs stores site, language, query, normalized query, result count, clicked URL, and searched_at.
- Logs connect to sites and languages.
- Retention defaults to 180 days in config.

- Models: `SearchLog`.
- Migrations: `2026_05_10_190868_01_create_search_logs_table.php`.
- Config: `packages/search/config/capell-search.php`.
- Data objects live in `src/Data/`; use them for payloads, form state, and view models.

## Extension Points

- Contracts: `Search`.
- Register Capell extension points, routes, migrations, settings, render hooks, and resources from service providers.

## Install Impact

- Adds search_logs table and settings migration.
- Adds frontend search route.
- Adds optional header search render hook.
- Adds dashboard insights widgets.
- Adds config keys for driver, route, result count, excerpts, logging, hashing, and retention.

## Install And Setup

- Install with `composer require capell-app/search` in the host Capell application.
- Run migrations through the host application package install flow.
- In this repository, verify package changes with `vendor/bin/pest`; do not use `php artisan`.

## Admin And Access

- None proven in this package directory.

- Gate: SearchOverviewStatsWidget: `admin`, `super_admin`
- Gate: TopSearchesWidget: `admin`, `super_admin`
- Gate: TrendingSearchesWidget: `admin`, `super_admin`
- Gate: ZeroResultSearchesWidget: `admin`, `super_admin`

## Common Pitfalls

- Database driver config must point at searchable columns that exist.
- Minimum query length defaults to 2 characters.
- Disable logging or hashing according to privacy requirements.
- Run log purge if retention needs enforcement.

## Docs

- [credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
- [drivers-and-logging.md](docs/drivers-and-logging.md)
- [overview.md](docs/overview.md)
- [search.md](docs/search.md)

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/search/tests --configuration=phpunit.xml
```

## Maintenance Notes

- Put behaviour changes in `src/Actions/`; UI classes, commands, and controllers should call actions instead of owning domain logic.
- Use package `Data` classes at boundaries instead of passing anonymous arrays between layers.
- Use backed enums for persisted values and enum labels for Filament options.
