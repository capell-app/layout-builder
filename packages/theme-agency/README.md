# Theme Agency

Status: **Available, no schema impact** · Kind: **theme** · Tier: **premium** · Bundle: **themes** · Contexts: **frontend** · Product group: **Capell Themes**

## What This Package Adds

Theme Agency is a standalone Capell theme package. It registers the `agency` theme key, extends Foundation Theme, and adds expressive renderer views for studio, portfolio, and brand-led sites.

- Agency theme service provider.
- Theme renderer/views for agency-style theme output.
- Dependency on Foundation Theme.

## Why It Matters

**For developers:** Adds a renderer package that plugs into Foundation Theme rather than changing Capell core rendering contracts.

**For teams:** Provides an agency-focused visual option for sites managed through the normal Theme admin page and install flow.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Core](https://github.com/capell-app/core)
- [Capell Foundation Theme](../foundation-theme/README.md)

**Open-source packages used here**

- No extra third-party Composer package beyond the Capell package stack is required here.

## Screens And Workflow

Screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment.

- Theme preset selection showing Agency.
- Frontend page rendered with Agency theme.
- Theme preview URL output.

## Technical Shape

- AgencyThemeServiceProvider registers the renderer.
- `capell.json` declares `themeKey: "agency"` and `extends: "capell-app/foundation-theme"`.
- Uses shared `capell::...` views for layered fallback.
- No migrations, config, routes, resources, or models are present.

## Data Model

- This package does not own data.
- It reads theme runtime data and core page content through Foundation Theme.

## Install Impact

- Adds an Agency renderer to theme system.
- No database changes.
- No admin navigation by itself.
- No public routes by itself.

## Commands

- None proven in this package directory.

## Admin And Access

- None proven in this package directory.

## Common Pitfalls

- Install Foundation Theme before using this renderer.
- Verify frontend assets from Foundation Theme are available.
- Do not install a Studio metapackage; this package installs independently.

## Quick Start

1. Install the package with `composer require capell-app/theme-agency`.
2. Choose `agency` during `capell:install`, the web installer, or from the Theme admin page after install.
3. Generate or publish frontend assets through the host app flow.

## Next Steps

- [docs/overview.md](docs/overview.md)
- [../../docs/creating-a-theme.md](../../docs/creating-a-theme.md)
- [../foundation-theme/README.md](../foundation-theme/README.md)
- [docs/credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
