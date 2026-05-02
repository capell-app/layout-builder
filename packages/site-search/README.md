# Capell Site Search

**Product group:** Capell Search & SEO
**Tier:** Premium

Site Search provides a small search contract plus database and Scout-backed drivers for public Capell search experiences.

It gives projects a default LIKE-query implementation for simple installs, while keeping the backend swappable for Scout, Meilisearch, Algolia, Typesense, or a custom service.

## When to install it

Install Site Search when a public site needs keyword search but the project should not hard-code a search engine into Core or Frontend.

## Quick install

```bash
composer require capell-app/site-search
php artisan optimize:clear
```

If the project uses Scout, configure the driver to `scout` and point it at the searchable model.

## What developers get

| Area            | Capability                                                        |
| --------------- | ----------------------------------------------------------------- |
| Contract        | `SiteSearch::search()` and `SiteSearch::highlight()`              |
| Database driver | LIKE-query search against configured table/columns                |
| Scout driver    | Adapter for models using Laravel Scout                            |
| Results         | `SearchResultData` DTOs with title, URL, excerpt, type, and score |

## Configuration

The provider reads `capell-site-search` config values. The important keys are:

| Key                | Default                        | Purpose                                        |
| ------------------ | ------------------------------ | ---------------------------------------------- |
| `driver`           | `database`                     | Search backend: `database` or `scout`          |
| `database.table`   | `pages`                        | Table queried by `DatabaseSiteSearch`          |
| `database.columns` | `['title', 'excerpt', 'body']` | Columns searched with escaped LIKE expressions |
| `scout.model`      | project-defined                | Searchable model class for `ScoutSiteSearch`   |
| `excerpt_length`   | `200`                          | Maximum generated excerpt length               |

## Reference

See [Site Search reference](docs/site-search.md) for driver behaviour, binding examples, and extension notes.
