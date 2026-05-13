# HTML Cache

Static HTML cache, dependency indexing, and cache administration for Capell.

## At A Glance

- Package: `capell-app/html-cache`
- Namespace: `Capell\HtmlCache\`
- Surfaces: Filament admin, Livewire, console, queue, database
- Service providers: `packages/html-cache/src/Providers/HtmlCacheServiceProvider.php`
- Capell dependencies: `capell-app/admin`, `capell-app/core`, `capell-app/frontend`
- Third-party dependencies: `laravel/framework`, `lorisleiva/laravel-actions`, `silber/page-cache`, `spatie/laravel-data`, `spatie/laravel-package-tools`

## What It Adds

- Static HTML cache, dependency indexing, and cache administration for Capell.
- Admin resources: `CachedModelUrlResource`.
- Livewire components: `SiteHealthCacheMap`.
- Package setup or maintenance commands.

## Code Map

| Area      | Path                                | Purpose                                                             |
| --------- | ----------------------------------- | ------------------------------------------------------------------- |
| Actions   | `packages/html-cache/src/Actions`   | Domain operations. Test these directly where possible.              |
| Data      | `packages/html-cache/src/Data`      | Structured payloads, form state, view models, and integration data. |
| Enums     | `packages/html-cache/src/Enums`     | Persisted states and Filament option values.                        |
| Models    | `packages/html-cache/src/Models`    | Eloquent records owned by the package.                              |
| Filament  | `packages/html-cache/src/Filament`  | Admin resources, pages, widgets, and settings UI.                   |
| Livewire  | `packages/html-cache/src/Livewire`  | Interactive frontend or admin components.                           |
| HTTP      | `packages/html-cache/src/Http`      | Controllers, middleware, and request handling.                      |
| Jobs      | `packages/html-cache/src/Jobs`      | Queued work and async side effects.                                 |
| Providers | `packages/html-cache/src/Providers` | Registration, extension hooks, routes, migrations, and resources.   |
| Resources | `packages/html-cache/resources`     | Views, translations, assets, and package resources.                 |
| Config    | `packages/html-cache/config`        | Package configuration and publishable config.                       |
| Database  | `packages/html-cache/database`      | Migrations, seeders, and settings migrations.                       |
| Tests     | `packages/html-cache/tests`         | Package-level Pest coverage.                                        |

## Admin Surface

- Resources: `CachedModelUrlResource`.
- Pages: `ListCachedModelUrls`.

## Runtime Surface

- Livewire: `SiteHealthCacheMap`.
- Jobs: `RegisterCachedModelUrlsJob`.

## Commands

- `capell:static-site {--site=} {--internal : Render URLs through the current Laravel kernel} {--refresh : Delete affected HTML cache files before rendering}` (packages/html-cache/src/Console/Commands/StaticSiteCommand.php)

## Data And Persistence

- Models: `CachedModelUrl`.
- Migrations: `2026_05_10_190854_01_create_cached_model_urls_table.php`.
- Config: `packages/html-cache/config/capell-html-cache.php`.
- Data objects live in `src/Data/`; use them for payloads, form state, and view models.

## Extension Points

- Contracts: `PageCacheNotifiable`.
- Register Capell extension points, routes, migrations, settings, render hooks, and resources from service providers.

## Install And Setup

- Install with `composer require capell-app/html-cache` in the host Capell application.
- Run migrations through the host application package install flow.
- In this repository, verify package changes with `vendor/bin/pest`; do not use `php artisan`.

## Docs

- [cache-invalidation.md](docs/cache-invalidation.md)

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/html-cache/tests --configuration=phpunit.xml
```

## Maintenance Notes

- Cached HTML must be safe for anonymous visitors, signed-in users, admins, crawlers, and static exports.
- Put behaviour changes in `src/Actions/`; UI classes, commands, and controllers should call actions instead of owning domain logic.
- Use package `Data` classes at boundaries instead of passing anonymous arrays between layers.
- Use backed enums for persisted values and enum labels for Filament options.
