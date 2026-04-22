# Capell Layout (Mosaic)

![Mosaic Hero Banner](./HERO_BANNER.svg)

The foundation package for Capell's content composition system. Layout is what makes pages editable in the admin: a visual builder that lets editors drop reusable **widgets** onto **layouts**, with **content items** as shared data blocks.

**[Full documentation →](https://docs.capell.app/packages/mosaic/)**

## Overview

## What this package adds

- **Visual layout builder** — a Filament form component for arranging widgets into rows, columns, and containers on any page.
- **Reusable content items** — shared blocks of data (text, media, links) that one site can use across many pages and widgets.
- **Widget library** — UI blocks (accordions, carousels, feature grids, media galleries, page lists, testimonials, banners, navigation). Each widget has its own schema, settings, and view.
- **Filament resources** for managing Contents, Widgets, Layouts, and Types from the admin panel.
- **Runtime relationships** on core Capell models (`Page::contents()`, `Site::contents()`, etc.).
- **Admin + frontend assets** published into the host app.

- Filament resources
    - Contents (list/create/edit) with relation managers for Pages and Widgets
    - Widgets (list/create/edit) with relation managers for Layouts and Assets
    - Layouts resource and tables
    - Type schemas for Content and Widget types
- Form components for the builder
    - Layout builder, widget selection, settings, translations, content selectors, etc.
- Runtime relationships
    - `Page::contents()`, `Page::widgets()`, `Page::widgetAssets()`
    - `Site::contents()`, `Type::contents()`, `Type::widgets()`
    - `Layout::layoutWidgets()` (JSON relationship)
- Assets
    - Publishes admin CSS/JS and frontend assets
    - Config file `config/capell-mosaic.php`
- Commands
    - `capell:mosaic-install` — publish assets and migrations, migrate, register resources
    - `capell:mosaic-setup` — post-install setup
    - `capell:mosaic-upgrade` — upgrade routines
    - `capell:mosaic-demo` — optional demo layouts
    - `capell:hero-setup` — wire hero widgets into default/home/results layouts
    - `capell:hero-demo` — insert demo hero content

## Installation

```sh
php artisan capell:layout-install
```

The installer registers Filament resources and permissions, publishes migrations, runs them, and registers builder components.

Optional config publish:

```sh
php artisan vendor:publish --tag=capell-mosaic-config
```

    ```bash
    php artisan capell:mosaic-install
    ```

```sh
php artisan capell:layout-demo
```

Run package upgrades after a Composer update:

```sh
php artisan capell:layout-upgrade
```

## Core concepts

**Content** — a hierarchical, translatable, workspace-aware record. Holds the data behind a widget (e.g. a hero's title and subtitle, or a card's copy). Contents can be shared across pages, have parent/child relationships, and carry publish dates and assets.

**Widget** — a placed UI component. Points at a Content record, a Type (which schema it uses), and its container layout. Widgets are positioned by `occurrence` inside a named container on a layout.

**Widget asset** — a polymorphic link between a widget and a media record (or any other model), with its own container/occurrence positioning for multi-slot widgets like carousels.

**Layout** — the structural template a page uses. Stores the container + widget graph as JSON (via `Layout::layoutWidgets()`) and is rendered through the `capell::layout.main` Blade component.

**Type** — a Capell Type row that declares _which schema_ a Content or Widget uses. Type schemas are registered with `CapellAdmin::registerSchema(...)` and resolved through the enums below.

## Runtime relationships

After install, these accessors are available on the core models:

- `Page::contents()` — content items attached to the page
- `Page::widgets()` — widgets placed on the page (via its layout)
- `Page::widgetAssets()` — media assets reached through widgets
- `Site::contents()` — every content item on the site
- `Type::contents()` — all content of a given type
- `Type::widgets()` — all widgets of a given type
- `Layout::layoutWidgets()` — the widget graph stored in the layout JSON

## Database

Three tables ship with this package (see [docs/mosaic-database.md](docs/mosaic-database.md) for the full column list):

- `contents` — reusable content records (workspace-scoped, nested set, translatable)
- `widgets` — widget instances with type, settings, and publish metadata
- `widget_assets` — polymorphic asset links for multi-slot widgets

Plus an alter to the core `layouts` table: `add_container_widgets_to_layouts_table`.

## Artisan commands

- Database reference: [docs/mosaic-database.md](docs/mosaic-database.md) · [docs.capell.app](https://docs.capell.app/packages/mosaic/)
- API reference: [docs/mosaic-api.md](docs/mosaic-api.md)
