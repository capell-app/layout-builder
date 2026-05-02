# Capell Packages

First-party add-ons for [Capell CMS](https://github.com/capell-app/capell). Install only the packages your project needs: foundation CMS features, premium forms, editorial workflows, operations tooling, growth analytics, search/SEO, and Theme Studio all live here.

## Product groups

| Group                 | Tier    | Packages                                                                                                        |
| --------------------- | ------- | --------------------------------------------------------------------------------------------------------------- |
| Capell Foundation     | Free    | Mosaic, Blog, Navigation, Tags, Redirects, Address, Media Curator, Frontend Toolbar, HTML Minify, Default Theme |
| Capell Forms          | Premium | Forms                                                                                                           |
| Capell Publishing Pro | Premium | Workspaces, Filament Peek                                                                                       |
| Capell Operations     | Premium | Backup, Developer Tools, Authentication Log                                                                     |
| Capell Growth         | Premium | Analytics, Campaigns                                                                                            |
| Capell Search & SEO   | Premium | SEO Tools, Site Search                                                                                          |
| Capell Theme Studio   | Premium | Theme Studio, Theme Studio Core, Theme Studio Admin, SaaS Theme, Corporate Theme, Agency Theme                  |

## Pick the package by job

| Need                                                   | Product group         | Composer package                                                                                                                                                             |
| ------------------------------------------------------ | --------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Visual page builder                                    | Capell Foundation     | `capell-app/mosaic`                                                                                                                                                          |
| Articles, tags, archives, RSS                          | Capell Foundation     | `capell-app/blog`                                                                                                                                                            |
| Header, footer, and sidebar menus                      | Capell Foundation     | `capell-app/navigation`                                                                                                                                                      |
| Shared tagging across content types                    | Capell Foundation     | `capell-app/tags`                                                                                                                                                            |
| 301/302 redirects                                      | Capell Foundation     | `capell-app/redirects`                                                                                                                                                       |
| Country and address fields                             | Capell Foundation     | `capell-app/address`                                                                                                                                                         |
| Curator instead of Spatie MediaLibrary                 | Capell Foundation     | `capell-app/media-curator`                                                                                                                                                   |
| Editor-managed forms and submissions                   | Capell Forms          | `capell-app/forms`                                                                                                                                                           |
| Drafts, previews, approvals, scheduled publishing      | Capell Publishing Pro | `capell-app/workspaces`, `capell-app/filament-peek`                                                                                                                          |
| Content package export, import, and restore            | Capell Operations     | `capell-app/backup`                                                                                                                                                          |
| System, queue, permission, and config health           | Capell Operations     | `capell-app/developer-tools`                                                                                                                                                 |
| Login and activity visibility                          | Capell Operations     | `capell-app/authentication-log`                                                                                                                                              |
| Campaign landing pages and conversion goals            | Capell Growth         | `capell-app/campaigns`                                                                                                                                                       |
| First-party analytics and visitor journeys             | Capell Growth         | `capell-app/analytics`                                                                                                                                                       |
| SEO audits, sitemaps, structured data, AI-assisted SEO | Capell Search & SEO   | `capell-app/seo-tools`                                                                                                                                                       |
| Public site keyword search and search analytics        | Capell Search & SEO   | `capell-app/site-search`                                                                                                                                                     |
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
composer require capell-app/mosaic capell-app/blog capell-app/workspaces capell-app/seo-tools
php artisan capell:mosaic-install
php artisan capell:blog-install
```

Then configure SEO Tools and Workspaces from the Capell admin.

## Package notes

| Product group         | What appears in the admin                                                                                                                     |
| --------------------- | --------------------------------------------------------------------------------------------------------------------------------------------- |
| Capell Foundation     | Contents, widgets, layouts, articles, navigation, tags, redirects, address fields, media backend integration                                  |
| Capell Forms          | Form records, submissions, validation, notifications, and lead capture workflows                                                              |
| Capell Publishing Pro | Workspace switcher, approvals, preview links, publish checks, scheduled publishing, stale drafts, version comparison                          |
| Capell Operations     | Import sessions, package validation, recovery workflows, system health, queue health, permission audit, config drift, authentication activity |
| Capell Growth         | Campaign records, CTA blocks, conversion goals, analytics widgets, attribution reports                                                        |
| Capell Search & SEO   | SEO settings, AI-assist panels, sitemap tools, audits, broken links, 404 reports, search analytics                                            |
| Capell Theme Studio   | Dedicated Studio page, curated gallery, shared content model, presets, preview/publish flow, and premium SaaS, Corporate, and Agency themes   |

## Documentation

- Core docs: [docs.capell.app](https://docs.capell.app)
- Package registry: [Capell-approved packages](https://docs.capell.app/packages/)
- Per-package API and database references live beside each package under `packages/<name>/docs/`.

## License

Proprietary unless an individual package states otherwise.
