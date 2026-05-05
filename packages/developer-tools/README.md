# Developer Tools

Status: **Available, audited schema** · Kind: **package** · Tier: **premium** · Bundle: **operations** · Contexts: **admin, console** · Product group: **Capell Operations**

## What This Plugin Adds

Developer Tools adds operational diagnostics for cache, configuration drift, migrations, packages, registries, queues, permissions, setup health, and Tailwind build status.

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

## Screens And Workflow

Screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment.

- Developer tools dashboard.
- Command palette page.
- System health page.
- Permission audit page.
- Queue health page.
- Health widgets on the admin dashboard.

## Technical Shape

- DeveloperToolsServiceProvider and AdminServiceProvider register admin pages and widgets.
- AdminServiceProvider registers palette command providers through the `capell.developer-tools.command-palette-provider` container tag.
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

- Adds a Developer Tools command palette page for trusted `capell:*` Artisan commands and operational navigation.
- Discovers commands dynamically from tagged providers so newly installed Capell commands can appear without hard-coding.
- Authorizes each command with command-specific abilities when provided.
- Dangerous commands such as install, setup, upgrade, and demo are marked dangerous.
- Cache, clear, and publish commands require confirmation.
- Command parameters are derived from Artisan argument and option definitions.

## Command Palette

Developer Tools provides an operational command palette when the package is installed:

- `developer-tools.open`: open the developer tools workspace.
- `developer-tools.system-health`: open system health.
- `developer-tools.queue-health`: open failed job / queue health reporting.
- `artisan.capell:*`: dynamic entries for trusted Capell Artisan commands.

Palette execution is handled inside Developer Tools so role permissions, confirmation requirements, parameter validation, user feedback, and audit records stay close to the operational commands they protect. Custom packages can add commands by binding a provider and tagging it with `capell.developer-tools.command-palette-provider`.

## Admin And Access

- DeveloperToolsPage (packages/developer-tools/src/Filament/Pages/DeveloperToolsPage.php, slug `developer-tools`)
- CommandPalettePage (packages/developer-tools/src/Filament/Pages/CommandPalettePage.php, slug `developer-tools/command-palette`)
- PermissionAuditPage (packages/developer-tools/src/Filament/Pages/PermissionAuditPage.php, slug `reports/permission-audit`)
- QueueHealthPage (packages/developer-tools/src/Filament/Pages/QueueHealthPage.php, slug `reports/queue-health`)
- SystemHealthPage (packages/developer-tools/src/Filament/Pages/SystemHealthPage.php, slug `system-health`)

- Gate: CacheHealthWidgetAbstract: `admin`, `super_admin`
- Gate: ConfigDriftWidgetAbstract: `super_admin`
- Gate: ContentHealthWidgetAbstract: `editor`, `admin`, `super_admin`
- Gate: DeveloperToolsPage: Gate `accessDeveloperTools`, `viewDeveloperTools`
- Gate: MigrationsHealthWidgetAbstract: `super_admin`
- Gate: PackagesInstalledWidgetAbstract: `super_admin`
- Gate: QueueHealthPage: Gate `accessDeveloperTools`, `viewDeveloperTools`
- Gate: RegistryHealthWidgetAbstract: `super_admin`
- Gate: SetupHealthWidgetAbstract: settings-gated only
- Gate: SiteHealthWidgetAbstract: settings-gated only
- Gate: TailwindBuildStatusWidgetAbstract: `super_admin`

## Common Pitfalls

- Some checks depend on host-app conventions and may need configuration.
- Queue health needs access to failed job data.
- Permission audit output is only useful when permissions are registered.

## Quick Start

1. Install the package with `composer require capell-app/developer-tools`.
2. Register the package provider through Composer discovery and clear cached config if the host app uses config caching.
3. Open the new admin surface or integration point and verify the result.

## Next Steps

- [docs/overview.md](docs/overview.md)
- [../migrator/README.md](../migrator/README.md)
- [../authentication-log/README.md](../authentication-log/README.md)
