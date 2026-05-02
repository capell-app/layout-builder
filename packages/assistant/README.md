# Capell Assistant

**Product group:** Capell Commercial
**Tier:** Premium

Capell Assistant is the commercial AI orchestration package for Capell. It keeps provider connectors, prompt templates, approval levels, and package-specific capabilities behind one Assistant surface instead of scattering separate AI layers across Mosaic, Blog, SEO, and future packages.

## Install

```bash
composer require capell-app/assistant
```

## Package Integration Pattern

Assistant owns the AI module registry. Optional package integrations live in Assistant and only register when the target package is installed.

| Integration | Behaviour                                                                                                                 |
| ----------- | ------------------------------------------------------------------------------------------------------------------------- |
| Mosaic      | Exposes layout-plan previewing through Assistant while Mosaic remains a Foundation package with no commercial dependency. |

Mosaic still owns layout presets and creator actions. Assistant wraps those actions for AI runs, so the package boundary stays simple: Foundation packages expose normal Actions; Assistant decides which of those Actions are available to prompts.

## Tests

```bash
php -d memory_limit=-1 vendor/bin/pest packages/assistant/tests --no-coverage
```
