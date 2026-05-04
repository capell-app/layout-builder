# Mosaic

Status: **Available, schema-owning** · Kind: **package** · Tier: **free** · Bundle: **foundation** · Contexts: **admin, frontend** · Product group: **Capell Foundation**

## What This Plugin Adds

Mosaic adds reusable widgets, sections, layout containers, widget assets, layout planning, and frontend widget rendering to Capell.

- Widget and section Filament resources.
- Layout and page schema extenders.
- Modern widget configurators for hero, card grids, FAQs, galleries, pricing, process steps, stats, teams, and testimonials.
- Actions for layout plans, widget creation, reusable widget lookup, and layout placement.
- Commands for install, setup, widget scaffolding, demo, faker, and upgrades.

## Why It Matters

**For developers:** Provides the package-based layout and widget foundation used by Blog, Campaigns, content blocks, and theme integrations.

**For teams:** Lets editors build structured pages from reusable sections and widgets instead of editing raw templates.

## Screens And Workflow

Screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment.

- Widgets admin index.
- Create/edit widget form.
- Sections admin index.
- Layout builder screen.
- Frontend page rendering Mosaic widgets.

## Technical Shape

- MosaicServiceProvider registers widgets, schemas, views, config, commands, and extension hooks.
- Config file: capell-mosaic.php.
- Migrations create widgets, widget_assets, sections, and add container widgets to layouts.
- Models include Widget, WidgetAsset, and Section.
- Filament resources cover widgets and sections.
- CapellLayout facade supports layout rendering concerns.

## Data Model

- widgets stores workspace, type, key, content, meta, and admin JSON.
- widget_assets connects widgets to media and pageable context.
- sections stores site, type, parent, meta, and visibility windows.
- layouts can store container widget references after migration.
- Mosaic connects to core types, sites, layouts, pages, and media.

## Install Impact

- Adds widgets, widget_assets, and sections tables.
- Extends page and layout admin forms.
- Adds widget and section admin navigation.
- Adds layout builder lazy-loading config.
- May affect page cache and layout rendering.

## Commands

- `capell:mosaic-demo {--user= : Whether to associate the created demo content with the first user in the system. If not provided, content will be created without an associated user.} {--sites= : Comma-separated list of site names to target for demo content insertion. If not provided, all sites will be targeted.} {--skip-hero : Skip hero demo content after creating mosaic demo content.}` (packages/mosaic/src/Console/Commands/DemoCommand.php)
- `capell:mosaic-faker {--count=25} {--force}` (packages/mosaic/src/Console/Commands/FakerCommand.php)
- `capell:hero-demo {--sites=}` (packages/mosaic/src/Console/Commands/Hero/DemoCommand.php)
- `capell:hero-setup` (packages/mosaic/src/Console/Commands/Hero/SetupCommand.php)
- `capell:mosaic-install` (packages/mosaic/src/Console/Commands/InstallCommand.php)
- `capell:mosaic-make-widget {name : The widget name (e.g. HeroBanner)} {--livewire : Also scaffold a Livewire widget class and view} {--F|force : Overwrite existing files after warning}` (packages/mosaic/src/Console/Commands/MakeWidgetCommand.php)
- `capell:mosaic-setup {--user= : Ignored — accepted for compatibility with capell:install} {--sites= : Ignored — accepted for compatibility with capell:install} {--languages= : Ignored — accepted for compatibility with capell:install} {--url= : Ignored — accepted for compatibility with capell:install}` (packages/mosaic/src/Console/Commands/SetupCommand.php)
- `capell:mosaic-upgrade` (packages/mosaic/src/Console/Commands/UpgradeCommand.php)

## Admin And Access

- LayoutResource (packages/mosaic/src/Filament/Resources/Layouts/LayoutResource.php)
- CreateSection (packages/mosaic/src/Filament/Resources/Sections/Pages/CreateSection.php)
- EditSection (packages/mosaic/src/Filament/Resources/Sections/Pages/EditSection.php)
- ListSections (packages/mosaic/src/Filament/Resources/Sections/Pages/ListSections.php)
- SectionResource (packages/mosaic/src/Filament/Resources/Sections/SectionResource.php)
- CreateWidget (packages/mosaic/src/Filament/Resources/Widgets/Pages/CreateWidget.php)
- EditWidget (packages/mosaic/src/Filament/Resources/Widgets/Pages/EditWidget.php)
- ListWidgets (packages/mosaic/src/Filament/Resources/Widgets/Pages/ListWidgets.php)
- WidgetResource (packages/mosaic/src/Filament/Resources/Widgets/WidgetResource.php)

- Gate: LayoutHealthWidgetAbstract: `super_admin`
- Gate: RecentActivityWidgetAbstract: `admin`, `super_admin`

## Common Pitfalls

- Run Mosaic install before Blog or other widget-dependent packages.
- Keep widget types and configurators registered together.
- Check layout cache after changing widgets.

## Quick Start

1. Install the package with `composer require capell-app/mosaic`.
2. Run the package migrations or the Capell package installer required by the host app.
3. Open the new admin surface or integration point and verify the result.

## Next Steps

- [docs/overview.md](docs/overview.md)
- [../blog/README.md](../blog/README.md)
- [../campaigns/README.md](../campaigns/README.md)
- [../content-blocks/README.md](../content-blocks/README.md)
