# Media Library

Media Library connects Capell to Awcodes Curator media, media health reporting, and Spatie Media migration support.

## At A Glance

- Package: `capell-app/media-library`
- Namespace: `Capell\MediaLibrary\`
- Surfaces: Filament admin, console, database
- Capell dependencies: `capell-app/admin`, `capell-app/core`
- Third-party dependencies: `awcodes/filament-curator`

## What It Adds

Media Library connects Capell to Awcodes Curator media, media health reporting, and Spatie Media migration support.

- Curator media model wrapper.
- Media health admin page and table.
- Curator media field factory.
- Migration command and action for moving Spatie media into Curator.
- InteractsWithCuratorMedia concern.

## Why It Matters

**For developers:** Centralises Curator integration behind actions, field factories, and model concerns so packages can use media fields consistently.

**For teams:** Helps site operators audit media records and move legacy media into the current Capell media foundation.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Admin](https://github.com/capell-app/admin)
- [Capell Core](https://github.com/capell-app/core)

**Open-source packages used here**

- [Awcodes Curator](https://github.com/awcodes/filament-curator) - the Filament media manager that Capell wraps for media fields, media health, and Curator-first media workflows.

**Linked package previews**

[![Awcodes Curator GitHub preview](https://opengraph.githubassets.com/capell-readme/awcodes/filament-curator)](https://github.com/awcodes/filament-curator)

## Screens And Workflow

Screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment.

- Media health page.
- Media health table.
- Curator media field inside a form.
- Migration command output or report.

## Technical Shape

- MediaLibraryServiceProvider registers the package.
- Model: CuratorMedia.
- Command: MigrateSpatieToCuratorCommand.
- Action: MigrateSpatieMediaToCuratorAction.
- Page: MediaHealthPage.
- No migrations are present in this package.

## Code Map

| Area      | Path                                  | Purpose                                                             |
| --------- | ------------------------------------- | ------------------------------------------------------------------- |
| Actions   | `packages/media-library/src/Actions`  | Domain operations. Test these directly where possible.              |
| Data      | `packages/media-library/src/Data`     | Structured payloads, form state, view models, and integration data. |
| Models    | `packages/media-library/src/Models`   | Eloquent records owned by the package.                              |
| Filament  | `packages/media-library/src/Filament` | Admin resources, pages, widgets, and settings UI.                   |
| Resources | `packages/media-library/resources`    | Views, translations, assets, and package resources.                 |
| Tests     | `packages/media-library/tests`        | Package-level Pest coverage.                                        |

## Admin Surface

- Pages: `MediaHealthPage`, `MediaHealthTable`.

## Commands

- `capell:media-migrate-to-curator {--dry-run : Report what would happen without writing} {--collection=* : Spatie collection names to migrate (repeatable; default: all)} {--owner-type= : Restrict migration to this owner model FQCN} {--chunk=200 : Number of Spatie media rows to process per chunk}` (packages/media-library/src/Console/MigrateSpatieToCuratorCommand.php)

## Data And Persistence

- This package does not define its own migrations.
- It relies on Curator and existing media tables.
- Migration result data records counts and outcomes for Spatie-to-Curator moves.

- Models: `CuratorMedia`.
- Data objects live in `src/Data/`; use them for payloads, form state, and view models.

## Install Impact

- Adds Curator media field integration.
- Adds media health admin page.
- Adds migration command.
- No package-owned database changes.

## Install And Setup

- Install with `composer require capell-app/media-library` in the host Capell application.
- In this repository, verify package changes with `vendor/bin/pest`; do not use `php artisan`.

## Admin And Access

- MediaHealthPage (packages/media-library/src/Filament/Pages/MediaHealthPage.php, slug `media-health`)

- Gate: MediaHealthPage: Filament Shield page permissions

## Common Pitfalls

- Install and migrate Curator before relying on CuratorMedia.
- Back up legacy Spatie media before migration.
- Check disk paths and conversions before bulk migration.

## Docs

- [credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
- [overview.md](docs/overview.md)

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/media-library/tests --configuration=phpunit.xml
```

## Maintenance Notes

- Put behaviour changes in `src/Actions/`; UI classes, commands, and controllers should call actions instead of owning domain logic.
- Use package `Data` classes at boundaries instead of passing anonymous arrays between layers.
