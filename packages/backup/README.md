# Backup

Status: **Available, schema-owning** · Kind: **package** · Tier: **premium** · Bundle: **operations** · Contexts: **admin, console** · Product group: **Capell Operations**

## What This Plugin Adds

Backup provides package export, import, restore, WordPress import, dependency graph, and validation workflows for Capell content operations.

- Import session tracking.
- Backup restore tracking.
- Package reader/writer services.
- Import validation and relation resolution actions.
- Queued import jobs.

## Why It Matters

**For developers:** Separates export/import work into services, actions, DTOs, jobs, events, and resolver contracts so package data can be moved with explicit ownership rules.

**For teams:** Supports controlled migration and recovery workflows where content, media, and relationships need review before import.

## Screens And Workflow

Screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment.

- Import session index or host admin surface.
- Import validation summary.
- Relation resolution review.
- Restore status view.
- Package export intent screen.

## Technical Shape

- BackupServiceProvider registers the package.
- Config file: backup.php.
- Migrations create backup_restores and import_sessions.
- Jobs execute import plans and WordPress imports.
- Events report import completed or failed.
- Services cover package reading, writing, validation, relation resolution, media ingest, and restore.

## Data Model

- backup_restores stores restore UUID, user, status, and source archive path.
- import_sessions stores import kind, status, manifest, and result summary.
- Retention and deletion rules should be verified against the host application policy.

## Install Impact

- Adds backup_restores and import_sessions tables.
- Adds backup queue configuration.
- Uses disk and path config for imports, exports, and working files.
- May require queue workers for long-running imports.
- No public routes are registered by this package.

## Commands

- None proven in this package directory.

## Admin And Access

- None proven in this package directory.

- Policy: OwnershipMap (packages/backup/src/Policy/OwnershipMap.php)

## Common Pitfalls

- Configure BACKUP_QUEUE and BACKUP_DISK before large imports.
- Check upload and package size limits before importing client archives.
- Run queue workers before testing async import jobs.
- Review relation resolution before applying imported data.

## Quick Start

1. Install the package with `composer require capell-app/backup`.
2. Run the package migrations or the Capell package installer required by the host app.
3. Open the new admin surface or integration point and verify the result.

## Next Steps

- [docs/overview.md](docs/overview.md)
- [../workspaces/README.md](../workspaces/README.md)
- [../mosaic/README.md](../mosaic/README.md)
