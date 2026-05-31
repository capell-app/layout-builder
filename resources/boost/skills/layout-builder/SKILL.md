---
name: layout-builder
description: Use when editing Capell Layout Builder widgets, containers, assets, presets, admin Livewire builder behavior, or public layout rendering.
---

# Capell Layout Builder

Layout Builder owns admin layout composition, widgets, assets, presets, and public layout output.

## Look

- `packages/layout-builder/src`
- `packages/layout-builder/resources/views`
- `packages/layout-builder/tests`

## Rules

- Authorize Livewire mutations and validate container keys, block indices, and asset IDs server-side.
- Keep business logic in Actions/Data/support classes, not Blade or Livewire callbacks.
- Public Blade must consume prepared data and avoid queries or lazy-loaded relationships.
- Run `vendor/bin/pest packages/layout-builder/tests`.
