# Tags

Tags adds shared tagging records and admin management for packages that need editor-controlled taxonomies.

## At A Glance

- Package: `capell-app/tags`
- Namespace: `Capell\Tags\`
- Surfaces: Filament admin, console, database
- Service providers: `packages/tags/src/Providers/AdminServiceProvider.php`, `packages/tags/src/Providers/ConsoleServiceProvider.php`, `packages/tags/src/Providers/TagsServiceProvider.php`
- Capell dependencies: `capell-app/admin`, `capell-app/navigation`, `capell-app/publishing-studio`
- Third-party dependencies: `filament/spatie-laravel-tags-plugin`

## What It Adds

Tags adds tag management, taggable relationships, a reusable tags input, and model traits for Capell content.

- Tag Filament resource.
- TagsInput form component.
- HasTags model concern.
- Tag and Taggable models.
- Install command and model registrar.

## Why It Matters

**For developers:** Provides a shared tagging layer that Blog and page-like models can use without each package defining its own tag tables.

**For teams:** Lets editors classify content consistently across articles and pages.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Admin](https://github.com/capell-app/admin)
- [Capell Navigation](../navigation/README.md)
- [Capell Publishing Studio](../publishing-studio/README.md)

**Open-source packages used here**

- [Filament Spatie Laravel Tags Plugin](https://github.com/filamentphp/spatie-laravel-tags-plugin) - Filament form integration for Spatie tags inside Capell tagging workflows.

**Linked package previews**

[![Filament Spatie Laravel Tags Plugin GitHub preview](https://opengraph.githubassets.com/capell-readme/filamentphp/spatie-laravel-tags-plugin)](https://github.com/filamentphp/spatie-laravel-tags-plugin)

## Screens And Workflow

Screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment.

- Tags admin index.
- Create/edit tag form.
- Tag relation manager showing tagged pages.
- Article or page form using TagsInput.

## Technical Shape

- TagsServiceProvider, AdminServiceProvider, and ConsoleServiceProvider register package surfaces.
- Migration alters/creates tag-related table support.
- Models: Tag and Taggable.
- Filament resource: TagResource.
- TagTypeEnum defines tag types.

## Code Map

| Area      | Path                          | Purpose                                                           |
| --------- | ----------------------------- | ----------------------------------------------------------------- |
| Enums     | `packages/tags/src/Enums`     | Persisted states and Filament option values.                      |
| Models    | `packages/tags/src/Models`    | Eloquent records owned by the package.                            |
| Filament  | `packages/tags/src/Filament`  | Admin resources, pages, widgets, and settings UI.                 |
| Providers | `packages/tags/src/Providers` | Registration, extension hooks, routes, migrations, and resources. |
| Resources | `packages/tags/resources`     | Views, translations, assets, and package resources.               |
| Database  | `packages/tags/database`      | Migrations, seeders, and settings migrations.                     |
| Tests     | `packages/tags/tests`         | Package-level Pest coverage.                                      |

## Admin Surface

- Resources: `TagResource`.
- Pages: `CreateTag`, `EditTag`, `ListTags`.

## Commands

- `capell:tags-install` (packages/tags/src/Console/Commands/InstallCommand.php)

## Data And Persistence

- tags stores translated name and slug values plus type.
- taggables connects tags to articles, pages, and other taggable models.
- Tag model registrar handles morph/model integration.
- Deletion behaviour for taggables should be verified before removing shared tags.

- Models: `HasTags`, `Tag`, `Taggable`.
- Migrations: `2026_05_10_190872_01_alter_tags_table.php`.

## Extension Points

- Register Capell extension points, routes, migrations, settings, render hooks, and resources from service providers.

## Install Impact

- Adds tag database changes.
- Adds tag admin navigation.
- Adds tags form component.
- No public route is registered by this package.

## Install And Setup

- Install with `composer require capell-app/tags` in the host Capell application.
- Run migrations through the host application package install flow.
- In this repository, verify package changes with `vendor/bin/pest`; do not use `php artisan`.

## Admin And Access

- CreateTag (packages/tags/src/Filament/Resources/Tags/Pages/CreateTag.php)
- EditTag (packages/tags/src/Filament/Resources/Tags/Pages/EditTag.php)
- ListTags (packages/tags/src/Filament/Resources/Tags/Pages/ListTags.php)
- TagResource (packages/tags/src/Filament/Resources/Tags/TagResource.php)

- None proven in this package directory.

## Common Pitfalls

- Run the install command or migration before using TagsInput.
- Register taggable models before expecting relationships.
- Use typed tag categories rather than ad hoc strings.

## Docs

- [credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
- [overview.md](docs/overview.md)

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/tags/tests --configuration=phpunit.xml
```

## Maintenance Notes

- Use backed enums for persisted values and enum labels for Filament options.
