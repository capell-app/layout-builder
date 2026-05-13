# Login Audit

Login Audit records login, failed login, logout, and admin/user activity metadata for Capell users.

## At A Glance

- Package: `capell-app/login-audit`
- Namespace: `Capell\LoginAudit\`
- Surfaces: Filament admin, database
- Service providers: `packages/login-audit/src/Providers/AdminServiceProvider.php`, `packages/login-audit/src/Providers/LoginAuditServiceProvider.php`
- Capell dependencies: `capell-app/admin`
- Third-party dependencies: `rappasoft/laravel-authentication-log`, `tapp/filament-authentication-log`

## What It Adds

Login Audit records login, failed login, logout, and admin/user activity metadata for Capell users.

- Filament resource for authentication logs.
- Dashboard widget for recent authentication activity.
- Settings schema for authentication log behaviour.
- Middleware for admin and user activity tracking.

## Why It Matters

**For developers:** Wraps Rappasoft Laravel Login Audit with Capell settings, resources, widgets, query actions, and IP resolution policy.

**For teams:** Helps site operators review access activity and spot account behaviour that needs follow-up.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Admin](https://github.com/capell-app/admin)

**Open-source packages used here**

- [Laravel Authentication Log](https://github.com/rappasoft/laravel-authentication-log) - authentication event storage for login, logout, IP, and user-agent history.
- [Filament Authentication Log](https://github.com/TappNetwork/filament-authentication-log) - the Filament UI layer for reviewing authentication activity inside the admin panel.

**Linked package previews**

[![Laravel Authentication Log GitHub preview](https://opengraph.githubassets.com/capell-readme/rappasoft/laravel-authentication-log)](https://github.com/rappasoft/laravel-authentication-log)

[![Filament Authentication Log GitHub preview](https://opengraph.githubassets.com/capell-readme/TappNetwork/filament-authentication-log)](https://github.com/TappNetwork/filament-authentication-log)

## Screens And Workflow

Screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment.

- Authentication logs admin index.
- Authentication log table filters.
- Dashboard widget.
- Authentication log settings screen.

## Technical Shape

- LoginAuditServiceProvider and AdminServiceProvider register the package.
- Config file: login-audit.php.
- Migration creates login_audit.
- Model: LoginAudit.
- Filament resource: LoginAuditResource.
- Middleware: AdminActivityMiddleware and UserActivityMiddleware.

## Code Map

| Area      | Path                                 | Purpose                                                           |
| --------- | ------------------------------------ | ----------------------------------------------------------------- |
| Actions   | `packages/login-audit/src/Actions`   | Domain operations. Test these directly where possible.            |
| Models    | `packages/login-audit/src/Models`    | Eloquent records owned by the package.                            |
| Filament  | `packages/login-audit/src/Filament`  | Admin resources, pages, widgets, and settings UI.                 |
| HTTP      | `packages/login-audit/src/Http`      | Controllers, middleware, and request handling.                    |
| Providers | `packages/login-audit/src/Providers` | Registration, extension hooks, routes, migrations, and resources. |
| Resources | `packages/login-audit/resources`     | Views, translations, assets, and package resources.               |
| Config    | `packages/login-audit/config`        | Package configuration and publishable config.                     |
| Database  | `packages/login-audit/database`      | Migrations, seeders, and settings migrations.                     |
| Tests     | `packages/login-audit/tests`         | Package-level Pest coverage.                                      |

## Admin Surface

- Resources: `LoginAuditResource`.
- Widgets: `LoginAuditsWidget`.
- Settings: `LoginAuditSettings`.

## Data And Persistence

- login_audit stores authenticatable type/id, IP address, user agent, login time, and logout time.
- Records belong polymorphically to authenticatable users.
- Config purge value defaults to 365 days.

- Models: `LoginAudit`.
- Migrations: `2026_05_10_190857_01_create_login_audit_table.php`.
- Config: `packages/login-audit/config/login-audit.php`.

## Extension Points

- Register Capell extension points, routes, migrations, settings, render hooks, and resources from service providers.

## Install Impact

- Adds login_audit table.
- Adds settings migration.
- Adds authentication log admin resource and widget.
- Listens to Laravel auth events configured in login-audit.php.
- May send new-device or failed-login notifications depending on config.

## Install And Setup

- Install with `composer require capell-app/login-audit` in the host Capell application.
- Run migrations through the host application package install flow.
- In this repository, verify package changes with `vendor/bin/pest`; do not use `php artisan`.

## Admin And Access

- LoginAuditResource (packages/login-audit/src/Filament/Resources/LoginAudits/LoginAuditResource.php)

- Gate: LoginAuditsWidget: `admin`, `super_admin`

## Common Pitfalls

- Set CDN IP header config before trusting IP addresses behind a proxy.
- Confirm notification settings before production rollout.
- Run migrations before loading the resource.

## Docs

- [credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
- [overview.md](docs/overview.md)
- [settings-and-ip-resolution.md](docs/settings-and-ip-resolution.md)

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/login-audit/tests --configuration=phpunit.xml
```

## Maintenance Notes

- Put behaviour changes in `src/Actions/`; UI classes, commands, and controllers should call actions instead of owning domain logic.
