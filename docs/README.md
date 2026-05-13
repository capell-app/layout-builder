# Capell Packages — cross-cutting docs

This directory holds **cross-cutting** documentation for the add-on packages. Per-package docs (API, Database, README) live alongside each package in `packages/<name>/`.

## Per-package references

For the commercial/free grouping, see [Package product groups](product-groups.md).
For package-level upstream credits, services, and acknowledgements, see [Credits and acknowledgements](credits-and-acknowledgements.md).
For theme package authoring, see [Creating a Capell theme](creating-a-theme.md).
For optional package integration rules, see [Optional Package Boundaries](optional-package-boundaries.md).

| Package            | Local reference                                                                                                                                                  |
| ------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Address            | [`packages/address/README.md`](../packages/address/README.md)                                                                                                    |
| Insights           | [`packages/insights/README.md`](../packages/insights/README.md)                                                                                                  |
| AIOrchestrator     | [`packages/ai-orchestrator/README.md`](../packages/ai-orchestrator/README.md)                                                                                    |
| Login Audit        | [`packages/login-audit/README.md`](../packages/login-audit/README.md)                                                                                            |
| MigrationAssistant | [`packages/migration-assistant/README.md`](../packages/migration-assistant/README.md)                                                                            |
| Blog               | [`packages/blog/README.md`](../packages/blog/README.md)                                                                                                          |
| CampaignStudio     | [`packages/campaign-studio/README.md`](../packages/campaign-studio/README.md)                                                                                    |
| Foundation Theme   | [`packages/foundation-theme/README.md`](../packages/foundation-theme/README.md)                                                                                  |
| Deployments        | [`packages/deployments/README.md`](../packages/deployments/README.md)                                                                                            |
| Diagnostics        | [`packages/diagnostics/README.md`](../packages/diagnostics/README.md)                                                                                            |
| FormBuilder        | [`packages/form-builder/README.md`](../packages/form-builder/README.md)                                                                                          |
| Agent Bridge       | [`packages/agent-bridge/README.md`](../packages/agent-bridge/README.md)                                                                                          |
| Media Library      | [`packages/media-library/README.md`](../packages/media-library/README.md)                                                                                        |
| Navigation         | [`packages/navigation/README.md`](../packages/navigation/README.md)                                                                                              |
| Site Discovery     | [`packages/site-discovery/README.md`](../packages/site-discovery/README.md)                                                                                      |
| SEO Suite          | [`packages/seo-suite/README.md`](../packages/seo-suite/README.md)                                                                                                |
| Search             | [`packages/search/README.md`](../packages/search/README.md)                                                                                                      |
| Tags               | [`packages/tags/README.md`](../packages/tags/README.md)                                                                                                          |
| Theme Agency       | [`packages/theme-agency/README.md`](../packages/theme-agency/README.md)                                                                                          |
| Theme Corporate    | [`packages/theme-corporate/README.md`](../packages/theme-corporate/README.md)                                                                                    |
| Theme SaaS         | [`packages/theme-saas/README.md`](../packages/theme-saas/README.md)                                                                                              |
| Frontend Authoring | [`packages/frontend-authoring/README.md`](../packages/frontend-authoring/README.md), [`in-page editing`](../packages/frontend-authoring/docs/in-page-editing.md) |
| WordPress Importer | [`packages/wordpress-importer/README.md`](../packages/wordpress-importer/README.md)                                                                              |
| PublishingStudio   | [`packages/publishing-studio/README.md`](../packages/publishing-studio/README.md)                                                                                |

For the full documentation site, see [docs.capell.app](https://docs.capell.app). For the package overview and dependency matrix, see the [repository README](../README.md).

## Screenshot Automation

Package screenshots are generated from committed manifests during deployment.
See [Package Screenshot Automation](package-screenshot-automation.md) for the contract
and expected output path.
GitHub Actions provides a `Screenshot Manifests` workflow that validates the committed
manifests stay in sync.
