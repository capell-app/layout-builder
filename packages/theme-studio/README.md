# Theme Studio

Status: **Available, metapackage** · Kind: **bundle** · Tier: **premium** · Bundle: **theme-studio** · Contexts: **frontend, admin** · Product group: **Capell Theme Studio**

## What This Plugin Adds

Theme Studio is the commercial theme system bundle that installs Theme Studio Core, Theme Studio Admin, and the Agency, Corporate, and SaaS renderers.

- Metapackage dependency bundle.
- Theme Studio Core runtime.
- Theme Studio Admin Filament page.
- Agency, Corporate, and SaaS renderer packages.

## Why It Matters

**For developers:** Provides one Composer install target for the full Theme Studio package set without adding runtime code in this directory.

**For teams:** Installs the full theme workflow in one package instead of selecting each renderer and admin package manually.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Theme Agency](../theme-agency/README.md)
- [Capell Theme Corporate](../theme-corporate/README.md)
- [Capell Theme Saas](../theme-saas/README.md)
- [Capell Theme Studio Admin](../theme-studio-admin/README.md)
- [Capell Theme Studio Core](../theme-studio-core/README.md)

**Open-source packages used here**

- No extra third-party Composer package beyond the Capell package stack is required here.

## Screens And Workflow

Screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment.

- Theme Studio admin page.
- Theme preset selection.
- Theme preview URL workflow.
- Frontend output from each bundled renderer.

## Technical Shape

- composer.json type is metapackage.
- Requires theme-agency, theme-corporate, theme-saas, theme-studio-admin, and theme-studio-core.
- No src, config, routes, migrations, resources, or tests are present in this directory.

## Data Model

- This metapackage does not own data.
- Schema impact comes from Theme Studio Core settings and Theme Studio Admin/Core dependencies.

## Install Impact

- Installs the Theme Studio package set.
- No direct database changes from this directory.
- Admin and settings impact come from bundled packages.
- No public routes are registered by this package directly.

## Commands

- None proven in this package directory.

## Admin And Access

- None proven in this package directory.

- None proven in this package directory.

## Common Pitfalls

- Do not document this as a runtime provider; it is a dependency bundle.
- Run setup for the installed child packages where required.

## Quick Start

1. Install the package with `composer require capell-app/theme-studio`.
2. Register the package provider through Composer discovery and clear cached config if the host app uses config caching.
3. Open the new admin surface or integration point and verify the result.

## Next Steps

- [docs/overview.md](docs/overview.md)
- [../theme-studio-core/README.md](../theme-studio-core/README.md)
- [../theme-studio-admin/README.md](../theme-studio-admin/README.md)
- [../theme-agency/README.md](../theme-agency/README.md)
- [../theme-corporate/README.md](../theme-corporate/README.md)
- [../theme-saas/README.md](../theme-saas/README.md)
- [docs/credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
