# Diagnostics

Diagnostics adds operational diagnostics for cache, configuration drift, migrations, packages, registries, queues, permissions, setup health, and Tailwind build status.

## At A Glance

- Package: `capell-app/diagnostics`
- Namespace: `Capell\Diagnostics\`
- Surfaces: Filament admin, database
- Service providers: `packages/diagnostics/src/Providers/AdminServiceProvider.php`, `packages/diagnostics/src/Providers/DiagnosticsServiceProvider.php`
- Capell dependencies: `capell-app/admin`, `capell-app/core`, `capell-app/html-cache`
- Third-party dependencies: `lorisleiva/laravel-actions`, `spatie/laravel-data`

## What It Adds

Diagnostics adds operational diagnostics for cache, configuration drift, migrations, packages, registries, queues, permissions, setup health, and Tailwind build status.

- Command palette admin page.
- System health admin pages.
- Developer tools dashboard page.
- Permission audit report.
- Queue health report.
- Health widgets for cache, content, migrations, registry, setup, packages, and Tailwind.
- Secure command palette discovery, execution, feedback, and audit logging for developer tools, system health, queue health, and trusted `capell:*` Artisan operations.

## Why It Matters

**For developers:** Keeps diagnostics in actions and data objects so admin pages can show health information without hard-coded checks in the UI.

**For teams:** Helps operators and agencies see setup problems before they become publishing or deployment issues.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Admin](https://github.com/capell-app/admin)
- [Capell Core](https://github.com/capell-app/core)

**Open-source packages used here**

- [Laravel Actions](https://github.com/lorisleiva/laravel-actions) - single-purpose action classes that keep package workflows out of controllers and Filament resources.
- [Spatie Laravel Data](https://github.com/spatie/laravel-data) - typed data objects for package boundaries, form state, settings, and structured results.

**Linked package previews**

[![Laravel Actions GitHub preview](https://opengraph.githubassets.com/capell-readme/lorisleiva/laravel-actions)](https://github.com/lorisleiva/laravel-actions)

[![Spatie Laravel Data GitHub preview](https://opengraph.githubassets.com/capell-readme/spatie/laravel-data)](https://github.com/spatie/laravel-data)

## Screens And Workflow

Screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment.

- Developer tools dashboard.
- Command palette page.
- System health page.
- Permission audit page.
- Queue health page.
- Health widgets on the admin dashboard.

## Technical Shape

- DiagnosticsServiceProvider and AdminServiceProvider register admin pages and widgets.
- AdminServiceProvider registers palette command providers through the `capell.diagnostics.command-palette-provider` container tag.
- Command palette actions discover providers dynamically, authorize commands, validate parameters, execute navigation or Artisan commands, and record audit runs.
- Actions build each health report.
- Data objects describe report rows and dashboard state.
- FailedJob model supports queue reporting.
- CommandPaletteRun model records command palette execution history.

## Code Map

| Area      | Path                                 | Purpose                                                             |
| --------- | ------------------------------------ | ------------------------------------------------------------------- |
| Actions   | `packages/diagnostics/src/Actions`   | Domain operations. Test these directly where possible.              |
| Data      | `packages/diagnostics/src/Data`      | Structured payloads, form state, view models, and integration data. |
| Enums     | `packages/diagnostics/src/Enums`     | Persisted states and Filament option values.                        |
| Models    | `packages/diagnostics/src/Models`    | Eloquent records owned by the package.                              |
| Filament  | `packages/diagnostics/src/Filament`  | Admin resources, pages, widgets, and settings UI.                   |
| Providers | `packages/diagnostics/src/Providers` | Registration, extension hooks, routes, migrations, and resources.   |
| Resources | `packages/diagnostics/resources`     | Views, translations, assets, and package resources.                 |
| Database  | `packages/diagnostics/database`      | Migrations, seeders, and settings migrations.                       |
| Tests     | `packages/diagnostics/tests`         | Package-level Pest coverage.                                        |

## Admin Surface

- Pages: `CommandPalettePage`, `DiagnosticsPage`, `PermissionAuditPage`, `PermissionAuditTable`, `QueueHealthPage`, `QueueHealthTable`, `SystemHealthPage`.
- Widgets: `AlertsWidgetAbstract`, `CacheHealthWidgetAbstract`, `ConfigDriftWidgetAbstract`, `ContentGraphHealthWidgetAbstract`, `ContentHealthWidgetAbstract`, `MigrationsHealthWidgetAbstract`, `PackagesInstalledWidgetAbstract`, `RegistryHealthWidgetAbstract`, `SetupHealthWidgetAbstract`, `SiteHealthWidgetAbstract`, `TailwindBuildStatusWidgetAbstract`.

## Data And Persistence

- This package owns the `command_palette_runs` table for command palette audit history.
- It reads existing Laravel and Capell state such as config, migrations, failed jobs, permissions, packages, registries, and Tailwind outputs.

- Models: `CommandPaletteRun`, `FailedJob`.
- Migrations: `2026_05_10_190846_01_create_command_palette_runs_table.php`.
- Data objects live in `src/Data/`; use them for payloads, form state, and view models.

## Extension Points

- Contracts: `CommandPaletteProvider`.
- Register Capell extension points, routes, migrations, settings, render hooks, and resources from service providers.

## Install Impact

- Adds admin pages for developer diagnostics.
- Adds dashboard widgets.
- Adds the `command_palette_runs` audit table.
- No public routes are registered by this package.

## Install And Setup

- Install with `composer require capell-app/diagnostics` in the host Capell application.
- Run migrations through the host application package install flow.
- In this repository, verify package changes with `vendor/bin/pest`; do not use `php artisan`.

## Admin And Access

- DiagnosticsPage (packages/diagnostics/src/Filament/Pages/DiagnosticsPage.php, slug `diagnostics`)
- CommandPalettePage (packages/diagnostics/src/Filament/Pages/CommandPalettePage.php, slug `diagnostics/command-palette`)
- PermissionAuditPage (packages/diagnostics/src/Filament/Pages/PermissionAuditPage.php, slug `dashboard-dashboard_reports/permission-audit`)
- QueueHealthPage (packages/diagnostics/src/Filament/Pages/QueueHealthPage.php, slug `dashboard-dashboard_reports/queue-health`)
- SystemHealthPage (packages/diagnostics/src/Filament/Pages/SystemHealthPage.php, slug `system-health`)

- Gate: CacheHealthWidgetAbstract: `admin`, `super_admin`
- Gate: ConfigDriftWidgetAbstract: `super_admin`
- Gate: ContentHealthWidgetAbstract: `editor`, `admin`, `super_admin`
- Gate: DiagnosticsPage: Gate `accessDiagnostics`, `viewDiagnostics`
- Gate: MigrationsHealthWidgetAbstract: `super_admin`
- Gate: PackagesInstalledWidgetAbstract: `super_admin`
- Gate: QueueHealthPage: Gate `accessDiagnostics`, `viewDiagnostics`
- Gate: RegistryHealthWidgetAbstract: `super_admin`
- Gate: SetupHealthWidgetAbstract: settings-gated only
- Gate: SiteHealthWidgetAbstract: settings-gated only
- Gate: TailwindBuildStatusWidgetAbstract: `super_admin`

## Common Pitfalls

- Some checks depend on host-app conventions and may need configuration.
- Queue health needs access to failed job data.
- Permission audit output is only useful when permissions are registered.

## Docs

- [command-palette.md](docs/command-palette.md)
- [credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
- [overview.md](docs/overview.md)

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/diagnostics/tests --configuration=phpunit.xml
```

## Maintenance Notes

- Put behaviour changes in `src/Actions/`; UI classes, commands, and controllers should call actions instead of owning domain logic.
- Use package `Data` classes at boundaries instead of passing anonymous arrays between layers.
- Use backed enums for persisted values and enum labels for Filament options.
