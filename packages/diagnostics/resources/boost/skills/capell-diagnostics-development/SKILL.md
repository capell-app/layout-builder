---
name: capell-diagnostics-development
description: Use when editing Capell Diagnostics diagnostics, health checks, or dashboard-dashboard_reports.
---

# Capell Diagnostics

Operational diagnostics for cache, config, migrations, packages, queues, permissions, and setup health.

## Look

- `packages/diagnostics/src`
- `packages/diagnostics/docs`
- `packages/diagnostics/README.md`

## Rules

- Diagnostics should observe and report; avoid hidden mutations.
- Keep health builders in Actions/Data for easy testing.
- Permission and setup dashboard-dashboard_reports must be explicit about risk level.
- Run `vendor/bin/pest packages/diagnostics/tests`.
