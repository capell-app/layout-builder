# Capell Layout

The foundation package for Capell's content composition system. Layout is what makes pages editable in the admin: a visual builder that lets editors drop reusable **widgets** onto **layouts**, with **content items** as shared data blocks.

Every other Capell add-on (Blog, Hero, Address) builds on Layout. Install Layout first.

## What this package adds

- **Visual layout builder** — a Filament form component for arranging widgets into rows, columns, and containers on any page.
- **Reusable content items** — shared blocks of data (text, media, links) that one site can use across many pages and widgets.
- **Widget library** — UI blocks (accordions, carousels, feature grids, media galleries, page lists, testimonials, banners, navigation). Each widget has its own schema, settings, and view.
- **Filament resources** for managing Contents, Widgets, Layouts, and Types from the admin panel.
- **Runtime relationships** on core Capell models (`Page::contents()`, `Site::contents()`, etc.).
- **Admin + frontend assets** published into the host app.

## Prerequisites

- `capell-app/admin`
- `capell-app/frontend`

## Installation

```sh
php artisan capell:layout-install
```

The installer registers Filament resources and permissions, publishes migrations, runs them, and registers builder components.

Optional config publish:

```sh
php artisan vendor:publish --tag=capell-layout-config
```

Seed a demo site:

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

**Type** — a Capell Type row that declares *which schema* a Content or Widget uses. Type schemas are registered with `CapellAdmin::registerSchema(...)` and resolved through the enums below.

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

Three tables ship with this package (see [docs/Database.md](docs/Database.md) for the full column list):

- `contents` — reusable content records (workspace-scoped, nested set, translatable)
- `widgets` — widget instances with type, settings, and publish metadata
- `widget_assets` — polymorphic asset links for multi-slot widgets

Plus an alter to the core `layouts` table: `add_container_widgets_to_layouts_table`.

## Artisan commands

| Command | Purpose |
| --- | --- |
| `capell:layout-install` | Register resources, publish and run migrations |
| `capell:layout-setup` | Run the setup-only phase (used by the installer and for repairs) |
| `capell:layout-upgrade` | Apply post-upgrade routines after a Composer update |
| `capell:layout-demo` | Seed demo widgets and contents (`--sites=` and `--user` options) |

## Configuration

`config/capell-layout.php` exposes:

- `widget.skip_render_empty` (default `false`) — skip rendering widgets with no content instead of emitting an empty wrapper.
- `layout_builder.lazy` (default `env('CAPELL_LAYOUT_BUILDER_LAZY', true)`) — lazy-load the Filament layout builder UI.

## Further reading

- [Database reference](docs/Database.md) — full schema, factories, runtime relations
- [API reference](docs/API.md) — service provider, actions, enums, extension points
- Capell core docs: [Packages overview](../../../capell-4/docs/packages.md), [Extending Capell](../../../capell-4/docs/extending-capell.md)
