# Theme Corporate

Trust-led corporate theme for Capell.

## At A Glance

- Package: `capell-app/theme-corporate`
- Namespace: `Capell\ThemeStudio\Corporate\`
- Capell dependencies: `capell-app/core`, `capell-app/foundation-theme`

## What It Adds

- Trust-led corporate theme for Capell.

## Why It Matters

**For developers:** Adds a renderer package that plugs into Foundation Theme contracts and runtime settings.

**For teams:** Provides a corporate visual option for sites that need restrained, trust-focused presentation through the normal Theme admin page and install flow.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Core](https://github.com/capell-app/core)
- [Capell Foundation Theme](../foundation-theme/README.md)

**Open-source packages used here**

- No extra third-party Composer package beyond the Capell package stack is required here.

## Screens And Workflow

Screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment.

- Theme preset selection showing Corporate.
- Frontend page rendered with Corporate theme.
- Theme preview URL output.

## Technical Shape

- CorporateThemeServiceProvider registers the renderer.
- `capell.json` declares `themeKey: "corporate"` and `extends: "capell-app/foundation-theme"`.
- Uses Foundation Theme runtime data and standard section keys, while rendering its own page and section Blade views.
- Ships Blade resources for the page wrapper and standard theme sections.
- No migrations, config, routes, models, admin navigation, or package-owned settings are present.

## Code Map

| Area      | Path                                 | Purpose                                             |
| --------- | ------------------------------------ | --------------------------------------------------- |
| Resources | `packages/theme-corporate/resources` | Views, translations, assets, and package resources. |
| Tests     | `packages/theme-corporate/tests`     | Package-level Pest coverage.                        |

## Data And Persistence

- This package does not own data.
- It consumes theme runtime settings and core page content.

## Install Impact

- Adds a Corporate renderer to theme system.
- No database changes.
- No admin navigation by itself.
- No public routes by itself.

## Install And Setup

- Install with `composer require capell-app/theme-corporate` in the host Capell application.
- In this repository, verify package changes with `vendor/bin/pest`; do not use `php artisan`.

## Admin And Access

- None proven in this package directory.

## Common Pitfalls

- Install Foundation Theme before using this renderer.
- Verify Foundation Theme assets are generated.
- Do not install a Studio metapackage; this package installs independently.

## Docs

- [credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
- [overview.md](docs/overview.md)

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/theme-corporate/tests --configuration=phpunit.xml
```

## Maintenance Notes

- Theme output is public output. Keep admin-only metadata and editor hooks out of rendered markup.
