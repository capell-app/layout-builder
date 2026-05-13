# Notes

Contextual notes, assignments, mentions, and reminders for Capell.

## At A Glance

- Package: `capell-app/notes`
- Namespace: `Capell\Notes\`
- Surfaces: Filament admin, database
- Service providers: `packages/notes/src/Providers/AdminServiceProvider.php`, `packages/notes/src/Providers/NotesServiceProvider.php`
- Capell dependencies: `capell-app/admin`

## What It Adds

- Contextual notes, assignments, mentions, and reminders for Capell.

## Code Map

| Area      | Path                           | Purpose                                                             |
| --------- | ------------------------------ | ------------------------------------------------------------------- |
| Actions   | `packages/notes/src/Actions`   | Domain operations. Test these directly where possible.              |
| Data      | `packages/notes/src/Data`      | Structured payloads, form state, view models, and integration data. |
| Enums     | `packages/notes/src/Enums`     | Persisted states and Filament option values.                        |
| Models    | `packages/notes/src/Models`    | Eloquent records owned by the package.                              |
| Filament  | `packages/notes/src/Filament`  | Admin resources, pages, widgets, and settings UI.                   |
| Providers | `packages/notes/src/Providers` | Registration, extension hooks, routes, migrations, and resources.   |
| Resources | `packages/notes/resources`     | Views, translations, assets, and package resources.                 |
| Database  | `packages/notes/database`      | Migrations, seeders, and settings migrations.                       |
| Tests     | `packages/notes/tests`         | Package-level Pest coverage.                                        |

## Admin Surface

- Pages: `NotesInboxPage`.

## Data And Persistence

- Models: `Note`, `NoteAssignment`, `NoteMention`, `NoteReminder`.
- Migrations: `2026_05_10_190862_01_create_notes_tables.php`.
- Data objects live in `src/Data/`; use them for payloads, form state, and view models.

## Extension Points

- Register Capell extension points, routes, migrations, settings, render hooks, and resources from service providers.

## Install And Setup

- Install with `composer require capell-app/notes` in the host Capell application.
- Run migrations through the host application package install flow.
- In this repository, verify package changes with `vendor/bin/pest`; do not use `php artisan`.

## Docs

No deeper package docs are currently published under `docs/`. Add design notes there when the README would become too long.

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/notes/tests --configuration=phpunit.xml
```

## Maintenance Notes

- Put behaviour changes in `src/Actions/`; UI classes, commands, and controllers should call actions instead of owning domain logic.
- Use package `Data` classes at boundaries instead of passing anonymous arrays between layers.
- Use backed enums for persisted values and enum labels for Filament options.
