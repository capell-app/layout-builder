# GA4 Reports

GA4 Reports 4 dashboard reporting for Capell.

## At A Glance

- Package: `capell-app/ga4-reports`
- Namespace: `Capell\GA4Reports\`
- Surfaces: Filament admin, console, database
- Service providers: `packages/ga4-reports/src/Providers/AdminServiceProvider.php`, `packages/ga4-reports/src/Providers/GA4ReportsServiceProvider.php`
- Capell dependencies: `capell-app/admin`, `capell-app/core`
- Third-party dependencies: `lorisleiva/laravel-actions`, `spatie/laravel-data`, `spatie/laravel-package-tools`

## What It Adds

GA4 Reports 4 dashboard reporting for Capell.

- GA4 settings, sync runs, daily metrics, and page metrics.
- Actions for overview, trend, top-page, and sync workflows.
- Admin reporting surfaces for growth and analytics teams.

## Why It Matters

**For developers:** Keeps GA4 Reports package responsibilities isolated behind providers, actions, data objects, and package-owned resources where the package needs them.

**For teams:** Makes the Capell Growth capability easier to explain, install, and verify during package selection.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Admin](https://github.com/capell-app/admin)
- [Capell Core](https://github.com/capell-app/core)

**Open-source packages used here**

- [Laravel Actions](https://github.com/lorisleiva/laravel-actions) - single-purpose action classes that keep package workflows out of controllers and Filament resources.
- [Spatie Laravel Data](https://github.com/spatie/laravel-data) - typed data objects for package boundaries, form state, settings, and structured results.
- [Spatie Laravel Package Tools](https://github.com/spatie/laravel-package-tools) - Laravel package bootstrapping for config, migrations, commands, translations, and service provider setup.

**Linked package previews**

[![Laravel Actions GitHub preview](https://opengraph.githubassets.com/capell-readme/lorisleiva/laravel-actions)](https://github.com/lorisleiva/laravel-actions)

[![Spatie Laravel Data GitHub preview](https://opengraph.githubassets.com/capell-readme/spatie/laravel-data)](https://github.com/spatie/laravel-data)

[![Spatie Laravel Package Tools GitHub preview](https://opengraph.githubassets.com/capell-readme/spatie/laravel-package-tools)](https://github.com/spatie/laravel-package-tools)

## Code Map

| Area      | Path                                 | Purpose                                                             |
| --------- | ------------------------------------ | ------------------------------------------------------------------- |
| Actions   | `packages/ga4-reports/src/Actions`   | Domain operations. Test these directly where possible.              |
| Data      | `packages/ga4-reports/src/Data`      | Structured payloads, form state, view models, and integration data. |
| Models    | `packages/ga4-reports/src/Models`    | Eloquent records owned by the package.                              |
| Filament  | `packages/ga4-reports/src/Filament`  | Admin resources, pages, widgets, and settings UI.                   |
| Providers | `packages/ga4-reports/src/Providers` | Registration, extension hooks, routes, migrations, and resources.   |
| Resources | `packages/ga4-reports/resources`     | Views, translations, assets, and package resources.                 |
| Config    | `packages/ga4-reports/config`        | Package configuration and publishable config.                       |
| Database  | `packages/ga4-reports/database`      | Migrations, seeders, and settings migrations.                       |
| Tests     | `packages/ga4-reports/tests`         | Package-level Pest coverage.                                        |

## Admin Surface

- Pages: `GA4ReportsPage`.
- Widgets: `BuildsGA4ReportsDashboardWindow`, `GA4ReportsOverviewStatsWidget`, `GA4ReportsSetupStatusWidget`, `GA4ReportsTopPagesTableWidget`, `GA4ReportsTopPagesWidget`, `GA4ReportsTrafficTrendWidget`.
- Settings: `GA4ReportsSettings`, `GA4ReportsSettingsMigrationProvider`.

## Commands

- `ga4-reports:sync` (packages/ga4-reports/src/Console/Commands/SyncGA4ReportsCommand.php)

## Data And Persistence

- Models: `GA4ReportsDailyMetric`, `GA4ReportsPageMetric`, `GA4ReportsSyncRun`.
- Migrations: `2026_05_10_190852_01_create_ga4_reports_daily_metrics_table.php`, `2026_05_10_190852_02_create_ga4_reports_page_metrics_table.php`, `2026_05_10_190852_03_create_ga4_reports_sync_runs_table.php`.
- Config: `packages/ga4-reports/config/capell-ga4-reports.php`.
- Data objects live in `src/Data/`; use them for payloads, form state, and view models.

## Extension Points

- Contracts: `GA4ReportsDataClientInterface`.
- Register Capell extension points, routes, migrations, settings, render hooks, and resources from service providers.

## Install And Setup

- Install with `composer require capell-app/ga4-reports` in the host Capell application.
- Run migrations through the host application package install flow.
- In this repository, verify package changes with `vendor/bin/pest`; do not use `php artisan`.

## Docs

- [credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
- [data-client.md](docs/data-client.md)

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/ga4-reports/tests --configuration=phpunit.xml
```

## Maintenance Notes

- Put behaviour changes in `src/Actions/`; UI classes, commands, and controllers should call actions instead of owning domain logic.
- Use package `Data` classes at boundaries instead of passing anonymous arrays between layers.
