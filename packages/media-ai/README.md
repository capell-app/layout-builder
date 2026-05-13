# Media AI

Optional AI-assisted media actions for Capell.

## At A Glance

- Package: `capell-app/media-ai`
- Namespace: `Capell\MediaAI\`
- Service providers: `packages/media-ai/src/Providers/MediaAIServiceProvider.php`
- Capell dependencies: `capell-app/admin`, `capell-app/core`
- Third-party dependencies: `filament/filament`, `laravel/framework`, `spatie/laravel-package-tools`

## What It Adds

Optional AI-assisted media actions for Capell.

- Optional AI-assisted media edit actions.
- ImageDoctor contract with a safe null implementation.
- Filament media action extender for admin workflows.

## Why It Matters

**For developers:** Keeps Media Ai package responsibilities isolated behind providers, actions, data objects, and package-owned resources where the package needs them.

**For teams:** Makes the Capell Media capability easier to explain, install, and verify during package selection.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Admin](https://github.com/capell-app/admin)
- [Capell Core](https://github.com/capell-app/core)

**Open-source packages used here**

- [Spatie Laravel Package Tools](https://github.com/spatie/laravel-package-tools) - Laravel package bootstrapping for config, migrations, commands, translations, and service provider setup.

**Linked package previews**

[![Spatie Laravel Package Tools GitHub preview](https://opengraph.githubassets.com/capell-readme/spatie/laravel-package-tools)](https://github.com/spatie/laravel-package-tools)

## Screens And Workflow

Screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment.

## Code Map

| Area      | Path                              | Purpose                                                             |
| --------- | --------------------------------- | ------------------------------------------------------------------- |
| Data      | `packages/media-ai/src/Data`      | Structured payloads, form state, view models, and integration data. |
| Filament  | `packages/media-ai/src/Filament`  | Admin resources, pages, widgets, and settings UI.                   |
| Providers | `packages/media-ai/src/Providers` | Registration, extension hooks, routes, migrations, and resources.   |
| Resources | `packages/media-ai/resources`     | Views, translations, assets, and package resources.                 |
| Config    | `packages/media-ai/config`        | Package configuration and publishable config.                       |
| Tests     | `packages/media-ai/tests`         | Package-level Pest coverage.                                        |

## Data And Persistence

- Config: `packages/media-ai/config/capell-media-ai.php`.
- Data objects live in `src/Data/`; use them for payloads, form state, and view models.

## Extension Points

- Contracts: `ImageDoctor`.
- Register Capell extension points, routes, migrations, settings, render hooks, and resources from service providers.

## Install And Setup

- Install with `composer require capell-app/media-ai` in the host Capell application.
- In this repository, verify package changes with `vendor/bin/pest`; do not use `php artisan`.

## Docs

- [credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
- [overview.md](docs/overview.md)

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/media-ai/tests --configuration=phpunit.xml
```

## Maintenance Notes

- Use package `Data` classes at boundaries instead of passing anonymous arrays between layers.
