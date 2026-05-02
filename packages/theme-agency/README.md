# Capell Agency Theme

**Composer package:** `capell-app/theme-agency`
**Product group:** Capell Theme Studio
**Tier:** Premium

Agency is the expressive theme for studios, creative teams, and case-study-led sites. It renders the shared Theme Studio content model with immersive media, bold typography, playful proof, and high-energy CTA treatment.

## Install

```bash
composer require capell-app/theme-agency
```

For the full commercial system, install `capell-app/theme-studio`.

## Includes

- Three presets: Signal, Gallery, Atelier.
- Renderers for navigation, hero, features, proof, content listings, CTA, and footer.
- Fallback-friendly section rendering through `capell-app/theme-studio-core`.

## Tests

```bash
php -d memory_limit=-1 vendor/bin/pest packages/theme-agency/tests --no-coverage
```
