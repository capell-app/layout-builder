# Access Gate

Access gating foundations for Capell CMS.

## At A Glance

- Package: `capell-app/access-gate`
- Namespace: `Capell\AccessGate\`
- Surfaces: Filament admin, console, HTTP, database
- Service providers: `packages/access-gate/src/Providers/AccessGateServiceProvider.php`
- Capell dependencies: `capell-app/core`
- Third-party dependencies: `laravel/framework`, `lorisleiva/laravel-actions`

## What It Adds

- Access gating foundations for Capell CMS.
- Admin resources: `AccessAreaResource`, `AccessGateEventResource`, `BrowserTokenResource`, `ClaimTokenResource`, `GrantResource`, `RegistrationResource`.
- Package setup or maintenance commands.

## Code Map

| Area      | Path                                 | Purpose                                                             |
| --------- | ------------------------------------ | ------------------------------------------------------------------- |
| Actions   | `packages/access-gate/src/Actions`   | Domain operations. Test these directly where possible.              |
| Data      | `packages/access-gate/src/Data`      | Structured payloads, form state, view models, and integration data. |
| Enums     | `packages/access-gate/src/Enums`     | Persisted states and Filament option values.                        |
| Models    | `packages/access-gate/src/Models`    | Eloquent records owned by the package.                              |
| Filament  | `packages/access-gate/src/Filament`  | Admin resources, pages, widgets, and settings UI.                   |
| HTTP      | `packages/access-gate/src/Http`      | Controllers, middleware, and request handling.                      |
| Providers | `packages/access-gate/src/Providers` | Registration, extension hooks, routes, migrations, and resources.   |
| Resources | `packages/access-gate/resources`     | Views, translations, assets, and package resources.                 |
| Routes    | `packages/access-gate/routes`        | Route files loaded by the service provider.                         |
| Config    | `packages/access-gate/config`        | Package configuration and publishable config.                       |
| Database  | `packages/access-gate/database`      | Migrations, seeders, and settings migrations.                       |
| Tests     | `packages/access-gate/tests`         | Package-level Pest coverage.                                        |

## Admin Surface

- Resources: `AccessAreaResource`, `AccessGateEventResource`, `BrowserTokenResource`, `ClaimTokenResource`, `GrantResource`, `RegistrationResource`.
- Pages: `CreateAccessArea`, `EditAccessArea`, `ListAccessAreas`, `ListAccessGateEvents`, `ListBrowserTokens`, `ListClaimTokens`, `ListGrants`, `ListRegistrations`.

## Runtime Surface

- Controllers: `AccessGateStatusController`, `ClaimAccessGateTokenController`, `LogoutAccessGateController`, `ShowAccessRequestController`, `StoreAccessRequestController`.
- Routes: `packages/access-gate/routes/web.php`.

## Commands

- `capell:access-gate-doctor` (packages/access-gate/src/Console/Commands/AccessGateDoctorCommand.php)
- `capell:access-gate-install` (packages/access-gate/src/Console/Commands/AccessGateInstallCommand.php)
- `capell:access-gate-setup` (packages/access-gate/src/Console/Commands/AccessGateSetupCommand.php)

## Data And Persistence

- Models: `AccessGateModel`, `Area`, `BrowserToken`, `ClaimToken`, `Event`, `Grant`, `Registration`.
- Migrations: `2026_05_10_190838_01_create_access_gate_areas_table.php`, `2026_05_10_190838_02_create_access_gate_registrations_table.php`, `2026_05_10_190838_03_create_access_gate_grants_table.php`, `2026_05_10_190838_04_create_access_gate_claim_tokens_table.php`, `2026_05_10_190838_05_create_access_gate_browser_tokens_table.php`, `2026_05_10_190838_06_create_access_gate_events_table.php`, `2026_05_10_190838_07_add_site_id_to_access_gate_areas_table.php`, `2026_05_12_120000_08_add_schedule_to_access_gate_areas_table.php`, `2026_05_12_120001_09_add_download_resolver_indexes_to_access_gate_tables.php`.
- Config: `packages/access-gate/config/access-gate.php`.
- Data objects live in `src/Data/`; use them for payloads, form state, and view models.

## Extension Points

- Contracts: `AccessRequestMethod`, `RegistrationField`.
- Events: `RegistrationApproved`.
- Register Capell extension points, routes, migrations, settings, render hooks, and resources from service providers.

## Install And Setup

- Install with `composer require capell-app/access-gate` in the host Capell application.
- Run migrations through the host application package install flow.
- In this repository, verify package changes with `vendor/bin/pest`; do not use `php artisan`.

## Docs

- [access-requests.md](docs/access-requests.md)

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/access-gate/tests --configuration=phpunit.xml
```

## Maintenance Notes

- Treat public routes as untrusted input and keep validation, permission checks, and side effects inside actions or dedicated services.
- Put behaviour changes in `src/Actions/`; UI classes, commands, and controllers should call actions instead of owning domain logic.
- Use package `Data` classes at boundaries instead of passing anonymous arrays between layers.
- Use backed enums for persisted values and enum labels for Filament options.
