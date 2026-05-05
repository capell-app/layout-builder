# Migrator

Status: **Available, schema-owning** · Kind: **package** · Tier: **premium** · Bundle: **operations** · Contexts: **admin, console** · Product group: **Capell Operations**

## What This Package Adds

Migrator provides the Migration Assistant workflow: package export/import, CSV/XML source reads, field mapping, preview, validation, dependency graph review, relation resolution, media ingest, execution state, and rollback reports for Capell content operations.

- Import source contracts for packages that provide rows, columns, metadata, and suggested targets.
- Native CSV and XML readers using PHP's built-in file/XML tooling.
- Field mapping into Capell pages and types, with collection-like imports resolved through the target registry.
- Preview and validation summaries that show creates, skips, warnings, and blocking errors before execution.
- Import rollback reports with created model class/id pairs, imported URL/media counts, source filename/checksum, executing user/time, and manual rollback instructions.
- Import session tracking, notifications, retry/cancel flow, and queued execution.
- Package reader/writer services.
- Relation resolution, media ingest, dependency graph review, and Capell package import/export.

## Why It Matters

**For developers:** Separates migration work into services, actions, DTOs, jobs, events, source readers, target registries, and resolver contracts so package and flat-file data can be moved with explicit ownership rules.

**For teams:** Supports controlled migration workflows where content, media, relationships, source files, and rollback evidence can be reviewed before and after import.

## Screens And Workflow

Screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment.

- Import session index or host admin surface.
- Import validation summary.
- Relation resolution review.
- Rollback report view.
- Package export intent screen.

## Technical Shape

- MigratorServiceProvider registers the package.
- Config file: migrator.php.
- Migrations create import_rollback_reports and import_sessions.
- Jobs execute import plans.
- Events report import completed or failed.
- Services cover package reading, writing, CSV/XML reading, mapping, validation, relation resolution, media ingest, preview, and rollback reporting.
- WordPress WXR support is intentionally provided by the separate `capell-app/wordpress-importer` package, which registers a source reader with Migrator.

## Data Model

- import_rollback_reports stores the import session, created model ids, source filename/checksum, summary counts, executing user/time, and manual rollback instructions.
- import_sessions stores import kind, status, manifest, and result summary.
- Retention and deletion rules should be verified against the host application policy.

## Install Impact

- Adds import_rollback_reports and import_sessions tables.
- Adds migrator queue configuration.
- Uses disk and path config for imports, exports, and working files.
- May require queue workers for long-running imports.
- No public routes are registered by this package.

## Commands

- None proven in this package directory.

## Admin And Access

- None proven in this package directory.

- Policy: OwnershipMap (packages/migrator/src/Policy/OwnershipMap.php)

## Common Pitfalls

- Configure MIGRATOR_QUEUE and MIGRATOR_DISK before large imports.
- Check upload and package size limits before importing client archives.
- Run queue workers before testing async import jobs.
- Review relation resolution before applying imported data.

## Quick Start

1. Install the package with `composer require capell-app/migrator`.
2. Run the package migrations or the Capell package installer required by the host app.
3. Open the new admin surface or integration point and verify the result.

## Next Steps

- [docs/overview.md](docs/overview.md)
- [../workspaces/README.md](../workspaces/README.md)
- [../mosaic/README.md](../mosaic/README.md)
