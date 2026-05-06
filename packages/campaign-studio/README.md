# CampaignStudio

Status: **Available, schema-owning** · Kind: **package** · Tier: **premium** · Bundle: **growth** · Contexts: **admin, frontend** · Product group: **Capell Growth**

## What This Plugin Adds

CampaignStudio adds campaign groups, landing pages, CTA blocks, conversion goals, UTM attribution, and conversion reporting to Capell.

- Campaign Filament resources for groups, landing pages, goals, and CTA blocks.
- Campaign dashboard widgets.
- Page schema extender for campaign fields.
- LayoutBuilder widget configurators for campaign hero, CTA, and lead form blocks.
- Conversion recording actions for page views, CTA clicks, and form submissions.

## Why It Matters

**For developers:** Connects Capell pages, FormBuilder, Insights, and LayoutBuilder through explicit actions and listener classes instead of inline resource logic.

**For teams:** Lets marketing and editorial teams connect landing pages to goals and see which campaign-studio convert.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Admin](https://github.com/capell-app/admin)
- [Capell Insights](../insights/README.md)
- [Capell Core](https://github.com/capell-app/core)
- [Capell Form Builder](../form-builder/README.md)
- [Capell Frontend](https://github.com/capell-app/frontend)
- [Capell Layout Builder](../layout-builder/README.md)

**Open-source packages used here**

- [Laravel Actions](https://github.com/lorisleiva/laravel-actions) - single-purpose action classes that keep package workflows out of controllers and Filament resources.
- [Spatie Laravel Data](https://github.com/spatie/laravel-data) - typed data objects for package boundaries, form state, settings, and structured results.
- [Spatie Laravel Package Tools](https://github.com/spatie/laravel-package-tools) - Laravel package bootstrapping for config, migrations, commands, translations, and service provider setup.

**Linked package previews**

[![Laravel Actions GitHub preview](https://opengraph.githubassets.com/capell-readme/lorisleiva/laravel-actions)](https://github.com/lorisleiva/laravel-actions)

[![Spatie Laravel Data GitHub preview](https://opengraph.githubassets.com/capell-readme/spatie/laravel-data)](https://github.com/spatie/laravel-data)

[![Spatie Laravel Package Tools GitHub preview](https://opengraph.githubassets.com/capell-readme/spatie/laravel-package-tools)](https://github.com/spatie/laravel-package-tools)

## Screens And Workflow

Screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment.

- Campaign groups index.
- Campaign landing pages index.
- Campaign conversion goals form.
- CTA block form.
- Campaign dashboard widgets.
- Frontend landing page with campaign widgets.

## Technical Shape

- CampaignStudioServiceProvider, AdminServiceProvider, and FrontendServiceProvider register package surfaces.
- Config file: capell-campaign-studio.php.
- Migrations create campaign groups, goals, landing pages, CTA blocks, and conversions.
- Filament resources cover each owned model.
- Listeners sync landing pages and form submission conversions.

## Data Model

- campaign_groups belong to sites.
- campaign_landing_pages belong to groups and target pages.
- campaign_conversion_goals define measurable outcomes.
- campaign_cta_blocks store CTA content.
- campaign_conversions connect goals, landing pages, insights visits/events, and attribution JSON.

## Install Impact

- Adds campaign admin navigation and database tables.
- Adds campaign dashboard widgets.
- Adds config keys for conversion cookie, UTM keys, table names, and layout presets.
- May use Insights events and FormBuilder submissions when those packages are installed.
- No explicit public route is registered by this package.

## Commands

- `capell:campaign-studio-install-layouts {--force : Update existing campaign layouts}` (packages/campaign-studio/src/Console/Commands/InstallCampaignLayoutsCommand.php)

## Admin And Access

- CampaignConversionGoalResource (packages/campaign-studio/src/Filament/Resources/CampaignConversionGoals/CampaignConversionGoalResource.php)
- CreateCampaignConversionGoal (packages/campaign-studio/src/Filament/Resources/CampaignConversionGoals/Pages/CreateCampaignConversionGoal.php)
- EditCampaignConversionGoal (packages/campaign-studio/src/Filament/Resources/CampaignConversionGoals/Pages/EditCampaignConversionGoal.php)
- ListCampaignConversionGoals (packages/campaign-studio/src/Filament/Resources/CampaignConversionGoals/Pages/ListCampaignConversionGoals.php)
- CampaignCtaBlockResource (packages/campaign-studio/src/Filament/Resources/CampaignCtaBlocks/CampaignCtaBlockResource.php)
- CreateCampaignCtaBlock (packages/campaign-studio/src/Filament/Resources/CampaignCtaBlocks/Pages/CreateCampaignCtaBlock.php)
- EditCampaignCtaBlock (packages/campaign-studio/src/Filament/Resources/CampaignCtaBlocks/Pages/EditCampaignCtaBlock.php)
- ListCampaignCtaBlocks (packages/campaign-studio/src/Filament/Resources/CampaignCtaBlocks/Pages/ListCampaignCtaBlocks.php)
- CampaignGroupResource (packages/campaign-studio/src/Filament/Resources/CampaignGroups/CampaignGroupResource.php)
- CreateCampaignGroup (packages/campaign-studio/src/Filament/Resources/CampaignGroups/Pages/CreateCampaignGroup.php)
- EditCampaignGroup (packages/campaign-studio/src/Filament/Resources/CampaignGroups/Pages/EditCampaignGroup.php)
- ListCampaignGroups (packages/campaign-studio/src/Filament/Resources/CampaignGroups/Pages/ListCampaignGroups.php)
- CampaignLandingPageResource (packages/campaign-studio/src/Filament/Resources/CampaignLandingPages/CampaignLandingPageResource.php)
- CreateCampaignLandingPage (packages/campaign-studio/src/Filament/Resources/CampaignLandingPages/Pages/CreateCampaignLandingPage.php)
- EditCampaignLandingPage (packages/campaign-studio/src/Filament/Resources/CampaignLandingPages/Pages/EditCampaignLandingPage.php)
- ListCampaignLandingPages (packages/campaign-studio/src/Filament/Resources/CampaignLandingPages/Pages/ListCampaignLandingPages.php)

- Gate: CampaignOverviewStatsWidget: `admin`, `super_admin`
- Gate: TopCampaignStudioWidget: `admin`, `super_admin`
- Gate: TopLandingPagesWidget: `admin`, `super_admin`

## Common Pitfalls

- Install dependent packages before expecting attribution from form-builder or insights.
- Check UTM keys before launch.
- Create conversion goals before reporting on landing page success.

## Quick Start

1. Install the package with `composer require capell-app/campaign-studio`.
2. Run the package migrations or the Capell package installer required by the host app.
3. Open the new admin surface or integration point and verify the result.

## Next Steps

- [docs/overview.md](docs/overview.md)
- [../insights/README.md](../insights/README.md)
- [../form-builder/README.md](../form-builder/README.md)
- [../layout-builder/README.md](../layout-builder/README.md)
- [docs/credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
