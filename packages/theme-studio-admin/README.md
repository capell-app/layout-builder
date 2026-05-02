# Capell Theme Studio Admin

**Product group:** Capell Theme Studio
**Tier:** Premium

Theme Studio Admin adds the optional Filament Studio page for choosing, previewing, staging, and publishing premium themes on top of Theme Studio Core. It is intentionally more integrated than a settings schema: editors get a curated gallery, theme status, readiness checks, and compact brand controls.

## Install

```bash
composer require capell-app/theme-studio-admin
```

Most projects install `capell-app/theme-studio`, which includes this package, Theme Studio Core, and the bundled commercial themes.

## Admin Surface

| Area          | Purpose                                                                                                              |
| ------------- | -------------------------------------------------------------------------------------------------------------------- |
| Theme gallery | Screenshot, tags, best-fit use cases, included section coverage, active/draft status, preview, and publish actions   |
| Brand profile | Global controls for colours, typography, spacing, alignment, card style, navigation style, layout, motion, and media |
| Draft/publish | Explicit staging, standalone publishing, and Workspaces approval when Workspaces is installed                        |
| Readiness     | Basic checks for registered themes, brand profile, and preview availability                                          |

## Workspaces

When `capell-app/workspaces` is installed, publishing a staged Theme Studio draft submits a Workspaces approval item instead of mutating the live theme immediately. Once that linked workspace reaches the approved transition, Theme Studio promotes the staged theme and preset to the active state and clears the draft marker. Without Workspaces, Theme Studio falls back to standalone draft/publish and clears the staged draft immediately after activation.

## Tests

```bash
php -d memory_limit=-1 vendor/bin/pest packages/theme-studio-admin/tests --no-coverage
```
