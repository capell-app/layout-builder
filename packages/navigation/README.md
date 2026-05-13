# Navigation

Navigation owns editor-managed menus and renders structured navigation data for Capell frontend themes.

## At A Glance

- Package: `capell-app/navigation`
- Namespace: `Capell\Navigation\`
- Surfaces: Filament admin, console, database
- Service providers: `packages/navigation/src/Providers/NavigationServiceProvider.php`
- Capell dependencies: `capell-app/admin`, `capell-app/frontend`

## What It Adds

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

## Code Map

| Area      | Path                                | Purpose                                                             |
| --------- | ----------------------------------- | ------------------------------------------------------------------- |
| Actions   | `packages/navigation/src/Actions`   | Domain operations. Test these directly where possible.              |
| Data      | `packages/navigation/src/Data`      | Structured payloads, form state, view models, and integration data. |
| Enums     | `packages/navigation/src/Enums`     | Persisted states and Filament option values.                        |
| Models    | `packages/navigation/src/Models`    | Eloquent records owned by the package.                              |
| Filament  | `packages/navigation/src/Filament`  | Admin resources, pages, widgets, and settings UI.                   |
| Providers | `packages/navigation/src/Providers` | Registration, extension hooks, routes, migrations, and resources.   |
| Resources | `packages/navigation/resources`     | Views, translations, assets, and package resources.                 |
| Database  | `packages/navigation/database`      | Migrations, seeders, and settings migrations.                       |
| Tests     | `packages/navigation/tests`         | Package-level Pest coverage.                                        |

## Admin Surface

- Resources: `NavigationResource`.
- Pages: `CreateNavigation`, `EditNavigation`, `ListNavigations`.

## Commands

- `capell:navigation-demo {--sites=} {--languages=}` (packages/navigation/src/Console/Commands/DemoCommand.php)
- `capell:navigation-setup {--sites=}` (packages/navigation/src/Console/Commands/SetupCommand.php)

## Data And Persistence

- navigations stores key, type, site, language, items JSON, meta, and visibility windows.
- Navigation items may reference pages and page URLs through JSON.
- Navigations connect to sites, languages, and types.
- Cache key enum indicates navigation cache behaviour.

- Models: `Navigation`.
- Migrations: `2026_05_10_190860_01_create_navigations_table.php`.
- Data objects live in `src/Data/`; use them for payloads, form state, and view models.

## Extension Points

- Contracts: `NavigationNamesResolver`, `NavigationPageSyncer`.
- Events: `NavigationCreating`.
- Listeners: `ReplicateSiteNavigationsListener`.
- Register Capell extension points, routes, migrations, settings, render hooks, and resources from service providers.

## Install Impact

- Adds navigations table.
- Adds navigation admin resource and site relation manager.
- Extends page and site admin schemas.
- No explicit public route is registered by this package.
- Adds setup and demo commands.

## Install And Setup

- Install with `composer require capell-app/navigation` in the host Capell application.
- Run migrations through the host application package install flow.
- In this repository, verify package changes with `vendor/bin/pest`; do not use `php artisan`.

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

## Docs

- [credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
- [overview.md](docs/overview.md)
- [rendering-and-sync.md](docs/rendering-and-sync.md)

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/navigation/tests --configuration=phpunit.xml
```

## Maintenance Notes

- Put behaviour changes in `src/Actions/`; UI classes, commands, and controllers should call actions instead of owning domain logic.
- Use package `Data` classes at boundaries instead of passing anonymous arrays between layers.
- Use backed enums for persisted values and enum labels for Filament options.
