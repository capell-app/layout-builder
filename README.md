# Capell Packages

First-party optional packages for Capell CMS. This repository is the package workspace beside the host application in `../capell-4`; install only the packages a project needs.

Each package README follows the same shape:

- At a glance: Composer name, namespace, runtime surfaces, service providers, and package dependencies.
- What it adds: the editor, frontend, console, queue, or integration behaviour the package owns.
- Code map: the package directories future changes should inspect first.
- Surfaces: Filament, Livewire, HTTP, commands, persistence, extension points, docs, and tests.

## Package Index

### Foundation And Content

| Package                                                 | Composer package              | Purpose                                                                                            |
| ------------------------------------------------------- | ----------------------------- | -------------------------------------------------------------------------------------------------- |
| [address](packages/address/README.md)                   | `capell-app/address`          | Country, region, and address data for Capell forms and admin records.                              |
| [blog](packages/blog/README.md)                         | `capell-app/blog`             | Article publishing, archive pages, tag pages, article widgets, and sitemap contributions.          |
| [content-sections](packages/content-sections/README.md) | `capell-app/content-sections` | Reusable content section records and Livewire rendering.                                           |
| [events](packages/events/README.md)                     | `capell-app/events`           | Event records, venues, occurrences, registrations, calendar pages, and iCalendar feeds.            |
| [hero](packages/hero/README.md)                         | `capell-app/hero`             | Default home-page hero widget rendering and setup.                                                 |
| [media-library](packages/media-library/README.md)       | `capell-app/media-library`    | Awcodes Curator backend integration for Capell media.                                              |
| [navigation](packages/navigation/README.md)             | `capell-app/navigation`       | Editor-managed menus for Capell frontend themes.                                                   |
| [notes](packages/notes/README.md)                       | `capell-app/notes`            | Contextual notes, assignments, mentions, and reminders.                                            |
| [tags](packages/tags/README.md)                         | `capell-app/tags`             | Shared editor-controlled taxonomies.                                                               |
| [foundation-theme](packages/foundation-theme/README.md) | `capell-app/foundation-theme` | Default frontend theme, asset pipeline, Blade directives, URL generation, and SVG media rendering. |

### Authoring And Publishing

| Package                                                       | Composer package                 | Purpose                                                                                    |
| ------------------------------------------------------------- | -------------------------------- | ------------------------------------------------------------------------------------------ |
| [frontend-authoring](packages/frontend-authoring/README.md)   | `capell-app/frontend-authoring`  | Authenticated admin in-page editing bridge for public frontend pages.                      |
| [frontend-optimizer](packages/frontend-optimizer/README.md)   | `capell-app/frontend-optimizer`  | Profile-based CSS and JavaScript delivery for public pages.                                |
| [html-cache](packages/html-cache/README.md)                   | `capell-app/html-cache`          | Static HTML cache, dependency indexing, and cache administration.                          |
| [publishing-studio](packages/publishing-studio/README.md)     | `capell-app/publishing-studio`   | Revisions, release workspaces, scheduling, approvals, previews, and controlled publishing. |
| [translation-manager](packages/translation-manager/README.md) | `capell-app/translation-manager` | File-based Laravel translation management for Capell and Filament panels.                  |
| [welcome-tour](packages/welcome-tour/README.md)               | `capell-app/welcome-tour`        | Optional Filament welcome tour for Capell Admin.                                           |

### Forms, Access, And Public Workflows

| Package                                               | Composer package             | Purpose                                                                                                    |
| ----------------------------------------------------- | ---------------------------- | ---------------------------------------------------------------------------------------------------------- |
| [access-gate](packages/access-gate/README.md)         | `capell-app/access-gate`     | Public access gates, entitlement checks, and gated delivery foundations.                                   |
| [form-builder](packages/form-builder/README.md)       | `capell-app/form-builder`    | Editor-managed forms, fields, submissions, validation, and notifications.                                  |
| [newsletter](packages/newsletter/README.md)           | `capell-app/newsletter`      | Audience management, subscriptions, consent state, imports, notifications, and public subscription routes. |
| [password-policy](packages/password-policy/README.md) | `capell-app/password-policy` | Password expiry, forced password changes, and password safety policy.                                      |
| [public-actions](packages/public-actions/README.md)   | `capell-app/public-actions`  | Reusable public submit actions, outbound automation dispatch, and integration endpoints.                   |

### Growth, Search, And Reporting

