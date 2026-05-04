---
name: capell-migrator-development
description: Use when editing Capell Migrator exports, imports, package readers.
---

# Capell Migrator

Export, import, dependency graph, and validation workflows.

## Look

- `packages/migrator/src`
- `packages/migrator/docs`
- `packages/migrator/README.md`

## Rules

- Validate imports before writes; prefer previewable rollback report steps.
- Keep package readers/writers isolated from Filament pages.
- Preserve relation resolution and dependency ordering.
- Run `vendor/bin/pest packages/migrator/tests`.
