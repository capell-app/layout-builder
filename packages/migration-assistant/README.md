# Migration Assistant

MigrationAssistant export, import, and rollback report workflows for Capell.

## At A Glance

- Package: `capell-app/migration-assistant`
- Namespace: `Capell\MigrationAssistant\`
- Surfaces: Filament admin, queue, database
- Service providers: `packages/migration-assistant/src/Providers/MigrationAssistantServiceProvider.php`
- Capell dependencies: `capell-app/admin`, `capell-app/core`
- Third-party dependencies: `lorisleiva/laravel-actions`, `spatie/laravel-package-tools`

## What It Adds

- MigrationAssistant export, import, and rollback report workflows for Capell.
- Admin resources: `ImportSessionResource`.

## Why It Matters

**For developers:** Separates migration work into services, actions, DTOs, jobs, events, source readers, target registries, and resolver contracts so package and flat-file data can be moved with explicit ownership rules.

**For teams:** Supports controlled migration workflows where content, media, relationships, source files, and rollback evidence can be reviewed before and after import.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Admin](https://github.com/capell-app/admin)
- [Capell Core](https://github.com/capell-app/core)

**Open-source packages used here**

- [Laravel Actions](https://github.com/lorisleiva/laravel-actions) - single-purpose action classes that keep package workflows out of controllers and Filament resources.
- [Spatie Laravel Package Tools](https://github.com/spatie/laravel-package-tools) - Laravel package bootstrapping for config, migrations, commands, translations, and service provider setup.

**Linked package previews**

[![Laravel Actions GitHub preview](https://opengraph.githubassets.com/capell-readme/lorisleiva/laravel-actions)](https://github.com/lorisleiva/laravel-actions)

[![Spatie Laravel Package Tools GitHub preview](https://opengraph.githubassets.com/capell-readme/spatie/laravel-package-tools)](https://github.com/spatie/laravel-package-tools)

## Screens And Workflow

Screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment.

- Import session index or host admin surface.
- Import validation summary.
- Relation resolution review.
- Rollback report view.
- Package export intent screen.

## Technical Shape

- MigrationAssistantServiceProvider registers the package.
- Config file: migration-assistant.php.
- Migrations create import_rollback_dashboard-dashboard_reports and import_sessions.
- Jobs execute import plans.
- Events report import completed or failed.
- Services cover package reading, writing, CSV/XML reading, mapping, validation, relation resolution, media ingest, preview, and rollback reporting.
- WordPress WXR support is intentionally provided by the separate `capell-app/wordpress-importer` package, which registers a source reader with MigrationAssistant.

## Code Map

| Area      | Path                                         | Purpose                                                             |
| --------- | -------------------------------------------- | ------------------------------------------------------------------- |
| Actions   | `packages/migration-assistant/src/Actions`   | Domain operations. Test these directly where possible.              |
| Data      | `packages/migration-assistant/src/Data`      | Structured payloads, form state, view models, and integration data. |
| Enums     | `packages/migration-assistant/src/Enums`     | Persisted states and Filament option values.                        |
| Models    | `packages/migration-assistant/src/Models`    | Eloquent records owned by the package.                              |
| Filament  | `packages/migration-assistant/src/Filament`  | Admin resources, pages, widgets, and settings UI.                   |
| Jobs      | `packages/migration-assistant/src/Jobs`      | Queued work and async side effects.                                 |
| Providers | `packages/migration-assistant/src/Providers` | Registration, extension hooks, routes, migrations, and resources.   |
| Resources | `packages/migration-assistant/resources`     | Views, translations, assets, and package resources.                 |
| Config    | `packages/migration-assistant/config`        | Package configuration and publishable config.                       |
| Database  | `packages/migration-assistant/database`      | Migrations, seeders, and settings migrations.                       |
| Tests     | `packages/migration-assistant/tests`         | Package-level Pest coverage.                                        |

## Admin Surface

- Resources: `ImportSessionResource`.
- Pages: `ImportSitesPage`, `ListImportSessions`, `ViewImportSession`.

## Runtime Surface

- Jobs: `ExecuteImportPlanJob`.

## Data And Persistence

- import_rollback_dashboard-dashboard_reports stores the import session, created model ids, source filename/checksum, summary counts, executing user/time, and manual rollback instructions.
- import_sessions stores import kind, status, manifest, and result summary.
- Retention and deletion rules should be verified against the host application policy.

- Models: `ImportRollbackReport`, `ImportSession`.
- Migrations: `2026_05_10_190859_01_create_import_sessions_table.php`, `2026_05_10_190859_02_create_import_rollback_dashboard-dashboard_reports_table.php`.
- Config: `packages/migration-assistant/config/migration-assistant.php`.
- Data objects live in `src/Data/`; use them for payloads, form state, and view models.

## Extension Points

- Contracts: `ImportSessionSubNavigationExtender`, `ImportSourceReader`, `MigrationAssistantContextResolver`, `MigrationAssistantRowContributor`, `NullMigrationAssistantContextResolver`, `NullMigrationAssistantRowContributor`, `NullPageCollisionDetector`, `PageCollisionDetector`.
- Events: `ImportCompleted`, `ImportFailed`.
- Listeners: `SendImportSessionNotifications`.
- Register Capell extension points, routes, migrations, settings, render hooks, and resources from service providers.

## Install Impact

- Adds import_rollback_dashboard-dashboard_reports and import_sessions tables.
- Adds migration-assistant queue configuration.
- Uses disk and path config for imports, exports, and working files.
- May require queue workers for long-running imports.
- No public routes are registered by this package.

## Install And Setup

- Install with `composer require capell-app/migration-assistant` in the host Capell application.
- Run migrations through the host application package install flow.
- In this repository, verify package changes with `vendor/bin/pest`; do not use `php artisan`.

## Admin And Access

- None proven in this package directory.

- Policy: OwnershipMap (packages/migration-assistant/src/Policy/OwnershipMap.php)

## Common Pitfalls

- Configure MIGRATOR_QUEUE and MIGRATOR_DISK before large imports.
- Check upload and package size limits before importing client archives.
- Run queue workers before testing async import jobs.
- Review relation resolution before applying imported data.

## Docs

- [credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
- [extension-points.md](docs/extension-points.md)
- [import-export-workflow.md](docs/import-export-workflow.md)
- [migration-assistant.md](docs/migration-assistant.md)
- [overview.md](docs/overview.md)

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/migration-assistant/tests --configuration=phpunit.xml
```

## Maintenance Notes

- Put behaviour changes in `src/Actions/`; UI classes, commands, and controllers should call actions instead of owning domain logic.
- Use package `Data` classes at boundaries instead of passing anonymous arrays between layers.
- Use backed enums for persisted values and enum labels for Filament options.
