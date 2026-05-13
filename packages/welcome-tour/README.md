# Welcome Tour

Optional Filament welcome tour for Capell Admin.

## At A Glance

- Package: `capell-app/welcome-tour`
- Namespace: `Capell\WelcomeTour\`
- Surfaces: Filament admin
- Service providers: `packages/welcome-tour/src/Providers/WelcomeTourServiceProvider.php`
- Capell dependencies: `capell-app/admin`
- Third-party dependencies: `jibaymcs/filament-tour`, `laravel/framework`, `lorisleiva/laravel-actions`, `spatie/laravel-package-tools`, `spatie/laravel-settings`

## What It Adds

- Optional Filament welcome tour for Capell Admin.

## Code Map

| Area      | Path                                  | Purpose                                                             |
| --------- | ------------------------------------- | ------------------------------------------------------------------- |
| Actions   | `packages/welcome-tour/src/Actions`   | Domain operations. Test these directly where possible.              |
| Data      | `packages/welcome-tour/src/Data`      | Structured payloads, form state, view models, and integration data. |
| Filament  | `packages/welcome-tour/src/Filament`  | Admin resources, pages, widgets, and settings UI.                   |
| Providers | `packages/welcome-tour/src/Providers` | Registration, extension hooks, routes, migrations, and resources.   |
| Resources | `packages/welcome-tour/resources`     | Views, translations, assets, and package resources.                 |
| Config    | `packages/welcome-tour/config`        | Package configuration and publishable config.                       |
| Database  | `packages/welcome-tour/database`      | Migrations, seeders, and settings migrations.                       |
| Tests     | `packages/welcome-tour/tests`         | Package-level Pest coverage.                                        |

## Admin Surface

- Pages: `WelcomeTourDashboard`.
- Settings: `WelcomeTourSettings`.

## Data And Persistence

- Config: `packages/welcome-tour/config/capell-welcome-tour.php`.
- Data objects live in `src/Data/`; use them for payloads, form state, and view models.

## Extension Points

- Register Capell extension points, routes, migrations, settings, render hooks, and resources from service providers.

## Install And Setup

- Install with `composer require capell-app/welcome-tour` in the host Capell application.
- In this repository, verify package changes with `vendor/bin/pest`; do not use `php artisan`.

## Docs

- [overview.md](docs/overview.md)
- [steps-and-settings.md](docs/steps-and-settings.md)

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/welcome-tour/tests --configuration=phpunit.xml
```

## Maintenance Notes

- Put behaviour changes in `src/Actions/`; UI classes, commands, and controllers should call actions instead of owning domain logic.
- Use package `Data` classes at boundaries instead of passing anonymous arrays between layers.
