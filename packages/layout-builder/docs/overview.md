# LayoutBuilder

Status: **Available, schema-owning** · Kind: **package** · Tier: **free** · Bundle: **foundation** · Contexts: **admin, frontend** · Product group: **Capell Foundation**

This page is the consolidated implementation overview for the LayoutBuilder package. It is extracted from the package README, service providers, migrations, config files, routes, resources, models, actions, and the shared Capell ERD notes where available.

## What This Plugin Adds

LayoutBuilder adds reusable widgets, sections, layout containers, widget assets, layout planning, and frontend widget rendering to Capell.

- Widget and section Filament resources.
- Layout and page schema extenders.
- Modern widget configurators for hero, card grids, FAQs, galleries, pricing, process steps, stats, teams, and testimonials.
- Actions for layout plans, widget creation, reusable widget lookup, and layout placement.
- Generated admin layout preview images for saved container/widget structures.
- Commands for install, setup, widget scaffolding, demo, faker, and upgrades.

## Developer Notes

Provides the package-based layout and widget foundation used by Blog, CampaignStudio, content blocks, and theme integrations.

- LayoutBuilderServiceProvider registers widgets, schemas, views, config, commands, and extension hooks.
- Config file: capell-layout-builder.php.
- Migrations create widgets, widget_assets, sections, and add container widgets to layouts.
- Models include Widget, WidgetAsset, and Section.
- Filament resources cover widgets and sections.
- CapellLayout facade supports layout rendering concerns.
- Layout preview generation stores admin-only PNG state on layout `admin` metadata.

## Operational Notes

Lets editors build structured pages from reusable sections and widgets instead of editing raw templates.

- Adds widgets, widget_assets, and sections tables.
- Extends page and layout admin form-builder.
- Adds widget and section admin navigation.
- Adds layout builder lazy-loading config.
- Queues generated preview image refreshes after layout/widget display changes.
- May affect page cache and layout rendering.

## Data And Retention

- widgets stores workspace, type, key, content, meta, and admin JSON.
- widget_assets connects widgets to media and pageable context.
- sections stores site, type, parent, meta, and visibility windows.
- layouts can store container widget references after migration.
- layouts can store generated preview image path, signature, status, and error metadata in `admin`.
- LayoutBuilder connects to core types, sites, layouts, pages, and media.

## Screenshot Plan

- Widgets admin index.
- Create/edit widget form.
- Sections admin index.
- Layout builder screen.
- Layout table and page layout select preview images.
- Frontend page rendering LayoutBuilder widgets.

## Pitfalls

- Run LayoutBuilder install before Blog or other widget-dependent packages.
- Keep widget types and configurators registered together.
- Check layout cache after changing widgets.
- Generated layout previews are admin-only fallbacks; manually uploaded preview images take precedence. See [Generated Layout Previews](generated-layout-previews.md).

## Verification

- Run `vendor/bin/pest packages/layout-builder/tests` when package tests exist.
- Run the relevant host-app migration or package install flow in a disposable database.
- Open the listed admin or frontend surface and compare it with the screenshot plan.

## Package Manifest

- Composer name: `capell-app/layout-builder`
- Product group: Capell Foundation
- Kind: package
- Tier: free
- Bundle: foundation
- Contexts: `admin`, `frontend`
- Requires: `capell-app/admin`, `capell-app/frontend`, `capell-app/publishing-studio`
- Optional dependencies: None listed.

## Admin Surfaces

- LayoutResource (packages/layout-builder/src/Filament/Resources/Layouts/LayoutResource.php)
- CreateSection (packages/layout-builder/src/Filament/Resources/Sections/Pages/CreateSection.php)
- EditSection (packages/layout-builder/src/Filament/Resources/Sections/Pages/EditSection.php)
- ListSections (packages/layout-builder/src/Filament/Resources/Sections/Pages/ListSections.php)
- SectionResource (packages/layout-builder/src/Filament/Resources/Sections/SectionResource.php)
- CreateWidget (packages/layout-builder/src/Filament/Resources/Widgets/Pages/CreateWidget.php)
- EditWidget (packages/layout-builder/src/Filament/Resources/Widgets/Pages/EditWidget.php)
- ListWidgets (packages/layout-builder/src/Filament/Resources/Widgets/Pages/ListWidgets.php)
- WidgetResource (packages/layout-builder/src/Filament/Resources/Widgets/WidgetResource.php)

