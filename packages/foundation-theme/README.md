# Foundation Theme

Status: **Available, no schema impact except settings** · Kind: **theme** · Tier: **free** · Bundle: **foundation** · Contexts: **frontend, admin** · Product group: **Capell Foundation**

## What This Plugin Adds

Foundation Theme ships Capell frontend theme infrastructure, Tailwind asset generation, Blade directives, media URL handling, and theme settings.

- Default theme service provider.
- Tailwind asset generation command.
- Theme settings schema and settings migration.
- SVG media component and Capell URL generator.
- Blade directives for frontend rendering.
- Frontend beacon client that can call shared beacon routes after page load.

## Why It Matters

**For developers:** Provides the baseline Laravel view and asset pipeline that other themes and frontend packages can target.

**For teams:** Gives each Capell installation a standard frontend foundation before a custom or Theme Studio renderer is added.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Frontend](https://github.com/capell-app/frontend)

**Open-source packages used here**

- [Spatie Laravel Package Tools](https://github.com/spatie/laravel-package-tools) - Laravel package bootstrapping for config, migrations, commands, translations, and service provider setup.

**Linked package previews**

[![Spatie Laravel Package Tools GitHub preview](https://opengraph.githubassets.com/capell-readme/spatie/laravel-package-tools)](https://github.com/spatie/laravel-package-tools)

## Screens And Workflow

Screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment.

- Default theme settings screen.
- Frontend page using the default theme.
- Generated Tailwind asset output review.

## Technical Shape

- FoundationThemeServiceProvider and AdminServiceProvider register theme services and settings.
- Config file: capell-foundation-theme.php.
- Settings migration creates default theme settings.
- GenerateTailwindAssetsCommand writes frontend CSS assets.
- BladeDirectives and CapellUrlGenerator support rendering.
- The beacon client is generic. It must not ship authoring controls or authoring metadata in theme HTML; `capell-app/frontend-authoring` owns the admin-only response that decorates the page.

## Data Model

- This package does not create content tables.
- It owns settings through create_foundation_theme_settings.php.
- Theme output depends on core site, page, layout, and media data.

## Install Impact

- Adds default theme settings.
- Adds Tailwind asset generation command.
- Adds config keys for asset build tool, npm dependencies, Tailwind sources, and media URL behaviour.
- No public routes are registered by this package.
- Does not add in-page authoring markup to public Blade or cached HTML.

## Commands

- `capell:frontend-tailwind-assets {--report : Print the aggregated assets report instead of writing files} {--output-path= : Base absolute path for generated CSS files; theme key is appended per enabled Theme (e.g. frontend-default.css)} {--theme-key= : Only regenerate the CSS file for the Theme with this key}` (packages/foundation-theme/src/Console/Commands/GenerateTailwindAssetsCommand.php)

## Admin And Access

- None proven in this package directory.

- None proven in this package directory.

## Common Pitfalls

- Regenerate assets after changing theme colours or source paths.
- Match asset_build_tool to the host app.
- Set media URL config before production media rendering.
- Keep authoring behaviour in `capell-app/frontend-authoring`; themes should expose stable presentation selectors, not hidden editor metadata.

## Quick Start

1. Install the package with `composer require capell-app/foundation-theme`.
2. Run the package migrations or the Capell package installer required by the host app.
3. Open the new admin or frontend surface and verify the result.

## Next Steps

- [docs/overview.md](docs/overview.md)
- [../theme-studio-core/README.md](../theme-studio-core/README.md)
- [../theme-agency/README.md](../theme-agency/README.md)
- [docs/credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
