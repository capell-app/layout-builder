# Diagnostics

Status: **Available, audited schema** · Kind: **package** · Tier: **premium** · Bundle: **operations** · Contexts: **admin, console** · Product group: **Capell Operations**

## What This Plugin Adds

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

## Data Model

- This package owns the `command_palette_runs` table for command palette audit history.
- It reads existing Laravel and Capell state such as config, migrations, failed jobs, permissions, packages, registries, and Tailwind outputs.

## Install Impact

- Adds admin pages for developer diagnostics.
- Adds dashboard widgets.
- Adds the `command_palette_runs` audit table.
- No public routes are registered by this package.

## Commands

- Adds a Diagnostics command palette page for trusted `capell:*` Artisan commands and operational navigation.
- Discovers commands dynamically from tagged providers so newly installed Capell commands can appear without hard-coding.
- Authorizes each command with command-specific abilities when provided.
- Dangerous commands such as install, setup, upgrade, and demo are marked dangerous.
- Cache, clear, and publish commands require confirmation.
- Command parameters are derived from Artisan argument and option definitions.

## Command Palette

Diagnostics provides an operational command palette when the package is installed:

- `diagnostics.open`: open the developer tools workspace.
- `diagnostics.system-health`: open system health.
- `diagnostics.queue-health`: open failed job / queue health reporting.
- `artisan.capell:*`: dynamic entries for trusted Capell Artisan commands.

Palette execution is handled inside Diagnostics so role permissions, confirmation requirements, parameter validation, user feedback, and audit records stay close to the operational commands they protect. Custom packages can add commands by binding a provider and tagging it with `capell.diagnostics.command-palette-provider`.

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

## Quick Start

1. Install the package with `composer require capell-app/diagnostics`.
2. Register the package provider through Composer discovery and clear cached config if the host app uses config caching.
3. Open the new admin surface or integration point and verify the result.

## Next Steps

- [docs/overview.md](docs/overview.md)
- [../migration-assistant/README.md](../migration-assistant/README.md)
- [../login-audit/README.md](../login-audit/README.md)
- [docs/credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
