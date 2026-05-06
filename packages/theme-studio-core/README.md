# Theme Studio Core

Status: **Available, settings-owning** · Kind: **package** · Tier: **premium** · Bundle: **theme-studio** · Contexts: **frontend, console** · Product group: **Capell Theme Studio**

## What This Plugin Adds

Theme Studio Core provides the contracts, registry, runtime data, preview context, token rendering, and Blade rendering support used by Theme Studio renderers.

- Theme registry and runtime resolver.
- Theme page adapters and renderer contracts.
- Preview context and signed preview support.
- Theme token store and renderer.
- Data objects for brand profiles, navigation, hero, content, proof, CTA, feature, footer, and theme pages.

## Why It Matters

**For developers:** Defines the runtime boundary for theme packages so renderer packages can register sections and presets without owning content schema.

**For teams:** Makes theme previews and renderer selection consistent across the package-based CMS foundation.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Core](https://github.com/capell-app/core)
- [Capell Frontend](https://github.com/capell-app/frontend)

**Open-source packages used here**

- [Laravel Actions](https://github.com/lorisleiva/laravel-actions) - single-purpose action classes that keep package workflows out of controllers and Filament resources.
- [Spatie Laravel Data](https://github.com/spatie/laravel-data) - typed data objects for package boundaries, form state, settings, and structured results.

**Linked package previews**

[![Laravel Actions GitHub preview](https://opengraph.githubassets.com/capell-readme/lorisleiva/laravel-actions)](https://github.com/lorisleiva/laravel-actions)

[![Spatie Laravel Data GitHub preview](https://opengraph.githubassets.com/capell-readme/spatie/laravel-data)](https://github.com/spatie/laravel-data)

## Screens And Workflow

Screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment.

- Theme preview URL output.
- Frontend page rendered through Theme Studio Core.
- Brand profile settings.
- Renderer selection proof.

## Technical Shape

- ThemeStudioCoreServiceProvider registers core services.
- Settings migration creates theme studio settings.
- Actions render current theme pages and resolve brand profile/runtime.
- Middleware resolves theme preview context.
- Contracts cover section rendering, page adapters, runtime settings, and theme renderers.

## Data Model

- This package owns Theme Studio settings.
- It does not create content tables.
- Runtime data is composed from settings, theme definitions, core page data, and renderer packages.

## Install Impact

- Adds Theme Studio settings migration.
- Adds preview context middleware support.
- Registers theme runtime services.
- No admin navigation by itself.
- No public route file is present.

## Commands

- None proven in this package directory.

## Admin And Access

- None proven in this package directory.

- None proven in this package directory.

## Common Pitfalls

- Renderer packages must register with the ThemeRegistry.
- Signed preview context must be kept scoped and temporary.
- Run settings migrations before opening admin surfaces that expect Theme Studio settings.

## Quick Start

1. Install the package with `composer require capell-app/theme-studio-core`.
2. Run the package migrations or the Capell package installer required by the host app.
3. Open the new admin surface or integration point and verify the result.

## Next Steps

- [docs/overview.md](docs/overview.md)
- [../theme-studio-admin/README.md](../theme-studio-admin/README.md)
- [../theme-agency/README.md](../theme-agency/README.md)
- [../theme-corporate/README.md](../theme-corporate/README.md)
- [../theme-saas/README.md](../theme-saas/README.md)
- [docs/credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
