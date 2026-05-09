# Theme Corporate

Status: **Available, no schema impact** · Kind: **theme** · Tier: **premium** · Bundle: **themes** · Contexts: **frontend** · Product group: **Capell Themes**

This page is the consolidated implementation overview for the Theme Corporate package. It is extracted from the package README, service providers, migrations, config files, routes, resources, models, actions, and the shared Capell ERD notes where available.

## What This Package Adds

Theme Corporate is a standalone Capell theme package. It registers the `corporate` theme key, extends Foundation Theme, and adds restrained renderer views for B2B, public sector, and professional-service sites.

- Corporate theme service provider.
- Theme renderer/views for corporate theme output.
- Dependency on Foundation Theme.

## Developer Notes

Adds a renderer package that plugs into Foundation Theme contracts and runtime settings.

- CorporateThemeServiceProvider registers the renderer.
- `capell.json` declares `themeKey: "corporate"` and `extends: "capell-app/foundation-theme"`.
- Uses Foundation Theme runtime data and standard section keys, while rendering its own page and section Blade views.
- Ships Blade resources for the page wrapper and standard theme sections.
- No migrations, config, routes, models, admin navigation, or package-owned settings are present.

## Operational Notes

Provides a corporate visual option for sites that need restrained, trust-focused presentation through the normal Theme admin page and install flow.

- Adds a Corporate renderer to theme system.
- No database changes.
- No admin navigation by itself.
- No public routes by itself.

## Data And Retention

- This package does not own data.
- It consumes theme runtime settings and core page content.

## Screenshot Plan

- Theme preset selection showing Corporate.
- Frontend page rendered with Corporate theme.
- Theme preview URL output.

## Pitfalls

- Install Foundation Theme before using this renderer.
- Verify Foundation Theme assets are generated.
- Do not install a Studio metapackage; this package installs independently.

## Verification

- Run `vendor/bin/pest packages/theme-corporate/tests` when package tests exist.
- Run the relevant host-app migration or package install flow in a disposable database.
- Open the listed admin or frontend surface and compare it with the screenshot plan.

## Package Manifest

- Composer name: `capell-app/theme-corporate`
- Theme key: `corporate`
- Product group: Capell Themes
- Kind: theme
- Tier: premium
- Bundle: themes
- Contexts: `frontend`
- Requires: `capell-app/foundation-theme`
- Optional dependencies: None listed.

## Admin Surfaces

- None proven in this package directory.

## Commands

- None proven in this package directory.

## Routes And Config

- None proven in this package directory.

## Permissions And Gates

- None proven in this package directory.

## Migrations

- None proven in this package directory.

## ERD Excerpt

This package has no committed ERD excerpt. Use implementation notes and extension points instead of inventing schema.

## Screenshot Automation

Deployment should read [screenshots.json](screenshots.json), install the package with demo data, resolve each admin surface or frontend URL, and write images to `public/docs/screenshots/packages/theme-corporate`.

- Theme preset selection showing Corporate.
- Frontend page rendered with Corporate theme.
- Theme preview URL output.
