# LayoutBuilder

Status: **Available, schema-owning** · Kind: **package** · Tier: **free** · Bundle: **foundation** · Contexts: **admin, frontend** · Product group: **Capell Foundation**

## What This Plugin Adds

LayoutBuilder adds reusable widgets, sections, layout containers, widget assets, layout planning, and frontend widget rendering to Capell.

- Widget and section Filament resources.
- Layout and page schema extenders.
- Modern widget configurators for hero, card grids, FAQs, galleries, pricing, process steps, stats, teams, and testimonials.
- Actions for layout plans, widget creation, reusable widget lookup, and layout placement.
- Generated admin layout preview images for saved container/widget structures.
- Commands for install, setup, widget scaffolding, demo, faker, and upgrades.

## Why It Matters

**For developers:** Provides the package-based layout and widget foundation used by Blog, CampaignStudio, content blocks, and theme integrations.

**For teams:** Lets editors build structured pages from reusable sections and widgets instead of editing raw templates.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Admin](https://github.com/capell-app/admin)
- [Capell Frontend](https://github.com/capell-app/frontend)
- [Capell Navigation](../navigation/README.md)
- [Capell Publishing Studio](../publishing-studio/README.md)

**Open-source packages used here**

- No extra third-party Composer package beyond the Capell package stack is required here.

## Screens And Workflow

Screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment.

- Widgets admin index.
- Create/edit widget form.
- Sections admin index.
- Layout builder screen.
- Layout table and page layout select preview images.
- Frontend page rendering LayoutBuilder widgets.

## Technical Shape

- LayoutBuilderServiceProvider registers widgets, schemas, views, config, commands, and extension hooks.
- Config file: capell-layout-builder.php.
- Migrations create widgets, widget_assets, sections, and add container widgets to layouts.
- Models include Widget, WidgetAsset, and Section.
- Filament resources cover widgets and sections.
- CapellLayout facade supports layout rendering concerns.
- Layout preview generation stores admin-only PNG state on the layout `admin` metadata.

## Data Model

- widgets stores workspace, type, key, content, meta, and admin JSON.
- widget_assets connects widgets to media and pageable context.
- sections stores site, type, parent, meta, and visibility windows.
- layouts can store container widget references after migration.
- layouts can store generated preview image path, signature, status, and error metadata in `admin`.
- LayoutBuilder connects to core types, sites, layouts, pages, and media.

## Install Impact

- Adds widgets, widget_assets, and sections tables.
- Extends page and layout admin form-builder.
- Adds widget and section admin navigation.
- Adds layout builder lazy-loading config.
- Queues generated preview image refreshes after layout/widget display changes.
- May affect page cache and layout rendering.

## Commands

- `capell:layout-builder-demo {--user= : Whether to associate the created demo content with the first user in the system. If not provided, content will be created without an associated user.} {--sites= : Comma-separated list of site names to target for demo content insertion. If not provided, all sites will be targeted.} {--skip-hero : Skip hero demo content after creating layout-builder demo content.}` (packages/layout-builder/src/Console/Commands/DemoCommand.php)
- `capell:layout-builder-faker {--count=25} {--force}` (packages/layout-builder/src/Console/Commands/FakerCommand.php)
- `capell:hero-demo {--sites=}` (packages/layout-builder/src/Console/Commands/Hero/DemoCommand.php)
- `capell:hero-setup` (packages/layout-builder/src/Console/Commands/Hero/SetupCommand.php)
- `capell:layout-builder-install` (packages/layout-builder/src/Console/Commands/InstallCommand.php)
- `capell:layout-builder-make-widget {name : The widget name (e.g. HeroBanner)} {--livewire : Also scaffold a Livewire widget class and view} {--F|force : Overwrite existing files after warning}` (packages/layout-builder/src/Console/Commands/MakeWidgetCommand.php)
- `capell:layout-builder-setup {--user= : Ignored — accepted for compatibility with capell:install} {--sites= : Ignored — accepted for compatibility with capell:install} {--languages= : Ignored — accepted for compatibility with capell:install} {--url= : Ignored — accepted for compatibility with capell:install}` (packages/layout-builder/src/Console/Commands/SetupCommand.php)
- `capell:layout-builder-upgrade` (packages/layout-builder/src/Console/Commands/UpgradeCommand.php)

## Admin And Access

- LayoutResource (packages/layout-builder/src/Filament/Resources/Layouts/LayoutResource.php)
- CreateSection (packages/layout-builder/src/Filament/Resources/Sections/Pages/CreateSection.php)
- EditSection (packages/layout-builder/src/Filament/Resources/Sections/Pages/EditSection.php)
- ListSections (packages/layout-builder/src/Filament/Resources/Sections/Pages/ListSections.php)
- SectionResource (packages/layout-builder/src/Filament/Resources/Sections/SectionResource.php)
- CreateWidget (packages/layout-builder/src/Filament/Resources/Widgets/Pages/CreateWidget.php)
- EditWidget (packages/layout-builder/src/Filament/Resources/Widgets/Pages/EditWidget.php)
- ListWidgets (packages/layout-builder/src/Filament/Resources/Widgets/Pages/ListWidgets.php)
- WidgetResource (packages/layout-builder/src/Filament/Resources/Widgets/WidgetResource.php)

- Gate: LayoutHealthWidgetAbstract: `super_admin`
- Gate: RecentActivityWidgetAbstract: `admin`, `super_admin`

## Common Pitfalls

- Run LayoutBuilder install before Blog or other widget-dependent packages.
- Keep widget types and configurators registered together.
- Check layout cache after changing widgets.
- Generated layout previews are admin-only fallbacks; manually uploaded preview images take precedence.

## Quick Start

1. Install the package with `composer require capell-app/layout-builder`.
2. Run the package migrations or the Capell package installer required by the host app.
3. Open the new admin surface or integration point and verify the result.

## Next Steps

- [docs/overview.md](docs/overview.md)
- [docs/generated-layout-previews.md](docs/generated-layout-previews.md)
- [../blog/README.md](../blog/README.md)
- [../campaign-studio/README.md](../campaign-studio/README.md)
- [docs/credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
