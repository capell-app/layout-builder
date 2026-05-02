# Capell Corporate Theme

**Composer package:** `capell-app/theme-corporate`
**Product group:** Capell Theme Studio
**Tier:** Premium

Corporate is the trust and clarity theme for professional services, institutions, and B2B sites. It renders the shared Theme Studio content model with restrained hierarchy, formal navigation, structured proof, and calm CTA treatment.

## Install

```bash
composer require capell-app/theme-corporate
```

For the full commercial system, install `capell-app/theme-studio`.

## Includes

- Three presets: Boardroom, Civic, Advisory.
- Renderers for navigation, hero, features, proof, content listings, CTA, and footer.
- Fallback-friendly section rendering through `capell-app/theme-studio-core`.

## Tests

```bash
php -d memory_limit=-1 vendor/bin/pest packages/theme-studio/themes/corporate/tests --no-coverage
```
