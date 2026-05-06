---
name: capell-migration-assistant-development
description: Use when editing Capell MigrationAssistant exports, imports, package readers.
---

# Capell MigrationAssistant

Export, import, dependency graph, and validation workflows.

## Look

- `packages/migration-assistant/src`
- `packages/migration-assistant/docs`
- `packages/migration-assistant/README.md`

## Rules

- Validate imports before writes; prefer previewable rollback report steps.
- Keep package readers/writers isolated from Filament pages.
- Preserve relation resolution and dependency ordering.
- Run `vendor/bin/pest packages/migration-assistant/tests`.
