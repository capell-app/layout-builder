---
name: capell-foundation-theme-development
description: Use when editing Capell Foundation Theme Blade, Tailwind assets, media URLs, or settings.
---

# Capell Foundation Theme

Default frontend theme infrastructure: Blade components, Tailwind assets, URL helpers, and theme settings.

## Look

- `packages/foundation-theme/src`
- `packages/foundation-theme/resources`
- `packages/foundation-theme/README.md`

## Rules

- Keep components generic; branded renderers belong in Theme Studio packages.
- Preserve safe output rules for Blade and SVG media.
- Theme settings must remain optional and migration-safe.
- Run `vendor/bin/pest packages/foundation-theme/tests`.
