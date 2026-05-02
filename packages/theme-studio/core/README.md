# Capell Theme Studio Core

**Product group:** Capell Theme Studio
**Tier:** Premium

Theme Studio Core is the runtime contract for commercial Capell themes. It owns portable section data, brand tokens, presets, preview context, cache key isolation, and renderer registration.

## Install

```bash
composer require capell-app/theme-studio-core
```

Most projects install `capell-app/theme-studio`, which pulls this package in with the admin and bundled themes.

## What Developers Get

| Area              | Purpose                                                                                                                         |
| ----------------- | ------------------------------------------------------------------------------------------------------------------------------- |
| Theme definitions | Gallery metadata, presets, assets, included shared sections, and best-fit use cases                                             |
| Brand profile     | Compact global controls for colours, typography, spacing, alignment, cards, navigation, layout, motion, and media               |
| Shared sections   | Portable hero, features, proof, content listing, CTA, navigation, and footer data                                               |
| Rendering         | Full-page and section renderer contracts with graceful fallbacks                                                                |
| Preview           | Signed whole-site preview context without publishing                                                                            |
| Assets            | Token rendering and isolated theme/preset cache keys                                                                            |
| Frontend adapter  | Converts the active Capell frontend page into portable theme sections, including loaded Mosaic widgets when Mosaic is installed |

## Frontend Runtime

`CapellFrontendThemePageAdapter` reads the current frontend context, keeps page content portable, and maps Mosaic layouts into the shared Theme Studio section model. Page-level Mosaic widget assets are respected through the existing layout loader, so a shared layout can render different hero, feature, proof, listing, or CTA content per page without duplicating the layout JSON.

## Tests

```bash
php -d memory_limit=-1 vendor/bin/pest packages/theme-studio/core/tests --no-coverage
```
