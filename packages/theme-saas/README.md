# Capell SaaS Theme

**Composer package:** `capell-app/theme-saas`
**Product group:** Capell Theme Studio
**Tier:** Premium

SaaS is the conversion and product-framing theme for software, subscription, and platform sites. It renders the shared Theme Studio content model with compact proof, product-led hierarchy, and optional extension room for pricing, integrations, and feature emphasis.

## Install

```bash
composer require capell-app/theme-saas
```

For the full commercial system, install `capell-app/theme-studio`.

## Includes

- Three presets: Launch, Platform, Labs.
- Renderers for navigation, hero, features, proof, content listings, CTA, and footer.
- Fallback-friendly section rendering through `capell-app/theme-studio-core`.

## Tests

```bash
php -d memory_limit=-1 vendor/bin/pest packages/theme-saas/tests --no-coverage
```
