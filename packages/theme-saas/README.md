# Theme SaaS

Status: **Available, no schema impact** · Kind: **theme** · Tier: **premium** · Bundle: **theme-studio** · Contexts: **frontend** · Product group: **Capell Theme Studio**

## What This Plugin Adds

Theme SaaS registers a conversion-led SaaS renderer for Capell Theme Studio.

- SaaS theme service provider.
- Theme renderer/views for SaaS Theme Studio output.
- Dependency on Foundation Theme and Theme Studio Core.

## Why It Matters

**For developers:** Adds a renderer package that uses Theme Studio Core runtime contracts while leaving content models unchanged.

**For teams:** Provides a SaaS-oriented visual option for product sites managed through Theme Studio.

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

- Theme Studio preset selection showing SaaS.
- Frontend page rendered with SaaS theme.
- Theme preview URL output.

## Technical Shape

- SaasThemeServiceProvider registers the renderer.
- Requires capell-app/foundation-theme and capell-app/theme-studio-core.
- No migrations, config, routes, resources, or models are present.

## Data Model

- This package does not own data.
- It consumes Theme Studio runtime settings and core page content.

## Install Impact

- Adds a SaaS renderer to Theme Studio.
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
- Verify Foundation Theme assets are generated.

## Quick Start

1. Install the package with `composer require capell-app/theme-saas`.
2. Register the package provider through Composer discovery and clear cached config if the host app uses config caching.
3. Open the new admin surface or integration point and verify the result.

## Next Steps

- [docs/overview.md](docs/overview.md)
- [../theme-studio-core/README.md](../theme-studio-core/README.md)
- [../theme-studio-admin/README.md](../theme-studio-admin/README.md)
- [docs/credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
