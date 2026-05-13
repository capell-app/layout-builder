# SEO Suite

SEO Suite adds metadata panels, structured data, broken link tracking, Search Console insights, AI-assisted content briefs, AI Discovery output, crawler policy controls, and publish checks.

## At A Glance

- Package: `capell-app/seo-suite`
- Namespace: `Capell\SeoSuite\`
- Surfaces: Filament admin, console, HTTP, database
- Service providers: `packages/seo-suite/src/Providers/SeoSuiteServiceProvider.php`
- Capell dependencies: `capell-app/admin`, `capell-app/frontend`, `capell-app/insights`, `capell-app/site-discovery`
- Third-party dependencies: `prism-php/prism`

## What It Adds

SEO Suite adds metadata panels, structured data, broken link tracking, Search Console insights, AI-assisted content briefs, AI Discovery output, crawler policy controls, and publish checks.

- Page and site SEO schema extenders.
- SEO audit, AI Discovery, broken links, not-found URLs, and translation coverage pages.
- AI creator actions for briefs, images, layouts, metadata suggestions, and draft application.
- AI Discovery for `llms.txt`, optional `llms-full.txt`, page Markdown URLs, `Accept: text/markdown`, configurable AI crawler rules, and page-readiness audits.
- Search Console sync and dashboard-dashboard_reports.

## Why It Matters

**For developers:** Exposes SEO work as actions, contracts, data objects, settings schemas, and extenders that connect to core pages, sites, translations, routes, and optional AI providers.

**For teams:** Gives editors and site operators practical checks before publishing and operational dashboard-dashboard_reports after launch.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Admin](https://github.com/capell-app/admin)
- [Capell Insights](../insights/README.md)
- [Capell Frontend](https://github.com/capell-app/frontend)
- [Capell Site Discovery](../site-discovery/README.md)

**Open-source packages used here**

- [Prism PHP](https://github.com/prism-php/prism) - AI provider abstraction used by SEO Suite for assisted content and metadata workflows.

**Linked package previews**

[![Prism PHP GitHub preview](https://opengraph.githubassets.com/capell-readme/prism-php/prism)](https://github.com/prism-php/prism)

## Screens And Workflow

Screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment.

- Page SEO panel.
- SEO audit page.
- Broken links page.
- Translation coverage page.
- AI creator action modal.
- Search Console insights panel.

## Technical Shape

- SeoSuiteServiceProvider registers settings, pages, extenders, commands, routes, and views.
- Config files: capell-seo-suite.php and exchanger.php.
- Migrations create broken links, page SEO snapshots, Search Console metrics, AI creator contexts, AI histories, AI sessions, AI Discovery profiles, crawler rules, and generated-output snapshots.
- Commands cover install, setup, AI cache, AI usage, and OpenAI connection testing.
- Controllers: LlmsTxtController, LlmsFullTxtController, PageMarkdownController, RobotsTxtController.

## Code Map

| Area      | Path                               | Purpose                                                             |
| --------- | ---------------------------------- | ------------------------------------------------------------------- |
| Actions   | `packages/seo-suite/src/Actions`   | Domain operations. Test these directly where possible.              |
| Data      | `packages/seo-suite/src/Data`      | Structured payloads, form state, view models, and integration data. |
| Enums     | `packages/seo-suite/src/Enums`     | Persisted states and Filament option values.                        |
| Models    | `packages/seo-suite/src/Models`    | Eloquent records owned by the package.                              |
| Filament  | `packages/seo-suite/src/Filament`  | Admin resources, pages, widgets, and settings UI.                   |
| Livewire  | `packages/seo-suite/src/Livewire`  | Interactive frontend or admin components.                           |
| HTTP      | `packages/seo-suite/src/Http`      | Controllers, middleware, and request handling.                      |
| Providers | `packages/seo-suite/src/Providers` | Registration, extension hooks, routes, migrations, and resources.   |
| Resources | `packages/seo-suite/resources`     | Views, translations, assets, and package resources.                 |
| Config    | `packages/seo-suite/config`        | Package configuration and publishable config.                       |
| Database  | `packages/seo-suite/database`      | Migrations, seeders, and settings migrations.                       |
| Tests     | `packages/seo-suite/tests`         | Package-level Pest coverage.                                        |

## Admin Surface

- Pages: `AiDiscoveryPage`, `AiDiscoveryTable`, `BrokenLinksPage`, `BrokenLinksTable`, `ListPageSeoAuditWidget`, `NotFoundUrlsPage`, `SeoAuditPage`, `SeoAuditTable`, `SeoSuiteSettingsPage`, `TranslationCoveragePage`, `TranslationCoverageTable`.
- Widgets: `AiMetricsWidgetAbstract`, `AiUsageWidget`, `EditPageSeoAuditWidget`, `ListPageSeoAuditWidget`.
- Settings: `AIOrchestratorSettings`, `SeoSuiteSettings`.

## Runtime Surface

- Controllers: `LlmsFullTxtController`, `LlmsTxtController`, `PageMarkdownController`, `RobotsTxtController`.

## Commands

- `capell:admin-clear-ai-cache` (packages/seo-suite/src/Console/Commands/ClearAiCacheCommand.php)
- `capell:admin-monitor-ai-usage` (packages/seo-suite/src/Console/Commands/MonitorAiUsageCommand.php)
- `capell:admin-test-openai` (packages/seo-suite/src/Console/Commands/TestOpenAiConnectionCommand.php)
- `capell:seo-suite-install` (packages/seo-suite/src/Console/Commands/InstallCommand.php)
- `capell:seo-suite-setup` (packages/seo-suite/src/Console/Commands/SetupCommand.php)

## Data And Persistence

- broken_links stores page, target URL, HTTP status, and last check time.
- page_seo_snapshots store page SEO report state.
- search_console_url_metrics store imported Search Console values.
- ai_creator_contexts, ai_generation_histories, and ai_creator_sessions store AI workflow state.
- ai_discovery_site_profiles, ai_discovery_page_profiles, ai_discovery_crawler_rules, and ai_discovery_snapshots store AI Discovery configuration, robots controls, and generated document state.
- SEO data connects to sites, pages, languages, users, and publishing-studio.

- Models: `AIGenerationHistory`, `AiCreatorContext`, `AiCreatorSession`, `AiDiscoveryCrawlerRule`, `AiDiscoveryPageProfile`, `AiDiscoverySiteProfile`, `AiDiscoverySnapshot`, `BrokenLink`, `PageSeoSnapshot`, `SearchConsoleUrlMetric`.
- Migrations: `2026_05_10_190870_01_create_ai_creator_contexts_table.php`, `2026_05_10_190870_02_create_ai_generation_histories_table.php`, `2026_05_10_190870_03_create_ai_creator_sessions_table.php`, `2026_05_10_190870_04_create_ai_discovery_crawler_rules_table.php`, `2026_05_10_190870_05_create_ai_discovery_page_profiles_table.php`, `2026_05_10_190870_06_create_ai_discovery_site_profiles_table.php`, `2026_05_10_190870_07_create_ai_discovery_snapshots_table.php`, `2026_05_10_190870_08_create_broken_links_table.php`, `2026_05_10_190870_09_create_page_seo_snapshots_table.php`, `2026_05_10_190870_10_create_search_console_url_metrics_table.php`, `2026_05_10_190870_11_remove_redirect_opportunities_count_from_page_seo_snapshots_table.php`.
- Config: `packages/seo-suite/config/capell-seo-suite.php`, `packages/seo-suite/config/exchanger.php`.
- Data objects live in `src/Data/`; use them for payloads, form state, and view models.

## Extension Points

- Contracts: `ActionContract`, `AiActionContextInterface`, `ContentTargetContract`, `ExchangerInterface`, `SchemaTemplate`, `SearchConsoleClientInterface`, `SearchMetaDataSectionExtender`, `SearchMetaDataSectionExtenderResolverInterface`, `SeoPublishReportProvider`.
- Events: `AiGenerationCompleted`, `AiGenerationFailed`, `AiGenerationStarted`.
- Listeners: `ClearAiDiscoveryCacheOnPageDeleted`, `ClearAiDiscoveryCacheOnPageSaved`, `LogAiGeneration`, `NotifyAiFailure`, `RecordBrokenLink`, `SeedAiCrawlerRulesOnSiteCreated`.
- Register Capell extension points, routes, migrations, settings, render hooks, and resources from service providers.

## Install Impact

- Adds SEO and AI-related tables/settings.
- Extends page and site admin form-builder.
- Adds SEO admin pages and widgets.
- Adds llms.txt, llms-full.txt, robots.txt, and page Markdown frontend output.
- Requires Site Discovery for public page discovery and sitemap outputs.
- Adds config for AI provider/model, image model, Search Console, publish gates, and prompts.

## Install And Setup

- Install with `composer require capell-app/seo-suite` in the host Capell application.
- Run migrations through the host application package install flow.
- In this repository, verify package changes with `vendor/bin/pest`; do not use `php artisan`.

## Admin And Access

- BrokenLinksPage (packages/seo-suite/src/Filament/Pages/BrokenLinksPage.php, slug `broken-links`)
- NotFoundUrlsPage (packages/seo-suite/src/Filament/Pages/NotFoundUrlsPage.php, slug `missing-pages`)
- SeoAuditPage (packages/seo-suite/src/Filament/Pages/SeoAuditPage.php, slug `seo-audit`)
- AiDiscoveryPage (packages/seo-suite/src/Filament/Pages/AiDiscoveryPage.php, slug `ai-discovery`)
- TranslationCoveragePage (packages/seo-suite/src/Filament/Pages/TranslationCoveragePage.php, slug `translation-coverage`)

- Policy: AiCreatorPolicy (packages/seo-suite/src/Policies/AiCreatorPolicy.php)
- Gate: AiMetricsWidgetAbstract: `developer`, `admin`, `super_admin`
- Gate: BrokenLinksPage: Filament Shield page permissions
- Gate: NotFoundUrlsPage: Filament Shield page permissions
- Gate: SeoAuditPage: Filament Shield page permissions
- Gate: AiDiscoveryPage: Filament Shield page permissions
- Gate: TranslationCoveragePage: Filament Shield page permissions

## Common Pitfalls

- Do not enable AI creator without checking provider credentials and review workflow.
- Search Console requires credentials and property URL.
- Publish gates can block publishing when required metadata is missing.
- Site Discovery owns sitemap output and public URL discovery; SEO Suite consumes that public discovery boundary for AI Discovery.
- Review AI Discovery summaries, Markdown previews, and crawler policy before launching a site that should be visible to AI search and answer engines.

## Docs

- [ai-discovery.md](docs/ai-discovery.md)
- [credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
- [extending-seo-suite.md](docs/extending-seo-suite.md)
- [overview.md](docs/overview.md)
- [publish-gates.md](docs/publish-gates.md)
- [schema-templates.md](docs/schema-templates.md)
- [search-console.md](docs/search-console.md)
- [seo-intelligence.md](docs/seo-intelligence.md)
- [seo-meta-and-discoverability.md](docs/seo-meta-and-discoverability.md)
- [sitemaps.md](docs/sitemaps.md)

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/seo-suite/tests --configuration=phpunit.xml
```

## Maintenance Notes

- Put behaviour changes in `src/Actions/`; UI classes, commands, and controllers should call actions instead of owning domain logic.
- Use package `Data` classes at boundaries instead of passing anonymous arrays between layers.
- Use backed enums for persisted values and enum labels for Filament options.
