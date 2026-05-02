# Capell Backup

**Product group:** Capell Operations
**Tier:** Premium

Backup provides export, import, and restore foundations for Capell content packages.

It is designed for moving pages and sites between environments without leaking package internals into core. The package writes deterministic ZIP archives with manifests, payload JSON, media binaries, and integrity checks, then imports them through a reviewable resolution flow.

## When to install it

Install Backup when a project needs content portability, recovery workflows, or a controlled path for importing content from another Capell environment.

## Quick install

```bash
composer require capell-app/backup
php artisan migrate
php artisan optimize:clear
```

## What developers get

| Area    | Capability                                                                                           |
| ------- | ---------------------------------------------------------------------------------------------------- |
| Export  | Page and site package archives with manifests, relation descriptors, media references, and checksums |
| Import  | Verified package reads, relation matching, URL collision checks, media ingest, and session tracking  |
| Review  | Page review rows, relation resolution rows, validation summaries, and retry/cancel actions           |
| Restore | Restore-session model and service placeholders for full-environment recovery                         |

## Import flow

1. `PackageReader` opens the archive, enforces configured size limits, and verifies every checksum from `integrity.json`.
2. `ResolutionMapBuilder` matches shared relations such as layouts, types, sites, and media against local records.
3. `BuildPageReviewRows` and `BuildRelationResolveRowsAction` prepare human-reviewable decisions.
4. `ExecuteImportPlanJob` applies the reviewed plan through `PageImportService`.

## Configuration

`config/backup.php` controls queue, disk, storage paths, size limits, and notification defaults.

| Key                                               | Purpose                                           |
| ------------------------------------------------- | ------------------------------------------------- |
| `queue.connection`, `queue.name`                  | Queue target for import and restore jobs          |
| `disk`                                            | Filesystem disk used for working archives         |
| `paths.imports`, `paths.exports`, `paths.working` | Package storage locations                         |
| `limits.*`                                        | Metadata, payload, media, and archive size guards |
| `notifications.*`                                 | Completion/failure notification defaults          |

## Extension points

- Bind `BackupContextResolver` to wrap export/import writes in workspace or tenant context.
- Bind `BackupRowContributor` to add package-owned attributes to exported rows.
- Bind `PageCollisionDetector` to customise URL conflict rules.
- Extend `RelationMatchResolverRegistry` with package-specific relation matchers.

## Reference

See [Backup reference](docs/backup.md) for the package shape, models, services, and safety guarantees.
