# Capell Theme And AIOrchestrator Boundaries

## Package Responsibilities

### `capell-app/frontend`

Owns Capell's public rendering context: site, language, page, layout, theme key, route params, render hooks, frontend assets, and page cache integration. Frontend resolves the active theme view chain, but does not import concrete premium themes.

### `capell-app/foundation-theme`

Owns the free theme foundation and shared theme runtime. It provides the baseline Blade/Tailwind rendering surface, theme registry, renderer contracts, preview context, and token CSS support. Premium themes may extend it, but Foundation remains the platform fallback and carries the `default` theme key.

### `capell-app/theme-corporate`, `capell-app/theme-agency`, `capell-app/theme-saas`

Own polished premium renderers. They register definitions, curated presets, page renderers, section renderers, views, and visual assets. Each theme installs independently and declares `extends: "capell-app/foundation-theme"` in `capell.json`. There is no Studio metapackage bundling them together.

### Admin/frontend layout builder APIs

Structured layout building, containers, widgets, widget assets, page-level widget asset overrides, layout presets, and layout creator actions now live in the admin/frontend core packages. Optional packages may consume those APIs, but there is no separate `capell-app/layout-builder` package in this repository.

### `capell-app/ai-orchestrator`

Owns the commercial AI orchestration layer: provider connectors, prompt runs, capability registry, approval levels, and optional package integrations. AIOrchestrator wraps package-owned Actions such as core layout builder layout previewing; packages expose normal Actions and do not need commercial AI dependencies.

## Composition Model

```text
HTTP request
  -> frontend resolves site, language, page, layout, and active theme key
  -> foundation theme runtime optionally supplies preview theme/preset
  -> theme runtime resolves active or preview theme/preset and brand profile
  -> CapellFrontendThemePageAdapter maps the page and layout builder layout widgets into portable sections
  -> selected theme renderer renders shared section data
  -> token CSS asset is loaded using the isolated theme/preset/brand cache key
```

## Rules

1. One theme is active per site.
2. Theme inheritance is single-parent through `capell.json` `extends`.
3. Parent preset defaults load first, child preset defaults load second, and Theme admin database edits win last.
4. Foundation Theme owns shared runtime behavior; visual treatment belongs in concrete theme packages.
5. The admin/frontend layout builder APIs own layout/widget storage and page-level widget asset overrides.
6. AIOrchestrator owns AI integration and optional package wrappers; Foundation packages do not import AIOrchestrator classes.

See [Creating a Capell theme](creating-a-theme.md) for the package contract and install flow.
