---
name: blog
description: Use when editing Capell Blog articles, archives, tag pages, widgets, or sitemaps.
---

# Capell Blog

Article publishing, archive/tag pages, LayoutBuilder article widgets, and blog sitemaps.

## Look

- `packages/blog/src`
- `packages/blog/docs`
- `packages/blog/README.md`

## Rules

- Blog depends on LayoutBuilder; do not move widget logic into Core.
- Keep article publishing actions separate from Filament pages.
- Preserve sitemap and frontend Livewire behaviour when changing slugs.
- Run `vendor/bin/pest packages/blog/tests`.
