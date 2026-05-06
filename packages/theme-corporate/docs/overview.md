# Theme Corporate

Status: **Available, no schema impact** · Kind: **theme** · Tier: **premium** · Bundle: **theme-studio** · Contexts: **frontend** · Product group: **Capell Theme Studio**

This page is the consolidated implementation overview for the Theme Corporate package. It is extracted from the package README, service providers, migrations, config files, routes, resources, models, actions, and the shared Capell ERD notes where available.

## What This Plugin Adds

Theme Corporate registers a trust-led corporate renderer for Capell Theme Studio.

- Corporate theme service provider.
- Theme renderer/views for corporate Theme Studio output.
- Dependency on Foundation Theme and Theme Studio Core.

## Developer Notes

Adds a renderer package that plugs into Theme Studio Core contracts and runtime settings.

- CorporateThemeServiceProvider registers the renderer.
- Requires capell-app/foundation-theme and capell-app/theme-studio-core.
- No migrations, config, routes, resources, or models are present.

## Operational Notes

Provides a corporate visual option for sites that need restrained, trust-focused presentation.

- Adds a Corporate renderer to Theme Studio.
- No database changes.
- No admin navigation by itself.
- No public routes by itself.

## Data And Retention

- This package does not own data.
- It consumes Theme Studio runtime settings and core page content.

## Screenshot Plan

- Theme Studio preset selection showing Corporate.
- Frontend page rendered with Corporate theme.
- Theme preview URL output.

## Pitfalls

- Install Theme Studio Core before using this renderer.
- Verify Foundation Theme assets are generated.

## Verification

- Run `vendor/bin/pest packages/theme-corporate/tests` when package tests exist.
- Run the relevant host-app migration or package install flow in a disposable database.
- Open the listed admin or frontend surface and compare it with the screenshot plan.

## Package Manifest

- Composer name: `capell-app/theme-corporate`
- Product group: Capell Theme Studio
- Kind: theme
- Tier: premium
- Bundle: theme-studio
- Contexts: `frontend`
- Requires: `capell-app/foundation-theme`, `capell-app/theme-studio-core`
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

- Theme Studio preset selection showing Corporate.
- Frontend page rendered with Corporate theme.
- Theme preview URL output.
