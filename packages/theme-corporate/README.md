# Theme Corporate

Status: **Available, no schema impact** · Kind: **theme** · Tier: **premium** · Bundle: **themes** · Contexts: **frontend** · Product group: **Capell Themes**

## What This Package Adds

Theme Corporate is a standalone Capell theme package. It registers the `corporate` theme key, extends Foundation Theme, and adds restrained renderer views for B2B, public sector, and professional-service sites.

- Corporate theme service provider.
- Theme renderer/views for corporate theme output.
- Dependency on Foundation Theme.

## Why It Matters

**For developers:** Adds a renderer package that plugs into Foundation Theme contracts and runtime settings.

**For teams:** Provides a corporate visual option for sites that need restrained, trust-focused presentation through the normal Theme admin page and install flow.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Core](https://github.com/capell-app/core)
- [Capell Foundation Theme](../foundation-theme/README.md)

**Open-source packages used here**

- No extra third-party Composer package beyond the Capell package stack is required here.

## Screens And Workflow

Screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment.

- Theme preset selection showing Corporate.
- Frontend page rendered with Corporate theme.
- Theme preview URL output.

## Technical Shape

- CorporateThemeServiceProvider registers the renderer.
- `capell.json` declares `themeKey: "corporate"` and `extends: "capell-app/foundation-theme"`.
- Uses Foundation Theme runtime data and standard section keys, while rendering its own page and section Blade views.
- Ships Blade resources for the page wrapper and standard theme sections.
- No migrations, config, routes, models, admin navigation, or package-owned settings are present.

## Data Model

- This package does not own data.
- It consumes theme runtime settings and core page content.

## Install Impact

- Adds a Corporate renderer to theme system.
- No database changes.
- No admin navigation by itself.
- No public routes by itself.

## Commands

- None proven in this package directory.

## Admin And Access

- None proven in this package directory.

## Common Pitfalls

- Install Foundation Theme before using this renderer.
- Verify Foundation Theme assets are generated.
- Do not install a Studio metapackage; this package installs independently.

## Quick Start

1. Install the package with `composer require capell-app/theme-corporate`.
2. Choose `corporate` during `capell:install`, the web installer, or from the Theme admin page after install.
3. Generate or publish frontend assets through the host app flow.

## Next Steps

- [docs/overview.md](docs/overview.md)
- [../../docs/creating-a-theme.md](../../docs/creating-a-theme.md)
- [../foundation-theme/README.md](../foundation-theme/README.md)
- [docs/credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