| Package                                                   | Composer package               | Purpose                                                                                       |
| --------------------------------------------------------- | ------------------------------ | --------------------------------------------------------------------------------------------- |
| [campaign-studio](packages/campaign-studio/README.md)     | `capell-app/campaign-studio`   | Campaign landing pages, CTA blocks, UTM attribution, conversion goals, and campaign insights. |
| [dashboard-reports](packages/dashboard-reports/README.md) | `capell-app/dashboard-reports` | Generic reporting widgets for Capell dashboards.                                              |
| [email-studio](packages/email-studio/README.md)           | `capell-app/email-studio`      | Transactional templates, delivery audit, provider events, replies, and suppressions.          |
| [ga4-reports](packages/ga4-reports/README.md)             | `capell-app/ga4-reports`       | GA4 dashboard reporting for Capell.                                                           |
| [insights](packages/insights/README.md)                   | `capell-app/insights`          | First-party insights, visitor journeys, click tracking, and consent management.               |
| [search](packages/search/README.md)                       | `capell-app/search`            | Public site search, optional logging, and admin search insights.                              |
| [seo-suite](packages/seo-suite/README.md)                 | `capell-app/seo-suite`         | Metadata panels, structured data, social meta, SEO audits, sitemaps, and AI-assisted SEO.     |
| [site-discovery](packages/site-discovery/README.md)       | `capell-app/site-discovery`    | Public discoverability and sitemap outputs.                                                   |

### Operations, Agents, And Migration

| Package                                                       | Composer package                 | Purpose                                                                        |
| ------------------------------------------------------------- | -------------------------------- | ------------------------------------------------------------------------------ |
| [agent-bridge](packages/agent-bridge/README.md)               | `capell-app/agent-bridge`        | Agent Bridge servers and capability adapters.                                  |
| [ai-orchestrator](packages/ai-orchestrator/README.md)         | `capell-app/ai-orchestrator`     | AI providers, prompts, structured requests, and package integration workflows. |
| [demo-kit](packages/demo-kit/README.md)                       | `capell-app/demo-kit`            | Demo content and media setup for Capell packages.                              |
| [deployments](packages/deployments/README.md)                 | `capell-app/deployments`         | Repository deployment connections and Composer publishing.                     |
| [diagnostics](packages/diagnostics/README.md)                 | `capell-app/diagnostics`         | Developer and operational diagnostics.                                         |
| [login-audit](packages/login-audit/README.md)                 | `capell-app/login-audit`         | Authentication log and login visibility.                                       |
| [media-ai](packages/media-ai/README.md)                       | `capell-app/media-ai`            | Optional AI-assisted media actions.                                            |
| [migration-assistant](packages/migration-assistant/README.md) | `capell-app/migration-assistant` | Export, import, rollback report, and migration workflow support.               |
| [wordpress-importer](packages/wordpress-importer/README.md)   | `capell-app/wordpress-importer`  | WordPress WXR import source for Migration Assistant.                           |

### Themes

| Package                                               | Composer package             | Purpose                               |
| ----------------------------------------------------- | ---------------------------- | ------------------------------------- |
| [theme-agency](packages/theme-agency/README.md)       | `capell-app/theme-agency`    | Expressive agency theme for Capell.   |
| [theme-corporate](packages/theme-corporate/README.md) | `capell-app/theme-corporate` | Trust-led corporate theme for Capell. |
| [theme-saas](packages/theme-saas/README.md)           | `capell-app/theme-saas`      | Conversion-led SaaS theme for Capell. |

## Install Pattern

Install packages from the host Capell application:

```bash
composer require capell-app/<package>
```

Then run the package install command listed in that package README when it owns migrations, settings, generated pages, demo data, or external setup.

## Working In This Repository

Use package-level checks while editing:

```bash
vendor/bin/pest packages/<package>/tests --configuration=phpunit.xml
```

Use broader checks before integration:

```bash
composer test
composer preflight
```

Do not run `php artisan` in this repository. Testbench provides the Laravel context for package tests.

## Documentation

- Per-package READMEs live at `packages/<package>/README.md`.
- Deeper package docs live under `packages/<package>/docs/` when the package needs API, database, workflow, or design notes.
- Screenshot generation is manifest-driven where `packages/<package>/docs/screenshots.json` exists.
- External docs: [docs.capell.app](https://docs.capell.app).

## License

Proprietary unless an individual package states otherwise.
