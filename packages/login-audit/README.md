# Login Audit

Status: **Available, schema-owning** · Kind: **package** · Tier: **premium** · Bundle: **operations** · Contexts: **admin** · Product group: **Capell Operations**

## What This Plugin Adds

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

## Data Model

- login_audit stores authenticatable type/id, IP address, user agent, login time, and logout time.
- Records belong polymorphically to authenticatable users.
- Config purge value defaults to 365 days.

## Install Impact

- Adds login_audit table.
- Adds settings migration.
- Adds authentication log admin resource and widget.
- Listens to Laravel auth events configured in login-audit.php.
- May send new-device or failed-login notifications depending on config.

## Commands

- None proven in this package directory.

## Admin And Access

- LoginAuditResource (packages/login-audit/src/Filament/Resources/LoginAudits/LoginAuditResource.php)

- Gate: LoginAuditsWidget: `admin`, `super_admin`

## Common Pitfalls

- Set CDN IP header config before trusting IP addresses behind a proxy.
- Confirm notification settings before production rollout.
- Run migrations before loading the resource.

## Quick Start

1. Install the package with `composer require capell-app/login-audit`.
2. Run the package migrations or the Capell package installer required by the host app.
3. Open the new admin surface or integration point and verify the result.

## Next Steps

- [docs/overview.md](docs/overview.md)
- [../diagnostics/README.md](../diagnostics/README.md)
- [docs/credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
