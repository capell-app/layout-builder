# Capell Layout

Content and Widget management for Capell. Provides the layout builder, Filament resources, runtime relations, and assets for rendering widgets on the frontend.

## Overview

- Layout builder UI and form components
- Filament resources for Contents, Widgets, Layouts, and Types
- Runtime relations for Pages, Sites, Types, and Layouts
- Frontend and admin assets publishing
- Install, upgrade, and demo commands

## Features

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
    - Config file `config/capell-layout.php`
- Commands
    - `capell-layout:install` — publish assets and migrations, migrate, register resources
    - `capell-layout:upgrade` — upgrade routines
    - `capell-layout:demo` — optional demo

## Installation

Prerequisites:

- Capell Admin and Frontend packages must be installed.

Steps:

1. Run the installer:

    ```bash
    php artisan capell:layout-install
    ```

    This will:
    - Register Filament resources and permissions
    - Publish and run database migrations
    - Register builder components and schemas

2. (Optional) Publish the package config:

    ```bash
    php artisan vendor:publish --tag=capell-layout-config
    ```

## Database

This package ships migrations for the core layout entities:

- `create_contents_table.php`
- `create_widgets_table.php`
- `create_widget_assets_table.php`

Factories are available for `Content`, `Widget`, and `WidgetAsset`.

See the extra docs for details and references:

- Database reference: [docs/Database.md](docs/Database.md)
- API reference: [docs/API.md](docs/API.md)
