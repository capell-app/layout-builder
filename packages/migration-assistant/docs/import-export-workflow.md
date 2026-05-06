# MigrationAssistant Import Export Workflow

This focused guide extends [Overview](overview.md) for the MigrationAssistant package.

## Purpose

MigrationAssistant separates export, package validation, flat-file source reading, field mapping, preview, dependency graph review, relation resolution, import execution, and rollback reporting.

## Export Workflow

1. Build the dependency graph.
2. Serialize package payloads and metadata.
3. Write the package archive to the configured export path.
4. Review package limits before moving archives between environments.

## Import Workflow

1. Read a Capell package, CSV, XML, or a source registered by another package.
2. Map source fields into pages, types, or another target registered with the target registry.
3. Preview creates, skips, warnings, and blocking errors before execution.
4. Review page collisions, dependency graph data, media ingest expectations, and relation resolution rows.
5. Execute the import plan on the configured queue.
6. Review `ImportCompleted` or `ImportFailed` output.
7. Review the rollback report for created model class/id pairs, imported URL/media counts, source filename/checksum, executing user/time, and manual rollback instructions.
8. Keep source archives until the import result summary and rollback report are accepted.

## Source Packages

Source packages register an implementation of `ImportSourceReader`. A reader provides rows, columns, metadata, and a suggested target. `capell-app/wordpress-importer` uses this extension point for WordPress WXR exports, keeping WordPress parsing out of MigrationAssistant while still appearing inside the Migration AIOrchestrator.

## Pitfalls

- Configure disk and queue settings before large imports.
- Review preview and relation resolution before executing an import plan.
- Check package size limits before accepting client archives.
