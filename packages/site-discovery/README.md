# Site Discovery

- Public discoverable page and URL APIs.
- HTML sitemap page type and frontend component.
- XML sitemap generation with chunking and incremental state.
- Sitemap admin page, admin actions, and generation tool.
- Lifecycle listeners that regenerate sitemap output when pages or sites change.

## At A Glance

- Package: `capell-app/site-discovery`
- Namespace: `Capell\SiteDiscovery\`
- Surfaces: Livewire, console
- Service providers: `packages/site-discovery/src/Providers/SiteDiscoveryServiceProvider.php`
- Capell dependencies: `capell-app/admin`, `capell-app/core`, `capell-app/frontend`
- Third-party dependencies: `icamys/php-sitemap-generator`

## What It Adds

- Public discoverable page and URL APIs.
- HTML sitemap page type and frontend component.
- XML sitemap generation with chunking and incremental state.
- Sitemap admin page, admin actions, and generation tool.
- Lifecycle listeners that regenerate sitemap output when pages or sites change.

## Built With

- [PHP Sitemap Generator](https://github.com/icamys/php-sitemap-generator) - XML sitemap generation.

## Code Map

| Area      | Path                                    | Purpose                                                             |
| --------- | --------------------------------------- | ------------------------------------------------------------------- |
| Actions   | `packages/site-discovery/src/Actions`   | Domain operations. Test these directly where possible.              |
| Data      | `packages/site-discovery/src/Data`      | Structured payloads, form state, view models, and integration data. |
| Enums     | `packages/site-discovery/src/Enums`     | Persisted states and Filament option values.                        |
| Filament  | `packages/site-discovery/src/Filament`  | Admin resources, pages, widgets, and settings UI.                   |
| Livewire  | `packages/site-discovery/src/Livewire`  | Interactive frontend or admin components.                           |
| Providers | `packages/site-discovery/src/Providers` | Registration, extension hooks, routes, migrations, and resources.   |
| Resources | `packages/site-discovery/resources`     | Views, translations, assets, and package resources.                 |
| Tests     | `packages/site-discovery/tests`         | Package-level Pest coverage.                                        |

## Runtime Surface

- Livewire: `Sitemap`, `SitemapTool`.

## Commands

- `capell:xml-sitemap {--site= : Only regenerate sitemaps for this site ID} {--incremental : Skip domains whose pages have not changed since the last run}` (packages/site-discovery/src/Console/Commands/XmlSitemapCommand.php)

## Data And Persistence

- Data objects live in `src/Data/`; use them for payloads, form state, and view models.

## Extension Points

- Contracts: `DiscoverableUrlSource`, `Sitemapable`.
- Listeners: `RegenerateSitemapsOnPageDeleted`, `RegenerateSitemapsOnPageSaved`, `RegenerateSitemapsOnSiteCreated`.
- Register Capell extension points, routes, migrations, settings, render hooks, and resources from service providers.

## Install And Setup

- Install with `composer require capell-app/site-discovery` in the host Capell application.
- In this repository, verify package changes with `vendor/bin/pest`; do not use `php artisan`.

## Docs

No deeper package docs are currently published under `docs/`. Add design notes there when the README would become too long.

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/site-discovery/tests --configuration=phpunit.xml
```

## Maintenance Notes

- Put behaviour changes in `src/Actions/`; UI classes, commands, and controllers should call actions instead of owning domain logic.
- Use package `Data` classes at boundaries instead of passing anonymous arrays between layers.
- Use backed enums for persisted values and enum labels for Filament options.
