# Content Sections

Reusable content sections for Capell.

## At A Glance

- Package: `capell-app/content-sections`
- Namespace: `Capell\ContentSections\`
- Surfaces: Filament admin, Livewire, database
- Service providers: `packages/content-sections/src/Providers/ContentSectionsServiceProvider.php`
- Capell dependencies: `capell-app/admin`, `capell-app/core`, `capell-app/frontend`

## What It Adds

- Reusable content sections for Capell.
- Content block definitions for each registered section, exposed through `capell-app/content-blocks`.
- Admin resources: `SectionResource`.
- Livewire components: `AbstractAssets`, `ModalTableSelect`, `SectionAssets`.

## Code Map

| Area      | Path                                      | Purpose                                                             |
| --------- | ----------------------------------------- | ------------------------------------------------------------------- |
| Actions   | `packages/content-sections/src/Actions`   | Domain operations. Test these directly where possible.              |
| Data      | `packages/content-sections/src/Data`      | Structured payloads, form state, view models, and integration data. |
| Enums     | `packages/content-sections/src/Enums`     | Persisted states and Filament option values.                        |
| Models    | `packages/content-sections/src/Models`    | Eloquent records owned by the package.                              |
| Filament  | `packages/content-sections/src/Filament`  | Admin resources, pages, widgets, and settings UI.                   |
| Livewire  | `packages/content-sections/src/Livewire`  | Interactive frontend or admin components.                           |
| Providers | `packages/content-sections/src/Providers` | Registration, extension hooks, routes, migrations, and resources.   |
| Resources | `packages/content-sections/resources`     | Views, translations, assets, and package resources.                 |
| Config    | `packages/content-sections/config`        | Package configuration and publishable config.                       |
| Database  | `packages/content-sections/database`      | Migrations, seeders, and settings migrations.                       |
| Tests     | `packages/content-sections/tests`         | Package-level Pest coverage.                                        |

## Admin Surface

- Resources: `SectionResource`.
- Pages: `CreateSection`, `EditSection`, `ListSections`.
- Widgets: `SectionAlertsWidget`.

## Runtime Surface

- Livewire: `AbstractAssets`, `ModalTableSelect`, `SectionAssets`.

## Data And Persistence

- Models: `ComposhipsJsonRelationshipsTrait`, `Section`.
- Migrations: `2026_05_10_190844_01_create_sections_table.php`.
- Config: `packages/content-sections/config/capell-content-sections.php`.
- Data objects live in `src/Data/`; use them for payloads, form state, and view models.

## Extension Points

- Contracts: `SectionDefinitionProvider`.
- Content block bridge: registered sections are exposed as `section.{key}` block definitions through `BlockDefinitionProvider::TAG`.
- Register Capell extension points, routes, migrations, settings, render hooks, and resources from service providers.

## Install And Setup

- Install with `composer require capell-app/content-sections` in the host Capell application.
- Run migrations through the host application package install flow.
- In this repository, verify package changes with `vendor/bin/pest`; do not use `php artisan`.

## Docs

No deeper package docs are currently published under `docs/`. Add design notes there when the README would become too long.

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/content-sections/tests --configuration=phpunit.xml
```

## Maintenance Notes

- Put behaviour changes in `src/Actions/`; UI classes, commands, and controllers should call actions instead of owning domain logic.
- Use package `Data` classes at boundaries instead of passing anonymous arrays between layers.
- Use backed enums for persisted values and enum labels for Filament options.
