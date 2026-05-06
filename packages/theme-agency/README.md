# Theme Agency

Status: **Available, no schema impact** · Kind: **theme** · Tier: **premium** · Bundle: **theme-studio** · Contexts: **frontend** · Product group: **Capell Theme Studio**

## What This Plugin Adds

Theme Agency registers an expressive agency renderer for Capell Theme Studio.

- Agency theme service provider.
- Theme renderer/views for agency-style Theme Studio output.
- Dependency on Foundation Theme and Theme Studio Core.

## Why It Matters

**For developers:** Adds a renderer package that plugs into Theme Studio Core rather than changing Capell core rendering contracts.

**For teams:** Provides an agency-focused visual option for sites managed through Theme Studio.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Core](https://github.com/capell-app/core)
- [Capell Foundation Theme](../foundation-theme/README.md)
- [Capell Theme Studio Core](../theme-studio-core/README.md)

**Open-source packages used here**

- No extra third-party Composer package beyond the Capell package stack is required here.

## Screens And Workflow

Screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment.

- Theme Studio preset selection showing Agency.
- Frontend page rendered with Agency theme.
- Theme preview URL output.

## Technical Shape

- AgencyThemeServiceProvider registers the renderer.
- Requires capell-app/foundation-theme and capell-app/theme-studio-core.
- No migrations, config, routes, resources, or models are present.

## Data Model

- This package does not own data.
- It reads Theme Studio runtime data and core page content through Theme Studio Core.

## Install Impact

- Adds an Agency renderer to Theme Studio.
- No database changes.
- No admin navigation by itself.
- No public routes by itself.

## Commands

- None proven in this package directory.

## Admin And Access

- None proven in this package directory.

- None proven in this package directory.

## Common Pitfalls

- Install Theme Studio Core before using this renderer.
- Verify frontend assets from Foundation Theme are available.

## Quick Start

1. Install the package with `composer require capell-app/theme-agency`.
2. Register the package provider through Composer discovery and clear cached config if the host app uses config caching.
3. Open the new admin surface or integration point and verify the result.

## Next Steps

- [docs/overview.md](docs/overview.md)
- [../theme-studio-core/README.md](../theme-studio-core/README.md)
- [../theme-studio-admin/README.md](../theme-studio-admin/README.md)
- [docs/credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
