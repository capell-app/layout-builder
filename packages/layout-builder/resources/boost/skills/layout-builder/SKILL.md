---
name: layout-builder
description: Use when editing Capell LayoutBuilder widgets, sections, layout containers, or layout planning.
---

# Capell LayoutBuilder

Reusable widgets, sections, layout containers, widget assets, and frontend widget rendering.

## Look

- `packages/layout-builder/src`
- `packages/layout-builder/docs`
- `packages/layout-builder/README.md`

## Rules

- Keep layout/domain behaviour in Actions, not Livewire or Filament pages.
- Widgets, assets, and configurators are separate concepts; do not blur them.
- Preserve page/schema extension points for other packages.
- Run `vendor/bin/pest packages/layout-builder/tests`.
