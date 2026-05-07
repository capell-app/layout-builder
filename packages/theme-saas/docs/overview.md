# Theme SaaS

Status: **Available, no schema impact** · Kind: **theme** · Tier: **premium** · Bundle: **themes** · Contexts: **frontend** · Product group: **Capell Themes**

This page is the consolidated implementation overview for the Theme SaaS package. It is extracted from the package README, service providers, migrations, config files, routes, resources, models, actions, and the shared Capell ERD notes where available.

## What This Package Adds

Theme SaaS is a standalone Capell theme package. It registers the `saas` theme key, extends Foundation Theme, and adds product-focused renderer views for software and subscription sites.

- SaaS theme service provider.
- Theme renderer/views for SaaS theme output.
- Dependency on Foundation Theme.

## Developer Notes

Adds a renderer package that uses Foundation Theme runtime contracts while leaving content models unchanged.

- SaasThemeServiceProvider registers the renderer.
- `capell.json` declares `themeKey: "saas"` and `extends: "capell-app/foundation-theme"`.
- Uses shared `capell::...` views for layered fallback.
- No migrations, config, routes, resources, or models are present.

## Operational Notes

Provides a SaaS-oriented visual option for product sites managed through the normal Theme admin page and install flow.

- Adds a SaaS renderer to theme system.
- No database changes.
- No admin navigation by itself.
- No public routes by itself.

## Data And Retention

- This package does not own data.
- It consumes theme runtime settings and core page content.

## Screenshot Plan

- Theme preset selection showing SaaS.
- Frontend page rendered with SaaS theme.
- Theme preview URL output.

## Pitfalls

- Install Foundation Theme before using this renderer.
- Verify Foundation Theme assets are generated.
- Do not install a Studio metapackage; this package installs independently.

## Verification

- Run `vendor/bin/pest packages/theme-saas/tests` when package tests exist.
- Run the relevant host-app migration or package install flow in a disposable database.
- Open the listed admin or frontend surface and compare it with the screenshot plan.

## Package Manifest

- Composer name: `capell-app/theme-saas`
- Theme key: `saas`
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

Deployment should read [screenshots.json](screenshots.json), install the package with demo data, resolve each admin surface or frontend URL, and write images to `public/docs/screenshots/packages/theme-saas`.

- Theme preset selection showing SaaS.
- Frontend page rendered with SaaS theme.
- Theme preview URL output.
