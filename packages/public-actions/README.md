# Public Actions

Reusable public submit actions, outbound automation dispatch, and integration endpoints for Capell CMS.

## At A Glance

- Package: `capell-app/public-actions`
- Namespace: `Capell\PublicActions\`
- Surfaces: Filament admin, HTTP, queue, database
- Service providers: `packages/public-actions/src/Providers/PublicActionsServiceProvider.php`
- Capell dependencies: `capell-app/admin`, `capell-app/core`, `capell-app/frontend`
- Third-party dependencies: `laravel/framework`, `lorisleiva/laravel-actions`

## What It Adds

- Reusable public submit actions, outbound automation dispatch, and integration endpoints for Capell CMS.
- Admin resources: `PublicActionDestinationResource`, `PublicActionDispatchAttemptResource`, `PublicActionIntegrationTokenResource`, `PublicActionResource`, `PublicActionSubmissionResource`.

## Code Map

| Area      | Path                                    | Purpose                                                             |
| --------- | --------------------------------------- | ------------------------------------------------------------------- |
| Actions   | `packages/public-actions/src/Actions`   | Domain operations. Test these directly where possible.              |
| Data      | `packages/public-actions/src/Data`      | Structured payloads, form state, view models, and integration data. |
| Enums     | `packages/public-actions/src/Enums`     | Persisted states and Filament option values.                        |
| Models    | `packages/public-actions/src/Models`    | Eloquent records owned by the package.                              |
| Filament  | `packages/public-actions/src/Filament`  | Admin resources, pages, widgets, and settings UI.                   |
| HTTP      | `packages/public-actions/src/Http`      | Controllers, middleware, and request handling.                      |
| Jobs      | `packages/public-actions/src/Jobs`      | Queued work and async side effects.                                 |
| Providers | `packages/public-actions/src/Providers` | Registration, extension hooks, routes, migrations, and resources.   |
| Resources | `packages/public-actions/resources`     | Views, translations, assets, and package resources.                 |
| Routes    | `packages/public-actions/routes`        | Route files loaded by the service provider.                         |
| Config    | `packages/public-actions/config`        | Package configuration and publishable config.                       |
| Database  | `packages/public-actions/database`      | Migrations, seeders, and settings migrations.                       |
| Tests     | `packages/public-actions/tests`         | Package-level Pest coverage.                                        |

## Admin Surface

- Resources: `PublicActionDestinationResource`, `PublicActionDispatchAttemptResource`, `PublicActionIntegrationTokenResource`, `PublicActionResource`, `PublicActionSubmissionResource`.
- Pages: `CreatePublicAction`, `CreatePublicActionDestination`, `EditPublicAction`, `EditPublicActionDestination`, `ListPublicActionDestinations`, `ListPublicActionDispatchAttempts`, `ListPublicActionIntegrationTokens`, `ListPublicActionSubmissions`, `ListPublicActions`.

## Runtime Surface

- Controllers: `ListZapierPublicActionSubmissionsController`, `ListZapierPublicActionsController`, `ShowPublicActionController`, `ShowZapierAccountController`, `SubmitPublicActionController`, `SubmitZapierPublicActionController`.
- Routes: `packages/public-actions/routes/web.php`.
- Jobs: `DispatchPublicActionDestinationJob`.

## Data And Persistence

- Models: `PublicAction`, `PublicActionDestination`, `PublicActionDispatchAttempt`, `PublicActionIntegrationToken`, `PublicActionSubmission`.
- Migrations: `2026_05_10_190865_01_create_public_actions_table.php`, `2026_05_10_190865_02_create_public_action_destinations_table.php`, `2026_05_10_190865_03_create_public_action_submissions_table.php`, `2026_05_10_190865_04_create_public_action_dispatch_attempts_table.php`, `2026_05_10_190865_05_create_public_action_integration_tokens_table.php`.
- Config: `packages/public-actions/config/capell-public-actions.php`.
- Data objects live in `src/Data/`; use them for payloads, form state, and view models.

## Extension Points

- Contracts: `PublicActionDestinationAdapter`, `PublicActionHandler`.
- Listeners: `SubmitPublicActionFromFormSubmission`.
- Register Capell extension points, routes, migrations, settings, render hooks, and resources from service providers.

## Install And Setup

- Install with `composer require capell-app/public-actions` in the host Capell application.
- Run migrations through the host application package install flow.
- In this repository, verify package changes with `vendor/bin/pest`; do not use `php artisan`.

## Docs

- [actions-and-integrations.md](docs/actions-and-integrations.md)
- [provider-presets.md](docs/provider-presets.md)

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/public-actions/tests --configuration=phpunit.xml
```

## Maintenance Notes

- Treat public routes as untrusted input and keep validation, permission checks, and side effects inside actions or dedicated services.
- Put behaviour changes in `src/Actions/`; UI classes, commands, and controllers should call actions instead of owning domain logic.
- Use package `Data` classes at boundaries instead of passing anonymous arrays between layers.
- Use backed enums for persisted values and enum labels for Filament options.
