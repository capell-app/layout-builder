# Frontend Authoring

Frontend authoring bridge and in-page editing for Capell frontend.

## At A Glance

- Package: `capell-app/frontend-authoring`
- Namespace: `Capell\FrontendAuthoring\`
- Surfaces: Livewire, HTTP
- Service providers: `packages/frontend-authoring/src/Providers/FrontendAuthoringServiceProvider.php`
- Capell dependencies: `capell-app/admin`, `capell-app/frontend`, `capell-app/html-cache`
- Third-party dependencies: `spatie/laravel-package-tools`

## What It Adds

- Frontend authoring bridge and in-page editing for Capell frontend.
- Livewire components: `EditRegionField`.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Admin](https://github.com/capell-app/admin)
- [Capell Frontend](https://github.com/capell-app/frontend)
- [Capell HTML Cache](https://github.com/capell-app/html-cache)

**Open-source packages used here**

- [Spatie Laravel Package Tools](https://github.com/spatie/laravel-package-tools) - Laravel package bootstrapping for config, migrations, commands, translations, and service provider setup.

**Linked package previews**

[![Spatie Laravel Package Tools GitHub preview](https://opengraph.githubassets.com/capell-readme/spatie/laravel-package-tools)](https://github.com/spatie/laravel-package-tools)

## Screens And Workflow

Screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment.

## Code Map

| Area      | Path                                        | Purpose                                                             |
| --------- | ------------------------------------------- | ------------------------------------------------------------------- |
| Actions   | `packages/frontend-authoring/src/Actions`   | Domain operations. Test these directly where possible.              |
| Data      | `packages/frontend-authoring/src/Data`      | Structured payloads, form state, view models, and integration data. |
| Livewire  | `packages/frontend-authoring/src/Livewire`  | Interactive frontend or admin components.                           |
| HTTP      | `packages/frontend-authoring/src/Http`      | Controllers, middleware, and request handling.                      |
| Providers | `packages/frontend-authoring/src/Providers` | Registration, extension hooks, routes, migrations, and resources.   |
| Resources | `packages/frontend-authoring/resources`     | Views, translations, assets, and package resources.                 |
| Routes    | `packages/frontend-authoring/routes`        | Route files loaded by the service provider.                         |
| Config    | `packages/frontend-authoring/config`        | Package configuration and publishable config.                       |
| Tests     | `packages/frontend-authoring/tests`         | Package-level Pest coverage.                                        |

## Runtime Surface

- Livewire: `EditRegionField`.
- Controllers: `BeaconController`, `EditRegionController`.
- Routes: `packages/frontend-authoring/routes/web.php`.

## Data And Persistence

- Config: `packages/frontend-authoring/config/capell-frontend-authoring.php`.
- Data objects live in `src/Data/`; use them for payloads, form state, and view models.

## Extension Points

- Register Capell extension points, routes, migrations, settings, render hooks, and resources from service providers.

## Install And Setup

- Install with `composer require capell-app/frontend-authoring` in the host Capell application.
- In this repository, verify package changes with `vendor/bin/pest`; do not use `php artisan`.

## Docs

- [credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
- [editable-regions.md](docs/editable-regions.md)
- [in-page-editing.md](docs/in-page-editing.md)
- [overview.md](docs/overview.md)

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/frontend-authoring/tests --configuration=phpunit.xml
```

## Maintenance Notes

- Never render authoring controls, model identifiers, field paths, selectors, signed editor URLs, or package hints into public HTML. Add editing affordances only after an authenticated admin beacon response.
- Put behaviour changes in `src/Actions/`; UI classes, commands, and controllers should call actions instead of owning domain logic.
- Use package `Data` classes at boundaries instead of passing anonymous arrays between layers.
