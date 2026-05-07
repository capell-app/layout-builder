# Capell Packages Context Map

This repository contains optional add-on packages for Capell CMS. The package set is organised by product group and runtime context. Use this map to choose the right `CONTEXT.md` vocabulary before making architectural suggestions.

## Root Context

- [Capell Packages](CONTEXT.md) — shared vocabulary for package architecture, package installation, extension points, and cross-package collaboration.

## Product Contexts

| Context                 | Packages                                                                                                                                 | Purpose                                                                                                             |
| ----------------------- | ---------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------- |
| Foundation              | `address`, `blog`, `foundation-theme`, `html-optimizer`, `media-library`, `layout-builder`, `navigation`, `redirects`, `tags`, `toolbar` | Core publishing add-ons, frontend rendering, navigation, taxonomy, redirects, media, and reusable site structure.   |
| Publishing Pro          | `publishing-studio`, `admin-preview`                                                                                                     | Draft publishing-studio, approvals, preview links, scheduled publishing, rollback, and preview workflow.            |
| Search & SEO            | `seo-suite`, `search`                                                                                                                    | Metadata, structured data, sitemaps, search insights, internal search, and search insight workflows.                |
| Growth                  | `insights`, `campaign-studio`, `form-builder`                                                                                            | First-party insights, consent, campaign-studio, attribution, conversion goals, and form submissions.                |
| Operations              | `login-audit`, `backup`, `diagnostics`                                                                                                   | Authentication audit, export/import, restore, health checks, config drift, queue health, and operational reporting. |
| Theme Studio            | `theme-studio`, `theme-studio-core`, `theme-studio-admin`, `theme-agency`, `theme-corporate`, `theme-saas`                               | Theme runtime, draft theme publishing, preview context, renderer packages, and commercial theme bundle.             |
| Commercial Integrations | `ai-orchestrator`, `agent-bridge`, `deployments`                                                                                         | AIOrchestrator capabilities, Agent Bridge tools, and deployment publishing workflows.                               |
| Internal Packaging      | `plugins`                                                                                                                                | Package metadata and plugin packaging support.                                                                      |

## Runtime Contexts

| Runtime Context | Meaning                                                                                                   |
| --------------- | --------------------------------------------------------------------------------------------------------- |
| Admin           | Filament resources, pages, widgets, settings schemas, page schema extenders, and admin-only actions.      |
| Frontend        | Public routes, render hooks, Livewire frontend components, themes, toolbars, and frontend middleware.     |
| Console         | Install, setup, demo, faker, import, export, cleanup, and package maintenance commands.                   |
| Shared          | Providers, actions, data, models, enums, contracts, and registries used by more than one runtime context. |

## Package Dependency Direction

- Foundation packages may depend on Capell core/admin/frontend and on other explicit Foundation packages where declared in `capell.json`.
- `blog` depends on `layout-builder` and `tags`.
- `campaign-studio` depends on `layout-builder` and `form-builder`, with optional collaboration with `insights` and `seo-suite`.
- `publishing-studio` is a publishing layer used by `layout-builder` and preview-related packages.
- Theme renderer packages depend on `foundation-theme` and `theme-studio-core`.
- `theme-studio-admin` depends on `theme-studio-core` and may collaborate with `publishing-studio`.
- Packages should collaborate through declared extension points, contracts, events, render hooks, settings registries, and action calls rather than reaching into another package's internals.

## Context Maintenance

- Add package-scoped `CONTEXT.md` files only when a package develops vocabulary that is too specific for the root context.
- When a new domain term appears in more than one package, add it to the root [Capell Packages](CONTEXT.md) context.
- When a package deliberately rejects a recurring architectural suggestion, record that decision in an ADR rather than relying on memory.
