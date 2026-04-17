# Capell Packages Documentation

This directory holds cross-cutting documentation for the add-on packages in `capell-app/capell-packages`. Per-package docs (install, config, API) live alongside each package in `packages/<name>/README.md` and `packages/<name>/docs/`.

## Per-package documentation

| Package | README | Database | API |
| --- | --- | --- | --- |
| Layout | [README](../packages/layout/README.md) | [Database](../packages/layout/docs/Database.md) | [API](../packages/layout/docs/API.md) |
| Blog | [README](../packages/blog/README.md) | [Database](../packages/blog/docs/Database.md) | [API](../packages/blog/docs/API.md) |
| Hero | [README](../packages/hero/README.md) | [Database](../packages/hero/docs/Database.md) (no tables) | [API](../packages/hero/docs/API.md) |
| Address | [README](../packages/address/README.md) | [Database](../packages/address/docs/Database.md) | [API](../packages/address/docs/API.md) |
| Assistant | [README](../packages/assistant/README.md) | [Database](../packages/assistant/docs/Database.md) | [API](../packages/assistant/docs/API.md) |

## Cross-cutting documents in this directory

| Document | Purpose |
| --- | --- |
| [OpenAI integration](openai-integration.md) | Architectural overview of the Assistant package's AI pipeline |
| [Test plan — actions & services](test-plan-actions-services.md) | Inventory of Admin/Core actions and services with test scope (historical; includes pre-split Assistant entries) |

## Capell core documentation

Everything that isn't package-specific — install guide, workspace model, page/site loading, extension points, render hooks, sitemaps, caching, and so on — lives in the [main Capell docs](../../capell-4/docs/). Start with:

- [Capell docs index](../../capell-4/docs/README.md)
- [Packages & add-ons overview](../../capell-4/docs/packages.md)
- [Extending Capell](../../capell-4/docs/extending-capell.md)
- [Glossary](../../capell-4/docs/glossary.md)

## Relationship to the main repo

- **`capell-app/capell`** (`../../capell-4`) is the main platform: core, admin, frontend, and most user-facing docs.
- **`capell-app/capell-packages`** (this repo) holds optional add-ons. Each ships independently, follows the same conventions, and plugs in through the extension points documented in the main repo.