## Commands

- `capell:layout-builder-demo {--user= : Whether to associate the created demo content with the first user in the system. If not provided, content will be created without an associated user.} {--sites= : Comma-separated list of site names to target for demo content insertion. If not provided, all sites will be targeted.} {--skip-hero : Skip hero demo content after creating layout-builder demo content.}` (packages/layout-builder/src/Console/Commands/DemoCommand.php)
- `capell:layout-builder-faker {--count=25} {--force}` (packages/layout-builder/src/Console/Commands/FakerCommand.php)
- `capell:hero-demo {--sites=}` (packages/layout-builder/src/Console/Commands/Hero/DemoCommand.php)
- `capell:hero-setup` (packages/layout-builder/src/Console/Commands/Hero/SetupCommand.php)
- `capell:layout-builder-install` (packages/layout-builder/src/Console/Commands/InstallCommand.php)
- `capell:layout-builder-make-widget {name : The widget name (e.g. HeroBanner)} {--livewire : Also scaffold a Livewire widget class and view} {--F|force : Overwrite existing files after warning}` (packages/layout-builder/src/Console/Commands/MakeWidgetCommand.php)
- `capell:layout-builder-setup {--user= : Ignored — accepted for compatibility with capell:install} {--sites= : Ignored — accepted for compatibility with capell:install} {--languages= : Ignored — accepted for compatibility with capell:install} {--url= : Ignored — accepted for compatibility with capell:install}` (packages/layout-builder/src/Console/Commands/SetupCommand.php)
- `capell:layout-builder-upgrade` (packages/layout-builder/src/Console/Commands/UpgradeCommand.php)

## Routes And Config

- Config: packages/layout-builder/config/capell-layout-builder.php

## Permissions And Gates

- Gate: LayoutHealthWidgetAbstract: `super_admin`
- Gate: RecentActivityWidgetAbstract: `admin`, `super_admin`

## Migrations

- Migration: 2026_04_20_000001_create_widgets_table.php
- Migration: 2026_04_20_000002_create_widget_assets_table.php
- Migration: add_container_widgets_to_layouts_table.php
- Migration: create_sections_table.php

## ERD Excerpt

```mermaid
erDiagram
    TYPES ||--o{ WIDGETS : classifies
    TYPES ||--o{ SECTIONS : classifies
    SITES ||--o{ SECTIONS : owns
    LAYOUTS ||--o{ WIDGETS : embeds_by_json_reference
    WIDGETS ||--o{ WIDGET_ASSETS : has_assets
    PAGES ||--o{ WIDGET_ASSETS : pageable_context
    SECTIONS ||--o{ SECTIONS : parent_child
    MEDIA ||--o{ WIDGET_ASSETS : asset

    WIDGETS {
        bigint id PK
        bigint workspace_id
        bigint type_id FK
        string key
        longtext content
        json meta
        json admin
    }

    WIDGET_ASSETS {
        bigint id PK
        bigint widget_id FK
        string pageable_type
        bigint pageable_id
        string asset_type
        uuid asset_id
        string container
        int occurrence
    }

    SECTIONS {
        bigint id PK
        bigint type_id FK
        bigint site_id FK
        bigint parent_id
        json meta
        timestamp visible_from
        timestamp visible_until
    }
```

## Screenshot Automation

Deployment should read [screenshots.json](screenshots.json), install the package with demo data, resolve each admin surface or frontend URL, and write images to `public/docs/screenshots/packages/layout-builder`.

- Widgets admin index.
- Create/edit widget form.
- Sections admin index.
- Layout builder screen.
- Frontend page rendering LayoutBuilder widgets.
