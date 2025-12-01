# Capell Hero

Hero section component for the Capell layout builder. Provides schemas, form components, and a Blade widget for rendering a site/page hero.

## Overview

- Registers Content and Widget schemas for Hero
- Hero editor form component for page/content schemas
- Blade component to render Hero widget
- Integrates with Layout and Admin packages

## Features

- Schemas
    - Registers `ContentSchemaEnum::Hero` and `WidgetSchemaEnum::Hero`
- Filament form component
    - `Capell\Hero\Filament\Components\Forms\Page\HeroEditor`
- Blade widget component
    - `capell-hero::widget.hero`
- Schema extender
    - `HeroPageSchemaExtender` to add hero fields to compatible page schemas
- Commands
    - `capell-hero:install` — install and register components/schemas
    - `capell-hero:demo` — optional demo

## Installation

Prerequisites:

- Capell Admin, Frontend, and Layout packages must be installed.

Steps:

1. Run the installer:

    ```bash
    php artisan capell-hero:install
    ```

    This registers schemas, components, and translations.

2. (Optional) Seed demo data:
    ```bash
    php artisan capell-hero:demo
    ```

## Database

This package does not ship its own database tables. It relies on the Layout package tables (contents, widgets, widget_assets) to store hero content.

See the extra docs for details and references:

- Database reference: [docs/Database.md](docs/Database.md)
- API reference: [docs/API.md](docs/API.md)
