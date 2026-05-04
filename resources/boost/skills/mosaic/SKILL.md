---
name: mosaic
description: Use when editing Capell Mosaic widgets, sections, layout containers, or layout planning.
---

# Capell Mosaic

Reusable widgets, sections, layout containers, widget assets, and frontend widget rendering.

## Look

- `packages/mosaic/src`
- `packages/mosaic/docs`
- `packages/mosaic/README.md`

## Rules

- Keep layout/domain behaviour in Actions, not Livewire or Filament pages.
- Widgets, assets, and configurators are separate concepts; do not blur them.
- Preserve page/schema extension points for other packages.
- Run `vendor/bin/pest packages/mosaic/tests`.
