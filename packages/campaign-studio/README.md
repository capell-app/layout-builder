# Campaign Studio

CampaignStudio adds campaign groups, landing pages, CTA blocks, conversion goals, UTM attribution, and conversion reporting to Capell.

## At A Glance

- Package: `capell-app/campaign-studio`
- Namespace: `Capell\CampaignStudio\`
- Surfaces: Filament admin, console, database
- Service providers: `packages/campaign-studio/src/Providers/AdminServiceProvider.php`, `packages/campaign-studio/src/Providers/CampaignStudioServiceProvider.php`, `packages/campaign-studio/src/Providers/FrontendServiceProvider.php`
- Capell dependencies: `capell-app/admin`, `capell-app/core`, `capell-app/form-builder`, `capell-app/frontend`, `capell-app/insights`
- Third-party dependencies: `lorisleiva/laravel-actions`, `spatie/laravel-data`, `spatie/laravel-package-tools`

## What It Adds

CampaignStudio adds campaign groups, landing pages, CTA blocks, conversion goals, UTM attribution, and conversion reporting to Capell.

- Campaign Filament resources for groups, landing pages, goals, and CTA blocks.
- Campaign dashboard widgets.
- Page schema extender for campaign fields.
- core layout builder widget configurators for campaign hero, CTA, and lead form blocks.
- Conversion recording actions for page views, CTA clicks, and form submissions.

## Why It Matters

**For developers:** Connects Capell pages, FormBuilder, Insights, and core layout builder APIs through explicit actions and listener classes instead of inline resource logic.

**For teams:** Lets marketing and editorial teams connect landing pages to goals and see which campaign-studio convert.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Admin](https://github.com/capell-app/admin)
- [Capell Insights](../insights/README.md)
- [Capell Core](https://github.com/capell-app/core)
- [Capell Form Builder](../form-builder/README.md)
- [Capell Frontend](https://github.com/capell-app/frontend)
- Core admin/frontend layout builder APIs

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

## Code Map

| Area      | Path                                     | Purpose                                                             |
| --------- | ---------------------------------------- | ------------------------------------------------------------------- |
| Actions   | `packages/campaign-studio/src/Actions`   | Domain operations. Test these directly where possible.              |
| Data      | `packages/campaign-studio/src/Data`      | Structured payloads, form state, view models, and integration data. |
| Enums     | `packages/campaign-studio/src/Enums`     | Persisted states and Filament option values.                        |
| Models    | `packages/campaign-studio/src/Models`    | Eloquent records owned by the package.                              |
| Filament  | `packages/campaign-studio/src/Filament`  | Admin resources, pages, widgets, and settings UI.                   |
| Providers | `packages/campaign-studio/src/Providers` | Registration, extension hooks, routes, migrations, and resources.   |
| Resources | `packages/campaign-studio/resources`     | Views, translations, assets, and package resources.                 |
| Config    | `packages/campaign-studio/config`        | Package configuration and publishable config.                       |
| Database  | `packages/campaign-studio/database`      | Migrations, seeders, and settings migrations.                       |
| Tests     | `packages/campaign-studio/tests`         | Package-level Pest coverage.                                        |

## Admin Surface

- Resources: `CampaignConversionGoalResource`, `CampaignCtaBlockResource`, `CampaignGroupResource`, `CampaignLandingPageResource`.
- Pages: `CreateCampaignConversionGoal`, `CreateCampaignCtaBlock`, `CreateCampaignGroup`, `CreateCampaignLandingPage`, `EditCampaignConversionGoal`, `EditCampaignCtaBlock`, `EditCampaignGroup`, `EditCampaignLandingPage`, `ListCampaignConversionGoals`, `ListCampaignCtaBlocks`, `ListCampaignGroups`, `ListCampaignLandingPages`.
- Widgets: `CampaignCtaBlockWidgetConfigurator`, `CampaignHeroWidgetConfigurator`, `CampaignLeadFormWidgetConfigurator`, `CampaignOverviewStatsWidget`, `TopCampaignStudioWidget`, `TopLandingPagesWidget`.

## Commands

- `capell:campaign-studio-install-layouts {--force : Update existing campaign layouts}` (packages/campaign-studio/src/Console/Commands/InstallCampaignLayoutsCommand.php)

## Data And Persistence

- campaign_groups belong to sites.
- campaign_landing_pages belong to groups and target pages.
- campaign_conversion_goals define measurable outcomes.
- campaign_cta_blocks store CTA content.
- campaign_conversions connect goals, landing pages, insights visits/events, and attribution JSON.

- Models: `CampaignConversion`, `CampaignConversionGoal`, `CampaignCtaBlock`, `CampaignGroup`, `CampaignLandingPage`.
- Migrations: `2026_05_10_190843_01_create_campaign_groups_table.php`, `2026_05_10_190843_02_create_campaign_conversion_goals_table.php`, `2026_05_10_190843_03_create_campaign_landing_pages_table.php`, `2026_05_10_190843_04_create_campaign_cta_blocks_table.php`, `2026_05_10_190843_05_create_campaign_conversions_table.php`.
- Config: `packages/campaign-studio/config/capell-campaign-studio.php`.
- Data objects live in `src/Data/`; use them for payloads, form state, and view models.

## Extension Points

- Listeners: `RecordFormSubmissionConversion`, `SyncCampaignLandingPageFromPage`.
- Register Capell extension points, routes, migrations, settings, render hooks, and resources from service providers.

## Install Impact

- Adds campaign admin navigation and database tables.
- Adds campaign dashboard widgets.
- Adds config keys for conversion cookie, UTM keys, table names, and layout presets.
- May use Insights events and FormBuilder submissions when those packages are installed.
- No explicit public route is registered by this package.

## Install And Setup

- Install with `composer require capell-app/campaign-studio` in the host Capell application.
- Run migrations through the host application package install flow.
- In this repository, verify package changes with `vendor/bin/pest`; do not use `php artisan`.

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

## Docs

- [campaign-studio-api.md](docs/campaign-studio-api.md)
- [campaign-studio-database.md](docs/campaign-studio-database.md)
- [credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
- [overview.md](docs/overview.md)

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/campaign-studio/tests --configuration=phpunit.xml
```

## Maintenance Notes

- Put behaviour changes in `src/Actions/`; UI classes, commands, and controllers should call actions instead of owning domain logic.
- Use package `Data` classes at boundaries instead of passing anonymous arrays between layers.
- Use backed enums for persisted values and enum labels for Filament options.
