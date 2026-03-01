# Capell Blog

Article pages with archives, tags, and widgets for Capell. Ships Filament resources, Livewire pages, sitemap integration, and optional widgets for the layout builder.

## Overview

- Article page type with default Blog and Archives pages
- Tagging support via Spatie Tags (custom `Tag` model)
- Filament admin resources for Articles and Tags
- Widgets and page components (Article, Related, Archives, Tags)
- Livewire pages for Blog, Archive, and Tag views
- Sitemap integration for archives and tag pages

## Features

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
    - `capell-blog:install` — publish config/migration and migrate
    - `capell-blog:demo` — optional demo

## Installation

Prerequisites:

- Capell Admin and Frontend packages must be installed.

Steps:

1. Install and run the installer:

    ```bash
    php artisan capell-blog:install
    ```

    This will:
    - Register Filament resources and permissions
    - Publish the package config `config/tags.php` override
    - Publish the `alter_tags_table` migration
    - Run database migrations

2. (Optional) Create default pages for a given site:

    ```bash
    php artisan capell-blog:create-pages {site-id}
    ```

3. (Optional) Seed demo data:
    ```bash
    php artisan capell-blog:demo
    ```

## Database

This package integrates with the tags system and provides an additional migration:

- `database/migrations/alter_tags_table.php`

It also works with a `taggables` pivot for morph tagging and a custom `Tag` model.
Factories are available for Articles, Article types, and Tags.

See the extra docs for details and references:

- Database reference: [docs/Database.md](docs/Database.md)
- API reference: [docs/API.md](docs/API.md)
