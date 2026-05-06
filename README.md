# Capell Packages

First-party add-ons for [Capell CMS](https://github.com/capell-app/capell). Install only the packages your project needs: foundation CMS features, premium form-builder, editorial workflows, operations tooling, growth insights, search/SEO, and Theme Studio all live here.

## Product groups

| Group                 | Tier    | Packages                                                                                                                     |
| --------------------- | ------- | ---------------------------------------------------------------------------------------------------------------------------- |
| Capell Foundation     | Free    | LayoutBuilder, Blog, Navigation, Tags, Redirects, Address, Media Library, Frontend Toolbar, HTML Optimizer, Foundation Theme |
| Capell FormBuilder    | Premium | FormBuilder                                                                                                                  |
| Capell Publishing Pro | Premium | PublishingStudio, Admin Preview                                                                                              |
| Capell Operations     | Premium | Backup, Diagnostics, Login Audit                                                                                             |
| Capell Growth         | Premium | Insights, CampaignStudio                                                                                                     |
| Capell Search & SEO   | Premium | SEO Suite, Search                                                                                                            |
| Capell Theme Studio   | Premium | Theme Studio, Theme Studio Core, Theme Studio Admin, SaaS Theme, Corporate Theme, Agency Theme                               |

## Pick the package by job

| Need                                                   | Product group         | Composer package                                                                                                                                                             |
| ------------------------------------------------------ | --------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Visual page builder                                    | Capell Foundation     | `capell-app/layout-builder`                                                                                                                                                  |
| Articles, tags, archives, RSS                          | Capell Foundation     | `capell-app/blog`                                                                                                                                                            |
| Header, footer, and sidebar menus                      | Capell Foundation     | `capell-app/navigation`                                                                                                                                                      |
| Shared tagging across content types                    | Capell Foundation     | `capell-app/tags`                                                                                                                                                            |
| 301/302 redirects                                      | Capell Foundation     | `capell-app/redirects`                                                                                                                                                       |
| Country and address fields                             | Capell Foundation     | `capell-app/address`                                                                                                                                                         |
| Curator instead of Spatie MediaLibrary                 | Capell Foundation     | `capell-app/media-library`                                                                                                                                                   |
| Editor-managed form-builder and submissions            | Capell FormBuilder    | `capell-app/form-builder`                                                                                                                                                    |
| Drafts, previews, approvals, scheduled publishing      | Capell Publishing Pro | `capell-app/publishing-studio`, `capell-app/admin-preview`                                                                                                                   |
| Content package export, import, and restore            | Capell Operations     | `capell-app/backup`                                                                                                                                                          |
| System, queue, permission, and config health           | Capell Operations     | `capell-app/diagnostics`                                                                                                                                                     |
| Login and activity visibility                          | Capell Operations     | `capell-app/login-audit`                                                                                                                                                     |
| Campaign landing pages and conversion goals            | Capell Growth         | `capell-app/campaign-studio`                                                                                                                                                 |
| First-party insights and visitor journeys              | Capell Growth         | `capell-app/insights`                                                                                                                                                        |
| SEO audits, sitemaps, structured data, AI-assisted SEO | Capell Search & SEO   | `capell-app/seo-suite`                                                                                                                                                       |
| Public site keyword search and search insights         | Capell Search & SEO   | `capell-app/search`                                                                                                                                                          |
| Premium frontend themes and theme tooling              | Capell Theme Studio   | `capell-app/theme-studio`, `capell-app/theme-studio-core`, `capell-app/theme-studio-admin`, `capell-app/theme-saas`, `capell-app/theme-corporate`, `capell-app/theme-agency` |

## Common install pattern

Most packages follow this shape:

```bash
composer require capell-app/<package>
php artisan capell:<package>-install
php artisan capell:<package>-demo
```

Some packages auto-register through Laravel package discovery or have theme-specific commands. Check each package README for the exact commands.

## Recommended editorial stack

For a content-heavy site with pages, widgets, articles, approvals, and search metadata:

```bash
composer require capell-app/layout-builder capell-app/blog capell-app/publishing-studio capell-app/seo-suite
php artisan capell:layout-builder-install
php artisan capell:blog-install
```

Then configure SEO Suite and PublishingStudio from the Capell admin.

## Package notes

| Product group         | What appears in the admin                                                                                                                     |
| --------------------- | --------------------------------------------------------------------------------------------------------------------------------------------- |
| Capell Foundation     | Contents, widgets, layouts, articles, navigation, tags, redirects, address fields, media backend integration                                  |
| Capell FormBuilder    | Form records, submissions, validation, notifications, and lead capture workflows                                                              |
| Capell Publishing Pro | Workspace switcher, approvals, preview links, publish checks, scheduled publishing, stale drafts, version comparison                          |
| Capell Operations     | Import sessions, package validation, recovery workflows, system health, queue health, permission audit, config drift, authentication activity |
| Capell Growth         | Campaign records, CTA blocks, conversion goals, insights widgets, attribution dashboard-dashboard_reports                                     |
| Capell Search & SEO   | SEO settings, AI-assist panels, sitemap tools, audits, broken links, 404 dashboard-dashboard_reports, search insights                         |
| Capell Theme Studio   | Dedicated Studio page, curated gallery, shared content model, presets, preview/publish flow, and premium SaaS, Corporate, and Agency themes   |

## Documentation

- Core docs: [docs.capell.app](https://docs.capell.app)
- Package registry: [Capell-approved packages](https://docs.capell.app/packages/)
- Per-package API and database references live beside each package under `packages/<name>/docs/`.
- Screenshot generation is manifest-driven; see [Package Screenshot Automation](docs/package-screenshot-automation.md).

## License

Proprietary unless an individual package states otherwise.
