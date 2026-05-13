# Dashboard Reports

Generic CMS reporting widgets for Capell dashboards.

## At A Glance

- Package: `capell-app/dashboard-reports`
- Namespace: `Capell\DashboardReports\`
- Surfaces: Filament admin
- Service providers: `packages/dashboard-reports/src/Providers/AdminServiceProvider.php`, `packages/dashboard-reports/src/Providers/DashboardReportsServiceProvider.php`
- Capell dependencies: `capell-app/admin`, `capell-app/core`
- Third-party dependencies: `lorisleiva/laravel-actions`, `spatie/laravel-data`, `spatie/laravel-package-tools`

## What It Adds

Generic CMS reporting widgets for Capell dashboards.

- Shared dashboard reporting widgets for Capell admin screens.
- Service providers for package and admin registration.
- A reporting foundation that other operations packages can build on.

## Why It Matters

**For developers:** Keeps Dashboard Reports package responsibilities isolated behind providers, actions, data objects, and package-owned resources where the package needs them.

**For teams:** Makes the Capell Operations capability easier to explain, install, and verify during package selection.

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

| Area      | Path                                       | Purpose                                                             |
| --------- | ------------------------------------------ | ------------------------------------------------------------------- |
| Actions   | `packages/dashboard-reports/src/Actions`   | Domain operations. Test these directly where possible.              |
| Data      | `packages/dashboard-reports/src/Data`      | Structured payloads, form state, view models, and integration data. |
| Filament  | `packages/dashboard-reports/src/Filament`  | Admin resources, pages, widgets, and settings UI.                   |
| Providers | `packages/dashboard-reports/src/Providers` | Registration, extension hooks, routes, migrations, and resources.   |
| Resources | `packages/dashboard-reports/resources`     | Views, translations, assets, and package resources.                 |
| Tests     | `packages/dashboard-reports/tests`         | Package-level Pest coverage.                                        |

## Admin Surface

- Widgets: `ContentHealthWidget`, `PublishingTrendChartWidget`.

## Data And Persistence

- Data objects live in `src/Data/`; use them for payloads, form state, and view models.

## Extension Points

- Register Capell extension points, routes, migrations, settings, render hooks, and resources from service providers.

## Install And Setup

- Install with `composer require capell-app/dashboard-reports` in the host Capell application.
- In this repository, verify package changes with `vendor/bin/pest`; do not use `php artisan`.

## Docs

- [credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/dashboard-reports/tests --configuration=phpunit.xml
```

## Maintenance Notes

- Put behaviour changes in `src/Actions/`; UI classes, commands, and controllers should call actions instead of owning domain logic.
- Use package `Data` classes at boundaries instead of passing anonymous arrays between layers.
