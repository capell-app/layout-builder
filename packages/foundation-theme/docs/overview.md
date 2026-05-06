# Foundation Theme

Status: **Available, no schema impact except settings** · Kind: **theme** · Tier: **free** · Bundle: **foundation** · Contexts: **frontend, admin** · Product group: **Capell Foundation**

This page is the consolidated implementation overview for the Foundation Theme package. It is extracted from the package README, service providers, migrations, config files, routes, resources, models, actions, and the shared Capell ERD notes where available.

## What This Plugin Adds

Foundation Theme ships Capell frontend theme infrastructure, Tailwind asset generation, Blade directives, media URL handling, and theme settings.

- Default theme service provider.
- Tailwind asset generation command.
- Theme settings schema and settings migration.
- SVG media component and Capell URL generator.
- Blade directives for frontend rendering.
- Frontend beacon client that can call shared beacon routes after page load.

## Developer Notes

Provides the baseline Laravel view and asset pipeline that other themes and frontend packages can target.

- FoundationThemeServiceProvider and AdminServiceProvider register theme services and settings.
- Config file: capell-foundation-theme.php.
- Settings migration creates default theme settings.
- GenerateTailwindAssetsCommand writes frontend CSS assets.
- BladeDirectives and CapellUrlGenerator support rendering.
- The beacon client is generic. It must not ship authoring controls or authoring metadata in theme HTML; `capell-app/frontend-authoring` owns the admin-only response that decorates the page.

## Operational Notes

Gives each Capell installation a standard frontend foundation before a custom or Theme Studio renderer is added.

- Adds default theme settings.
- Adds Tailwind asset generation command.
- Adds config keys for asset build tool, npm dependencies, Tailwind sources, and media URL behaviour.
- No public routes are registered by this package.
- Does not add in-page authoring markup to public Blade or cached HTML.

## Data And Retention

- This package does not create content tables.
- It owns settings through create_foundation_theme_settings.php.
- Theme output depends on core site, page, layout, and media data.

## Screenshot Plan

- Default theme settings screen.
- Frontend page using the default theme.
- Generated Tailwind asset output review.

## Pitfalls

- Regenerate assets after changing theme colours or source paths.
- Match asset_build_tool to the host app.
- Set media URL config before production media rendering.
- Keep authoring behaviour in `capell-app/frontend-authoring`; themes should expose stable presentation selectors, not hidden editor metadata.

## Verification

- Run `vendor/bin/pest packages/foundation-theme/tests` when package tests exist.
- Run the relevant host-app migration or package install flow in a disposable database.
- Open the listed admin or frontend surface and compare it with the screenshot plan.

## Package Manifest

- Composer name: `capell-app/foundation-theme`
- Product group: Capell Foundation
- Kind: theme
- Tier: free
- Bundle: foundation
- Contexts: `frontend`, `admin`
- Requires: Not declared.
- Optional dependencies: None listed.

## Admin Surfaces

- None proven in this package directory.

## Commands

- `capell:frontend-tailwind-assets {--report : Print the aggregated assets report instead of writing files} {--output-path= : Base absolute path for generated CSS files; theme key is appended per enabled Theme (e.g. frontend-default.css)} {--theme-key= : Only regenerate the CSS file for the Theme with this key}` (packages/foundation-theme/src/Console/Commands/GenerateTailwindAssetsCommand.php)

## Routes And Config

- Config: packages/foundation-theme/config/capell-foundation-theme.php

## Permissions And Gates

- None proven in this package directory.

## Migrations

- Settings migration: create_foundation_theme_settings.php

## ERD Excerpt

This package has no committed ERD excerpt. Use implementation notes and extension points instead of inventing schema.

## Screenshot Automation

Deployment should read [screenshots.json](screenshots.json), install the package with demo data, resolve each admin surface or frontend URL, and write images to `public/docs/screenshots/packages/foundation-theme`.

- Default theme settings screen.
- Frontend page using the default theme.
- Generated Tailwind asset output review.
