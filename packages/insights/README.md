# Insights

Insights records first-party visits, events, consent decisions, page views, clicks, and journey data for Capell sites.

## At A Glance

- Package: `capell-app/insights`
- Namespace: `Capell\Insights\`
- Surfaces: Filament admin, console, HTTP, database
- Service providers: `packages/insights/src/Providers/AdminServiceProvider.php`, `packages/insights/src/Providers/InsightsServiceProvider.php`
- Capell dependencies: `capell-app/admin`, `capell-app/core`, `capell-app/frontend`
- Third-party dependencies: `lorisleiva/laravel-actions`, `spatie/laravel-data`, `spatie/laravel-package-tools`

## What It Adds

Insights records first-party visits, events, consent decisions, page views, clicks, and journey data for Capell sites.

- Frontend beacon endpoints for events and consent.
- Render hook that can register the tracker.
- Dashboard widgets for overview stats, popular pages, top actions, journeys, and trending pages.
- Settings schema for insights retention and behaviour.

## Why It Matters

**For developers:** Keeps insights in Laravel actions and data objects, with explicit consent enums and configurable routes.

**For teams:** Gives site operators practical traffic and journey insight without sending the workflow through an external dashboard first.

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

- Insights overview dashboard widgets.
- Popular pages widget.
- Recent journeys widget.
- Insights settings screen.
- Frontend page with tracker active.

## Technical Shape

- InsightsServiceProvider and AdminServiceProvider register routes, settings, and widgets.
- Config file: capell-insights.php.
- Routes: POST capell/insights/events and POST capell/insights/consent by default.
- Models: InsightsVisit, InsightsConsent, InsightsEvent.
- Actions record page views, clicks, custom events, and consent updates.
- PurgeInsightsDataCommand supports retention cleanup.

## Code Map

| Area      | Path                              | Purpose                                                             |
| --------- | --------------------------------- | ------------------------------------------------------------------- |
| Actions   | `packages/insights/src/Actions`   | Domain operations. Test these directly where possible.              |
| Data      | `packages/insights/src/Data`      | Structured payloads, form state, view models, and integration data. |
| Enums     | `packages/insights/src/Enums`     | Persisted states and Filament option values.                        |
| Models    | `packages/insights/src/Models`    | Eloquent records owned by the package.                              |
| Filament  | `packages/insights/src/Filament`  | Admin resources, pages, widgets, and settings UI.                   |
| HTTP      | `packages/insights/src/Http`      | Controllers, middleware, and request handling.                      |
| Providers | `packages/insights/src/Providers` | Registration, extension hooks, routes, migrations, and resources.   |
| Resources | `packages/insights/resources`     | Views, translations, assets, and package resources.                 |
| Routes    | `packages/insights/routes`        | Route files loaded by the service provider.                         |
| Config    | `packages/insights/config`        | Package configuration and publishable config.                       |
| Database  | `packages/insights/database`      | Migrations, seeders, and settings migrations.                       |
| Tests     | `packages/insights/tests`         | Package-level Pest coverage.                                        |

## Admin Surface

- Pages: `InsightsPage`.
- Widgets: `BuildsInsightsDashboardWindow`, `InsightsOverviewStatsWidget`, `LiveInsightsStatsWidget`, `PopularPagesWidget`, `RecentJourneysWidget`, `TopActionsWidget`, `TrendingPagesWidget`.
- Settings: `InsightsSettings`, `InsightsSettingsMigrationProvider`.

## Runtime Surface

- Controllers: `InsightsBeaconController`, `InsightsConsentController`.
- Routes: `packages/insights/routes/web.php`.

## Commands

- `insights:purge {--days= : Override insights retention days}` (packages/insights/src/Console/Commands/PurgeInsightsDataCommand.php)

## Data And Persistence

- insights_visits stores site, language, consent, landing URL, hashed visitor data, and start time.
- insights_consents stores consent decisions for a visit.
- insights_events stores event type, URL, path, metadata, and occurrence time.
- Visits relate to events and consents.
- Retention is governed by retention_days and purge actions.

- Models: `InsightsConsent`, `InsightsEvent`, `InsightsVisit`.
- Migrations: `2026_05_10_190855_01_create_insights_visits_table.php`, `2026_05_10_190855_02_create_insights_consents_table.php`, `2026_05_10_190855_03_create_insights_events_table.php`, `2026_05_10_190855_04_add_insights_reporting_indexes.php`, `2026_05_10_190855_05_import_legacy_page_views.php`, `2026_05_10_190855_06_add_page_url_hit_columns.php`.
- Config: `packages/insights/config/capell-insights.php`.
- Data objects live in `src/Data/`; use them for payloads, form state, and view models.

## Extension Points

- Register Capell extension points, routes, migrations, settings, render hooks, and resources from service providers.

## Install Impact

- Adds insights tables and settings migration.
- Adds beacon and consent public POST routes.
- Adds dashboard widgets and insights settings.
- Uses capell-insights config keys for route prefix, consent, hashing, retention, and ignored paths.
- May need scheduled cleanup if retention should be enforced automatically.

## Install And Setup

- Install with `composer require capell-app/insights` in the host Capell application.
- Run migrations through the host application package install flow.
- In this repository, verify package changes with `vendor/bin/pest`; do not use `php artisan`.

## Admin And Access

- None proven in this package directory.

- Gate: InsightsOverviewStatsWidget: `admin`, `super_admin`
- Gate: PopularPagesWidget: `admin`, `super_admin`
- Gate: RecentJourneysWidget: `admin`, `super_admin`
- Gate: TopActionsWidget: `admin`, `super_admin`
- Gate: TrendingPagesWidget: `admin`, `super_admin`

## Common Pitfalls

- Exclude admin, Livewire, and insights routes from tracking.
- Set hash_salt deliberately before production data is recorded.
- Consent settings must match the site privacy policy.

## Docs

- [credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
- [overview.md](docs/overview.md)
- [tracking-and-consent.md](docs/tracking-and-consent.md)

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/insights/tests --configuration=phpunit.xml
```

## Maintenance Notes

- Put behaviour changes in `src/Actions/`; UI classes, commands, and controllers should call actions instead of owning domain logic.
- Use package `Data` classes at boundaries instead of passing anonymous arrays between layers.
- Use backed enums for persisted values and enum labels for Filament options.
