# Theme Agency

Status: **Available, no schema impact** · Kind: **theme** · Tier: **premium** · Bundle: **theme-studio** · Contexts: **frontend** · Product group: **Capell Theme Studio**

This page is the consolidated implementation overview for the Theme Agency package. It is extracted from the package README, service providers, migrations, config files, routes, resources, models, actions, and the shared Capell ERD notes where available.

## What This Plugin Adds

Theme Agency registers an expressive agency renderer for Capell Theme Studio.

- Agency theme service provider.
- Theme renderer/views for agency-style Theme Studio output.
- Dependency on Foundation Theme and Theme Studio Core.

## Developer Notes

Adds a renderer package that plugs into Theme Studio Core rather than changing Capell core rendering contracts.

- AgencyThemeServiceProvider registers the renderer.
- Requires capell-app/foundation-theme and capell-app/theme-studio-core.
- No migrations, config, routes, resources, or models are present.

## Operational Notes

Provides an agency-focused visual option for sites managed through Theme Studio.

- Adds an Agency renderer to Theme Studio.
- No database changes.
- No admin navigation by itself.
- No public routes by itself.

## Data And Retention

- This package does not own data.
- It reads Theme Studio runtime data and core page content through Theme Studio Core.

## Screenshot Plan

- Theme Studio preset selection showing Agency.
- Frontend page rendered with Agency theme.
- Theme preview URL output.

## Pitfalls

- Install Theme Studio Core before using this renderer.
- Verify frontend assets from Foundation Theme are available.

## Verification

- Run `vendor/bin/pest packages/theme-agency/tests` when package tests exist.
- Run the relevant host-app migration or package install flow in a disposable database.
- Open the listed admin or frontend surface and compare it with the screenshot plan.

## Package Manifest

- Composer name: `capell-app/theme-agency`
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

Deployment should read [screenshots.json](screenshots.json), install the package with demo data, resolve each admin surface or frontend URL, and write images to `public/docs/screenshots/packages/theme-agency`.

- Theme Studio preset selection showing Agency.
- Frontend page rendered with Agency theme.
- Theme preview URL output.
