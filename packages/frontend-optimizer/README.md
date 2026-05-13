# Frontend Optimizer

Profile-based CSS and JavaScript delivery for public Capell pages.

## At A Glance

- Package: `capell-app/frontend-optimizer`
- Namespace: `Capell\FrontendOptimizer\`
- Surfaces: queue, database
- Service providers: `packages/frontend-optimizer/src/Providers/FrontendOptimizerServiceProvider.php`
- Capell dependencies: `capell-app/core`, `capell-app/frontend`
- Third-party dependencies: `lorisleiva/laravel-actions`, `spatie/laravel-data`, `spatie/laravel-package-tools`, `symfony/process`

## What It Adds

- Profile-based CSS and JavaScript delivery for public Capell pages.

## Code Map

| Area      | Path                                        | Purpose                                                             |
| --------- | ------------------------------------------- | ------------------------------------------------------------------- |
| Actions   | `packages/frontend-optimizer/src/Actions`   | Domain operations. Test these directly where possible.              |
| Data      | `packages/frontend-optimizer/src/Data`      | Structured payloads, form state, view models, and integration data. |
| Enums     | `packages/frontend-optimizer/src/Enums`     | Persisted states and Filament option values.                        |
| Models    | `packages/frontend-optimizer/src/Models`    | Eloquent records owned by the package.                              |
| Jobs      | `packages/frontend-optimizer/src/Jobs`      | Queued work and async side effects.                                 |
| Providers | `packages/frontend-optimizer/src/Providers` | Registration, extension hooks, routes, migrations, and resources.   |
| Resources | `packages/frontend-optimizer/resources`     | Views, translations, assets, and package resources.                 |
| Config    | `packages/frontend-optimizer/config`        | Package configuration and publishable config.                       |
| Database  | `packages/frontend-optimizer/database`      | Migrations, seeders, and settings migrations.                       |
| Tests     | `packages/frontend-optimizer/tests`         | Package-level Pest coverage.                                        |

## Runtime Surface

- Jobs: `GenerateCriticalCssJob`.

## Data And Persistence

- Models: `FrontendOptimizationRun`, `FrontendRenderProfile`.
- Migrations: `2026_05_10_190851_01_create_frontend_optimizer_tables.php`.
- Config: `packages/frontend-optimizer/config/capell-frontend-optimizer.php`.
- Data objects live in `src/Data/`; use them for payloads, form state, and view models.

## Extension Points

- Contracts: `CriticalCssGenerator`.
- Register Capell extension points, routes, migrations, settings, render hooks, and resources from service providers.

## Install And Setup

- Install with `composer require capell-app/frontend-optimizer` in the host Capell application.
- Run migrations through the host application package install flow.
- In this repository, verify package changes with `vendor/bin/pest`; do not use `php artisan`.

## Docs

- [assets-and-render-profiles.md](docs/assets-and-render-profiles.md)

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/frontend-optimizer/tests --configuration=phpunit.xml
```

## Maintenance Notes

- Put behaviour changes in `src/Actions/`; UI classes, commands, and controllers should call actions instead of owning domain logic.
- Use package `Data` classes at boundaries instead of passing anonymous arrays between layers.
- Use backed enums for persisted values and enum labels for Filament options.
