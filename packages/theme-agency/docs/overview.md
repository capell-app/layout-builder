# Theme Agency

Status: **Available, no schema impact** · Kind: **theme** · Tier: **premium** · Bundle: **themes** · Contexts: **frontend** · Product group: **Capell Themes**

This page is the consolidated implementation overview for the Theme Agency package. It is extracted from the package README, service providers, migrations, config files, routes, resources, models, actions, and the shared Capell ERD notes where available.

## What This Package Adds

Theme Agency is a standalone Capell theme package. It registers the `agency` theme key, extends Foundation Theme, and adds expressive renderer views for studio, portfolio, and brand-led sites.

- Agency theme service provider.
- Theme renderer/views for agency-style theme output.
- Dependency on Foundation Theme.

## Developer Notes

Adds a renderer package that plugs into Foundation Theme rather than changing Capell core rendering contracts.

- AgencyThemeServiceProvider registers the renderer.
- `capell.json` declares `themeKey: "agency"` and `extends: "capell-app/foundation-theme"`.
- Uses Foundation Theme runtime data and standard section keys, while rendering its own page and section Blade views.
- Ships Blade resources for the page wrapper and standard theme sections.
- No migrations, config, routes, models, admin navigation, or package-owned settings are present.

## Operational Notes

Provides an agency-focused visual option for sites managed through the normal Theme admin page and install flow.

- Adds an Agency renderer to theme system.
- No database changes.
- No admin navigation by itself.
- No public routes by itself.

## Data And Retention

- This package does not own data.
- It reads theme runtime data and core page content through Foundation Theme.

## Screenshot Plan

- Theme preset selection showing Agency.
- Frontend page rendered with Agency theme.
- Theme preview URL output.

## Pitfalls

- Install Foundation Theme before using this renderer.
- Verify frontend assets from Foundation Theme are available.
- Do not install a Studio metapackage; this package installs independently.

## Verification

- Run `vendor/bin/pest packages/theme-agency/tests` when package tests exist.
- Run the relevant host-app migration or package install flow in a disposable database.
- Open the listed admin or frontend surface and compare it with the screenshot plan.

## Package Manifest

- Composer name: `capell-app/theme-agency`
- Theme key: `agency`
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

Deployment should read [screenshots.json](screenshots.json), install the package with demo data, resolve each admin surface or frontend URL, and write images to `public/docs/screenshots/packages/theme-agency`.

- Theme preset selection showing Agency.
- Frontend page rendered with Agency theme.
- Theme preview URL output.
