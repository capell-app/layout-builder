# Hero

Hero renders and seeds the default home-page hero widget used by Capell frontend themes.

## At A Glance

- Package: `capell-app/hero`
- Namespace: `Capell\Hero\`
- Surfaces: console
- Service providers: `packages/hero/src/Providers/HeroServiceProvider.php`
- Capell dependencies: `capell-app/core`, `capell-app/frontend`
- Third-party dependencies: `lorisleiva/laravel-actions`, `spatie/laravel-package-tools`

## What It Adds

- Hero renders and seeds the default home-page hero widget used by Capell frontend themes.
- Package setup or maintenance commands.

## Technical Shape

- HeroServiceProvider registers the hero view components and setup/demo commands.
- Hero data objects shape the payload used by the default homepage hero view.
- The package is intentionally small because themes consume it as a shared visual primitive.

## Code Map

| Area      | Path                          | Purpose                                                             |
| --------- | ----------------------------- | ------------------------------------------------------------------- |
| Actions   | `packages/hero/src/Actions`   | Domain operations. Test these directly where possible.              |
| Data      | `packages/hero/src/Data`      | Structured payloads, form state, view models, and integration data. |
| Providers | `packages/hero/src/Providers` | Registration, extension hooks, routes, migrations, and resources.   |
| Resources | `packages/hero/resources`     | Views, translations, assets, and package resources.                 |
| Tests     | `packages/hero/tests`         | Package-level Pest coverage.                                        |

## Commands

- `capell:hero-setup {--force : Rebuild Hero-managed home layout defaults}` (packages/hero/src/Console/Commands/SetupCommand.php)

## Data And Persistence

- Data objects live in `src/Data/`; use them for payloads, form state, and view models.

## Extension Points

- Register Capell extension points, routes, migrations, settings, render hooks, and resources from service providers.

## Install Impact

- Adds default hero rendering support for frontend themes.
- Adds setup/demo commands for home hero content.

## Install And Setup

- Install with `composer require capell-app/hero` in the host Capell application.
- In this repository, verify package changes with `vendor/bin/pest`; do not use `php artisan`.

## Docs

No deeper package docs are currently published under `docs/`. Add design notes there when the README would become too long.

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/hero/tests --configuration=phpunit.xml
```

## Maintenance Notes

- Put behaviour changes in `src/Actions/`; UI classes, commands, and controllers should call actions instead of owning domain logic.
- Use package `Data` classes at boundaries instead of passing anonymous arrays between layers.
