# Capell Blog

Article publishing for Capell. Adds a dedicated **Article** page type, tagging, archives, and blog/archive/tag listing pages — all integrated with the workspace-aware editorial pipeline.

## What this package adds

- **Article page type** — a fully-featured page with body, excerpt, featured image, publish dates, and tags.
- **Tagging** via Spatie Laravel Tags, with a workspace-aware custom `Tag` model.
- **Default pages per site** — a Blog index, Archives page, and Tags page, created by command.
- **Livewire pages** for the blog listing, date archives, and tag views.
- **Filament resources** for managing Articles and Tags from the admin.
- **Sitemap integration** — article, archive, and tag URLs are included in the site's sitemap.
- **Layout widgets** — when the Layout package is installed, Blog registers `Article`, `Related`, `Archives`, and `Tags` widgets.

## Prerequisites

- `capell-app/admin`
- `capell-app/frontend`
- `capell-app/layout` (recommended — widgets are only registered when Layout is present)

## Installation

```sh
php artisan capell:blog-install
```

The installer registers the Article page type, Filament resources, and permissions; publishes the tags config and the `alter_tags_table` migration; and runs migrations.

Create the default Blog, Archives, and Tags pages for a site:

```sh
php artisan capell:blog-create-pages {site-id}
```

Seed demo articles and tags:

```sh
php artisan capell:blog-demo --sites=1 --limit=20
```

## Core concepts

**Article** — a workspace-aware page subtype. Everything a normal Capell page can do (translations, publish dates, layout assignment, meta, clone, soft-delete), plus tags and the article-specific content layout.

**Tag** — a workspace-aware custom model that replaces the default Spatie Tag. Tags belong to a site, carry a `featured` flag and a status, and are attached to articles (or any other taggable) via the `taggables` pivot.

**Taggable** — the pivot row linking a tag to a model. Stored in `taggables` with a `workspace_id` column, so tag assignments stage inside a workspace before they publish.

**Default pages** — `capell:blog-create-pages {site}` inserts three pages for the chosen site: a Blog index, a date-based Archives page, and a Tags page. Each renders the matching Livewire component.

## Livewire pages

Frontend listing pages under `src/Livewire/Page/`:

- `Blog` — the main article index
- `Archive` — filters articles by `year`/`month` route parameters
- `Tag` — filters articles by tag slug

## Database

| Migration | Effect |
| --- | --- |
| `create_articles_table.php` | Creates `articles` (workspace-scoped, typed, layout-linked, soft-delete) |
| `alter_tags_table.php` | Adds `workspace_id`, `site_id`, `featured`, and `status` to the Spatie `tags` table, and `workspace_id` to `taggables` |

Factories ship for Articles, Article types, and Tags. See [docs/Database.md](docs/Database.md) for the full schema.

## Artisan commands

| Command | Purpose |
| --- | --- |
| `capell:blog-install` | Publish migrations and configs; run install action |
| `capell:blog-setup` | Setup-only phase (used by installer) |
| `capell:blog-create-pages {site}` | Create Blog, Archives, and Tags pages for a site |
| `capell:blog-demo` | Seed demo articles (`--sites=`, `--user=`, `--limit=`) |

## Extending the Article schema

The Article page type registers through `BlogServiceProvider`. To add your own field to the article edit form, use a schema hook extender — see [Extending Capell §4](../../../../capell-4/docs/extending-capell.md#4-schema-hook-extenders) in the main repo.

## Further reading

- [Database reference](docs/Database.md) — tables, factories, relations
- [API reference](docs/API.md) — service provider, resources, widgets, commands
- Capell core docs: [Packages overview](../../../capell-4/docs/packages.md)
