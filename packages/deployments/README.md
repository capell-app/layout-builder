# Deployments

Repository deployment connections and Composer publishing for Capell CMS.

## At A Glance

- Package: `capell-app/deployments`
- Namespace: `Capell\Deployments\`
- Surfaces: Filament admin, HTTP, database
- Service providers: `packages/deployments/src/Providers/DeploymentsServiceProvider.php`
- Capell dependencies: `capell-app/admin`, `capell-app/core`
- Third-party dependencies: `laravel/framework`, `lorisleiva/laravel-actions`, `spatie/laravel-data`, `spatie/laravel-package-tools`

## What It Adds

- Repository deployment connections and Composer publishing for Capell CMS.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Admin](https://github.com/capell-app/admin)
- [Capell Core](https://github.com/capell-app/core)

**Open-source packages used here**

- [Laravel Actions](https://github.com/lorisleiva/laravel-actions) - single-purpose action classes that keep package workflows out of controllers and Filament resources.
- [Spatie Laravel Data](https://github.com/spatie/laravel-data) - typed data objects for package boundaries, form state, settings, and structured results.
- [Spatie Laravel Package Tools](https://github.com/spatie/laravel-package-tools) - Laravel package bootstrapping for config, migrations, commands, translations, and service provider setup.

**Linked package previews**

[![Laravel Actions GitHub preview](https://opengraph.githubassets.com/capell-readme/lorisleiva/laravel-actions)](https://github.com/lorisleiva/laravel-actions)

[![Spatie Laravel Data GitHub preview](https://opengraph.githubassets.com/capell-readme/spatie/laravel-data)](https://github.com/spatie/laravel-data)

[![Spatie Laravel Package Tools GitHub preview](https://opengraph.githubassets.com/capell-readme/spatie/laravel-package-tools)](https://github.com/spatie/laravel-package-tools)

## Screens And Workflow

Screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment.

## Code Map

| Area      | Path                                 | Purpose                                                             |
| --------- | ------------------------------------ | ------------------------------------------------------------------- |
| Actions   | `packages/deployments/src/Actions`   | Domain operations. Test these directly where possible.              |
| Data      | `packages/deployments/src/Data`      | Structured payloads, form state, view models, and integration data. |
| Enums     | `packages/deployments/src/Enums`     | Persisted states and Filament option values.                        |
| Models    | `packages/deployments/src/Models`    | Eloquent records owned by the package.                              |
| Filament  | `packages/deployments/src/Filament`  | Admin resources, pages, widgets, and settings UI.                   |
| HTTP      | `packages/deployments/src/Http`      | Controllers, middleware, and request handling.                      |
| Providers | `packages/deployments/src/Providers` | Registration, extension hooks, routes, migrations, and resources.   |
| Resources | `packages/deployments/resources`     | Views, translations, assets, and package resources.                 |
| Routes    | `packages/deployments/routes`        | Route files loaded by the service provider.                         |
| Config    | `packages/deployments/config`        | Package configuration and publishable config.                       |
| Database  | `packages/deployments/database`      | Migrations, seeders, and settings migrations.                       |
| Tests     | `packages/deployments/tests`         | Package-level Pest coverage.                                        |

## Admin Surface

- Pages: `DeploymentConnectionPage`.
- Widgets: `DeploymentConnectionWidget`.

## Runtime Surface

- Controllers: `BitbucketCallbackController`, `GitHubCallbackController`, `GitLabCallbackController`.
- Routes: `packages/deployments/routes/oauth.php`.

## Data And Persistence

- Models: `DeploymentConnection`.
- Migrations: `2026_05_10_190845_01_create_deployment_connections_table.php`.
- Config: `packages/deployments/config/capell-deployments.php`.
- Data objects live in `src/Data/`; use them for payloads, form state, and view models.

## Extension Points

- Contracts: `GitProviderContract`, `PublishesComposerChanges`.
- Register Capell extension points, routes, migrations, settings, render hooks, and resources from service providers.

## Install And Setup

- Install with `composer require capell-app/deployments` in the host Capell application.
- Run migrations through the host application package install flow.
- In this repository, verify package changes with `vendor/bin/pest`; do not use `php artisan`.

## Docs

- [credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
- [overview.md](docs/overview.md)

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/deployments/tests --configuration=phpunit.xml
```

## Maintenance Notes

- Put behaviour changes in `src/Actions/`; UI classes, commands, and controllers should call actions instead of owning domain logic.
- Use package `Data` classes at boundaries instead of passing anonymous arrays between layers.
- Use backed enums for persisted values and enum labels for Filament options.
