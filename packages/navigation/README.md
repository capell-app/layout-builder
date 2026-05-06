# Navigation

Status: **Available, schema-owning** · Kind: **package** · Tier: **free** · Bundle: **foundation** · Contexts: **admin, frontend, console** · Product group: **Capell Foundation**

## What This Plugin Adds

Navigation adds site and language scoped navigation trees, page navigation fields, sync actions, and frontend loading support.

- Navigation Filament resource.
- Navigation relation manager on sites.
- Page schema extender for navigation placement.
- Navigation item model resolution.
- Actions to add, remove, replicate, and resolve navigation entries.
- Navigation loader support for frontend rendering.

## Why It Matters

**For developers:** Stores navigation items in structured data and uses adapters/registries to connect navigable models without hard-coding page logic everywhere.

**For teams:** Lets editors manage menus for each site and language while keeping page selection tied to Capell records.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Admin](https://github.com/capell-app/admin)
- [Capell Frontend](https://github.com/capell-app/frontend)

**Open-source packages used here**

- No extra third-party Composer package beyond the Capell package stack is required here.

## Screens And Workflow

Screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment.

- Navigation admin index.
- Create/edit navigation form.
- Site relation manager for navigations.
- Page form navigation tab.
- Frontend menu output.

## Technical Shape

- NavigationServiceProvider registers the package.
- Migration creates navigations.
- Model: Navigation.
- Filament resource: NavigationResource.
- Policy: NavigationPolicy.
- Events/listeners handle creation and site replication.

## Data Model

- navigations stores key, type, site, language, items JSON, meta, and visibility windows.
- Navigation items may reference pages and page URLs through JSON.
- Navigations connect to sites, languages, and types.
- Cache key enum indicates navigation cache behaviour.

## Install Impact

- Adds navigations table.
- Adds navigation admin resource and site relation manager.
- Extends page and site admin schemas.
- No explicit public route is registered by this package.
- Adds setup and demo commands.

## Commands

- `capell:navigation-demo {--sites=} {--languages=}` (packages/navigation/src/Console/Commands/DemoCommand.php)
- `capell:navigation-setup {--sites=}` (packages/navigation/src/Console/Commands/SetupCommand.php)

## Admin And Access

- NavigationResource (packages/navigation/src/Filament/Resources/Navigations/NavigationResource.php)
- CreateNavigation (packages/navigation/src/Filament/Resources/Navigations/Pages/CreateNavigation.php)
- EditNavigation (packages/navigation/src/Filament/Resources/Navigations/Pages/EditNavigation.php)
- ListNavigations (packages/navigation/src/Filament/Resources/Navigations/Pages/ListNavigations.php)

- Policy: NavigationPolicy (packages/navigation/src/Policies/NavigationPolicy.php)

## Common Pitfalls

- Create language/site records before creating scoped navigation.
- Resolve stale page references after deleting pages.
- Clear navigation cache after manual data changes.

## Quick Start

1. Install the package with `composer require capell-app/navigation`.
2. Run the package migrations or the Capell package installer required by the host app.
3. Open the new admin or frontend surface and verify the result.

## Next Steps

- [docs/overview.md](docs/overview.md)
- [../blog/README.md](../blog/README.md)
- [../publishing-studio/README.md](../publishing-studio/README.md)
- [docs/credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
