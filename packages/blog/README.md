<p align="center">
  <a href="https://capell.app"><img src="https://capell.app/images/logo.svg" alt="Capell" width="280"></a>
</p>

# Capell Blog

![Blog Hero Banner](./HERO_BANNER.svg)

Article publishing for Capell. Adds a dedicated **Article** page type, tagging, archives, and blog/archive/tag listing pages — all integrated with the workspace-aware editorial pipeline.

**[Full documentation →](https://docs.capell.app/packages/blog/)**

## Overview

- **Article page type** — a fully-featured page with body, excerpt, featured image, publish dates, and tags.
- **Tagging** via Spatie Laravel Tags, with a workspace-aware custom `Tag` model.
- **Default pages per site** — a Blog index, Archives page, and Tags page, created by command.
- **Livewire pages** for the blog listing, date archives, and tag views.
- **Filament resources** for managing Articles and Tags from the admin.
- **Sitemap integration** — article, archive, and tag URLs are included in the site's sitemap.
- **Layout widgets** — when the Layout package is installed, Blog registers `Article`, `Related`, `Archives`, and `Tags` widgets.

## Prerequisites

- Filament resources
    - `Articles` resource with create/edit/list pages
    - `Tags` resource with list/create/edit and relation manager
- Widgets (for Layout builder)
    - `Article`, `RelatedWidget`, `Archives`, `Tags`
- Schemas
    - Registers `ArticlePageSchema` for Page
    - Registers widget schemas when Layout package is installed
- Navigation + default pages
    - Registers Blog and Archives default pages
    - Listens to navigation creation to add entries
- Commands
    - `capell:blog-install` — publish config/migration and migrate
    - `capell:blog-demo` — optional demo

## Installation

```sh
php artisan capell:blog-install
```

The installer registers the Article page type, Filament resources, and permissions; and runs migrations.

Create the default Blog, Archives, and Tags pages for a site:

```sh
php artisan capell:blog-create-pages {site-id}
```

    ```bash
    php artisan capell:blog-install
    ```

```sh
php artisan capell:blog-demo --sites=1 --limit=20
```

## Core concepts

    ```bash
    php artisan capell:blog-create-pages {site-id}
    ```

3. (Optional) Seed demo data:
    ```bash
    php artisan capell:blog-demo
    ```

## Database

| Migration                   | Effect                                                                                                                 |
| --------------------------- | ---------------------------------------------------------------------------------------------------------------------- |
| `create_articles_table.php` | Creates `articles` (workspace-scoped, typed, layout-linked, soft-delete)                                               |
| `alter_tags_table.php`      | Adds `workspace_id`, `site_id`, `featured`, and `status` to the Spatie `tags` table, and `workspace_id` to `taggables` |

Factories ship for Articles, Article types, and Tags. See [docs/blog-database.md](docs/blog-database.md) for the full schema.

## Artisan commands

| Command                           | Purpose                                                |
| --------------------------------- | ------------------------------------------------------ |
| `capell:blog-install`             | Publish migrations and configs; run install action     |
| `capell:blog-setup`               | Setup-only phase (used by installer)                   |
| `capell:blog-create-pages {site}` | Create Blog, Archives, and Tags pages for a site       |
| `capell:blog-demo`                | Seed demo articles (`--sites=`, `--user=`, `--limit=`) |

- Database reference: [docs/blog-database.md](docs/blog-database.md) · [docs.capell.app](https://docs.capell.app/packages/blog/)
- API reference: [docs/blog-api.md](docs/blog-api.md)
