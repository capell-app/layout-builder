# Backup Reference

Backup owns content package export/import mechanics for Capell. Core stays responsible for pages, sites, URLs, media contracts, and extension registries; Backup owns archive structure, import sessions, relation resolution, and recovery workflow state.

## Package archive

A Backup package is a ZIP archive containing:

| Entry                      | Purpose                                                                                        |
| -------------------------- | ---------------------------------------------------------------------------------------------- |
| `manifest.json`            | Package type, Capell version, source environment, counts, export note, and aggregate checksums |
| `integrity.json`           | Per-file checksums for every payload and media entry                                           |
| `pages/*.json`             | Page descriptors with attributes, owned page URLs, shared relation refs, and media bindings    |
| `relations/<group>/*.json` | Shared relation descriptors such as layouts, types, sites, domains, and media                  |
| `media/*`                  | Optional media binaries named by content checksum                                              |

`PackageWriter` sorts payload keys before writing so equivalent exports stay deterministic. `PackageReader` verifies file presence, per-entry checksums, and configured byte limits before exposing payload data to import services.

## Models

| Model           | Table             | Purpose                                                                                            |
| --------------- | ----------------- | -------------------------------------------------------------------------------------------------- |
| `ImportSession` | `import_sessions` | Tracks upload, validation, relation decisions, page decisions, execution result, and failure state |
| `BackupRestore` | `backup_restores` | Tracks full-environment restore jobs as the restore flow is expanded                               |

`ImportSessionKind` and `ImportSessionStatus` are backed enums for persisted import state.

## Export services

| Service                  | Responsibility                                                                   |
| ------------------------ | -------------------------------------------------------------------------------- |
| `PageExportService`      | Exports selected pages or whole sites into package archives                      |
| `DependencyGraphBuilder` | Walks selected roots and gathers required shared relations and media descriptors |
| `PayloadSerializer`      | Converts pages and shared relations into stable JSON payload entries             |
| `PackageWriter`          | Writes the ZIP archive, manifest, media entries, and integrity metadata          |

## Import services

| Service                    | Responsibility                                                             |
| -------------------------- | -------------------------------------------------------------------------- |
| `PackageReader`            | Opens and verifies package archives                                        |
| `ManifestValidator`        | Validates manifest shape before review/execution                           |
| `ResolutionMapBuilder`     | Resolves shared relation refs against local records                        |
| `PageUrlCollisionDetector` | Flags live/workspace URL conflicts before writing pages                    |
| `MediaIngestService`       | Streams verified media binaries into local storage                         |
| `PageImportService`        | Creates pages, remaps parents, restores owned page URLs, and rebinds media |

The import flow is intentionally review-first. Validation, relation matching, and page collision checks produce data rows for an admin UI or CLI workflow before `ExecuteImportPlanJob` mutates content.

## Relation matching

`RelationMatchResolverRegistry` stores an ordered resolver chain for each relation group. The default package registers:

- layouts: key match, then fingerprint match
- types: key match, then fingerprint match
- sites: slug match
- media: checksum match, then filename match

Packages can append or prepend resolvers for their own relation groups without changing Backup internals.

## Safety guarantees

- Archives are checksum-verified before import services read payload JSON.
- Metadata, payload, media, and total uncompressed sizes are capped by config.
- Page writes run inside a transaction through `PageImportService`.
- URL collision detection understands installs with and without the Workspaces `workspace_id` column.
- Import jobs store validation and execution summaries on `ImportSession` for retry and audit flows.

## Deferred areas

`SiteImportService`, `SpreadsheetReader`, `WpXmlReader`, and `RestoreService` are present as explicit placeholders for planned phases. They currently throw `NotImplementedException` rather than silently accepting unsupported restore/import paths.
