# SEO Suite

Status: **Available, schema-owning** · Kind: **package** · Tier: **premium** · Bundle: **search-seo** · Contexts: **admin, frontend, console** · Product group: **Capell Search & SEO**

## What This Plugin Adds

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

## Data Model

- broken_links stores page, target URL, HTTP status, and last check time.
- page_seo_snapshots store page SEO report state.
- search_console_url_metrics store imported Search Console values.
- ai_creator_contexts, ai_generation_histories, and ai_creator_sessions store AI workflow state.
- ai_discovery_site_profiles, ai_discovery_page_profiles, ai_discovery_crawler_rules, and ai_discovery_snapshots store AI Discovery configuration, robots controls, and generated document state.
- SEO data connects to sites, pages, languages, users, and publishing-studio.

## Install Impact

- Adds SEO and AI-related tables/settings.
- Extends page and site admin form-builder.
- Adds SEO admin pages and widgets.
- Adds llms.txt, llms-full.txt, robots.txt, and page Markdown frontend output.
- Adds config for AI provider/model, image model, Search Console, publish gates, and prompts.

## Commands

- `capell:admin-clear-ai-cache` (packages/seo-suite/src/Console/Commands/ClearAiCacheCommand.php)
- `capell:seo-suite-install` (packages/seo-suite/src/Console/Commands/InstallCommand.php)
- `capell:admin-monitor-ai-usage` (packages/seo-suite/src/Console/Commands/MonitorAiUsageCommand.php)
- `capell:seo-suite-setup` (packages/seo-suite/src/Console/Commands/SetupCommand.php)
- `capell:admin-test-openai` (packages/seo-suite/src/Console/Commands/TestOpenAiConnectionCommand.php)

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

## Quick Start

1. Install the package with `composer require capell-app/seo-suite`.
2. Run the package migrations or the Capell package installer required by the host app.
3. Open the new admin surface or integration point and verify the result.

## Next Steps

- [docs/overview.md](docs/overview.md)
- [docs/ai-discovery.md](docs/ai-discovery.md)
- [../redirects/README.md](../redirects/README.md)
- [../blog/README.md](../blog/README.md)
- [../publishing-studio/README.md](../publishing-studio/README.md)
- [docs/credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
