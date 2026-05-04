# Migrator Import Export Workflow

This focused guide extends [Overview](overview.md) for the Migrator package.

## Purpose

Migrator separates export, package validation, dependency graph review, relation resolution, import execution, and rollback reporting.

## Export Workflow

1. Build the dependency graph.
2. Serialize package payloads and metadata.
3. Write the package archive to the configured export path.
4. Review package limits before moving archives between environments.

## Import Workflow

1. Read and validate the package.
2. Review page collisions and relation resolution rows.
3. Execute the import plan on the configured queue.
4. Review `ImportCompleted` or `ImportFailed` output.
5. Keep source archives until the import result summary is accepted.

## Pitfalls

- Configure disk and queue settings before large imports.
- Review relation resolution before executing an import plan.
- Check package size limits before accepting client archives.
