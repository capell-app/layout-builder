# Demo Kit

Demo content and media kit for Capell.

## At A Glance

- Package: `capell-app/demo-kit`
- Namespace: `Capell\DemoKit\`
- Surfaces: Filament admin, console
- Service providers: `packages/demo-kit/src/Providers/DemoKitServiceProvider.php`
- Capell dependencies: `capell-app/admin`, `capell-app/core`, `capell-app/frontend`

## What It Adds

Demo content and media kit for Capell

- Example site content and media for local Capell demos.
- Demo content provider for admin and frontend package setup.
- Demo assets that help validate a package install quickly.

## Why It Matters

**For developers:** Keeps Demo Kit package responsibilities isolated behind providers, actions, data objects, and package-owned resources where the package needs them.

**For teams:** Makes the Capell Foundation capability easier to explain, install, and verify during package selection.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Admin](https://github.com/capell-app/admin)
- [Capell Core](https://github.com/capell-app/core)
- [Capell Frontend](https://github.com/capell-app/frontend)

**Open-source packages used here**

- No extra third-party Composer package beyond the Capell package stack is required here.

## Code Map

| Area      | Path                              | Purpose                                                           |
| --------- | --------------------------------- | ----------------------------------------------------------------- |
| Actions   | `packages/demo-kit/src/Actions`   | Domain operations. Test these directly where possible.            |
| Filament  | `packages/demo-kit/src/Filament`  | Admin resources, pages, widgets, and settings UI.                 |
| Providers | `packages/demo-kit/src/Providers` | Registration, extension hooks, routes, migrations, and resources. |
| Resources | `packages/demo-kit/resources`     | Views, translations, assets, and package resources.               |
| Config    | `packages/demo-kit/config`        | Package configuration and publishable config.                     |
| Tests     | `packages/demo-kit/tests`         | Package-level Pest coverage.                                      |

## Admin Surface

- Pages: `DemoKitPage`.

## Commands

- `capell:admin-demo {--user=} {--languages=} {--url=} {--sites=}` (packages/demo-kit/src/Console/Commands/AdminDemoCommand.php)
- `capell:demo {--user} {--languages=} {--packages} {--sites=} {--url} {--force}` (packages/demo-kit/src/Console/Commands/DemoCommand.php)
- `capell:demo-kit-full-demo {--url=} {--user=} {--languages=} {--sites=} {--force}` (packages/demo-kit/src/Console/Commands/FullDemoCommand.php)

## Data And Persistence

- Config: `packages/demo-kit/config/capell-demo-kit.php`.

## Extension Points

- Register Capell extension points, routes, migrations, settings, render hooks, and resources from service providers.

## Install And Setup

- Install with `composer require capell-app/demo-kit` in the host Capell application.
- In this repository, verify package changes with `vendor/bin/pest`; do not use `php artisan`.

## Docs

- [credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/demo-kit/tests --configuration=phpunit.xml
```

## Maintenance Notes

- Put behaviour changes in `src/Actions/`; UI classes, commands, and controllers should call actions instead of owning domain logic.
