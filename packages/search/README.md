# Search

Status: **Available, schema-owning** · Kind: **package** · Tier: **premium** · Bundle: **search-seo** · Contexts: **admin, frontend, console** · Product group: **Capell Search & SEO**

## What This Plugin Adds

Search adds a frontend search route, configurable search drivers, result click tracking, query logging, and admin search insights.

- Frontend search controller route.
- Database and Scout search drivers.
- Header search render hook.
- Search insights widgets.
- Settings schema and dashboard contributor.
- Actions for search, normalization, logging, click tracking, and purging logs.

## Why It Matters

**For developers:** Uses a Search contract and driver classes so search can start with database queries and move to Scout without changing the frontend surface.

**For teams:** Lets visitors search site content and lets operators review what people searched for, including zero-result terms.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Admin](https://github.com/capell-app/admin)
- [Capell Core](https://github.com/capell-app/core)
- [Capell Frontend](https://github.com/capell-app/frontend)

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

- Frontend search results page.
- Header search field.
- Top searches widget.
- Trending searches widget.
- Zero-result searches widget.
- Site search settings screen.

## Technical Shape

- SearchServiceProvider and AdminServiceProvider register the package.
- Config file: capell-search.php.
- Route: GET search by default, named capell-frontend.search.
- Model: SearchLog.
- Drivers: DatabaseSearch and ScoutSearch.
- Command: PurgeSearchLogsCommand.

## Data Model

- search_logs stores site, language, query, normalized query, result count, clicked URL, and searched_at.
- Logs connect to sites and languages.
- Retention defaults to 180 days in config.

## Install Impact

- Adds search_logs table and settings migration.
- Adds frontend search route.
- Adds optional header search render hook.
- Adds dashboard insights widgets.
- Adds config keys for driver, route, result count, excerpts, logging, hashing, and retention.

## Commands

- `search:purge {--days= : Override retention days}` (packages/search/src/Console/Commands/PurgeSearchLogsCommand.php)

## Admin And Access

- None proven in this package directory.

- Gate: SearchOverviewStatsWidget: `admin`, `super_admin`
- Gate: TopSearchesWidget: `admin`, `super_admin`
- Gate: TrendingSearchesWidget: `admin`, `super_admin`
- Gate: ZeroResultSearchesWidget: `admin`, `super_admin`

## Common Pitfalls

- Database driver config must point at searchable columns that exist.
- Minimum query length defaults to 2 characters.
- Disable logging or hashing according to privacy requirements.
- Run log purge if retention needs enforcement.

## Quick Start

1. Install the package with `composer require capell-app/search`.
2. Run the package migrations or the Capell package installer required by the host app.
3. Open the new admin or frontend surface and verify the result.

## Next Steps

- [docs/overview.md](docs/overview.md)
- [../insights/README.md](../insights/README.md)
- [../seo-suite/README.md](../seo-suite/README.md)
- [docs/credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
