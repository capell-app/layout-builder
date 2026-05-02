# Capell Theme Studio And Assistant Boundaries

## Package Responsibilities

### `capell-app/frontend`

Owns Capell's public rendering context: site, language, page, layout, theme key, route params, render hooks, frontend assets, and page cache integration. Theme Studio consumes this context through adapters; Frontend does not import Theme Studio.

### `capell-app/default-theme`

Stays the free infrastructure theme layer. It provides the baseline Blade/Tailwind rendering surface that every install can use. Premium themes must not replace this package as the platform fallback.

### `capell-app/theme-studio-core`

Owns the premium theme runtime:

| Area              | Responsibility                                                                                                                  |
| ----------------- | ------------------------------------------------------------------------------------------------------------------------------- |
| Definitions       | Theme metadata, presets, included sections, renderer maps, and gallery data.                                                    |
| Portable sections | Shared hero, features, proof, content listing, CTA, navigation, and footer data.                                                |
| Runtime adapter   | Converts the current Capell frontend page into portable section data, including loaded Mosaic widgets when Mosaic is installed. |
| Preview           | Signed theme/preset preview context without mutating active settings.                                                           |
| Assets            | Token CSS rendering and isolated cache keys per theme, preset, and brand profile.                                               |
| Rendering         | Theme and section renderer contracts. First-party renderers should fail loudly in tests/local development.                      |

Theme Studio Core owns the shared content model. Individual themes translate that model into visual language; they do not define separate content schemas.

### `capell-app/theme-studio-admin`

Owns the Filament Studio experience: gallery, staging, preview URLs, compact brand controls, per-theme overrides, readiness checks, and publish actions.

When `capell-app/workspaces` is installed, publishing a staged theme draft submits a Workspaces approval item. Approval of that linked workspace promotes the staged theme and preset to the active state. Without Workspaces, the same action publishes directly and clears the draft.

### `capell-app/theme-corporate`, `capell-app/theme-agency`, `capell-app/theme-saas`

Own polished premium renderers for the shared Theme Studio section model. They register definitions, curated presets, page renderers, and section renderers. They may own views and visual assets, but not a private content model.

### `capell-app/mosaic`

Owns structured layout building, containers, widgets, widget assets, page-level widget asset overrides, layout presets, and layout creator actions. Mosaic remains a Foundation package and does not import the commercial Assistant package.

### `capell-app/assistant`

Owns the commercial AI orchestration layer: provider connectors, prompt runs, capability registry, approval levels, and optional package integrations. Assistant wraps package-owned Actions such as Mosaic layout previewing; packages expose normal Actions and do not need commercial AI dependencies.

## Composition Model

```text
HTTP request
  -> frontend resolves site, language, page, layout, and active theme key
  -> Theme Studio preview middleware optionally supplies preview theme/preset
  -> Theme Studio runtime resolves active or preview theme/preset and brand profile
  -> CapellFrontendThemePageAdapter maps the page and Mosaic layout widgets into portable sections
  -> theme renderer renders shared section data through the selected premium theme
  -> token CSS asset is loaded using the isolated theme/preset/brand cache key
```

## Rules

1. Theme Studio themes render shared section data; they do not own content schemas.
2. Mosaic owns layout/widget storage and page-level widget asset overrides.
3. Assistant owns AI integration and optional package wrappers; Foundation packages do not import Assistant classes.
4. Frontend stays the public context provider and does not import Theme Studio.
5. Workspaces approval is optional and detected at runtime through the Theme Studio publisher adapter; approval completion is handled by Theme Studio Admin, not by Workspaces knowing Theme Studio internals.
6. New reusable theme runtime behavior belongs in `theme-studio-core`; admin workflow belongs in `theme-studio-admin`; visual treatment belongs in the theme package.
