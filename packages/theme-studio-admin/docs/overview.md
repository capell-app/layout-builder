# Theme Studio Admin

Status: **Available, no schema impact in this package** · Kind: **package** · Tier: **premium** · Bundle: **theme-studio** · Contexts: **admin** · Product group: **Capell Theme Studio**

This page is the consolidated implementation overview for the Theme Studio Admin package. It is extracted from the package README, service providers, migrations, config files, routes, resources, models, actions, and the shared Capell ERD notes where available.

## What This Plugin Adds

Theme Studio Admin adds the Filament admin experience for staging, reviewing, previewing, approving, and publishing theme drafts.

- Theme Studio Filament page.
- Actions for staging, publishing, readiness checks, labels, previews, and activation.
- Settings schema for Theme Studio.
- Standalone and workspace draft publishers.
- Safe CSS colour validation.

## Developer Notes

Keeps theme publishing behind explicit actions and publisher contracts, with optional PublishingStudio integration for review flow.

- ThemeStudioAdminServiceProvider registers admin services.
- Filament page: ThemeStudioPage.
- Actions stage, publish, preview, activate, and check readiness.
- Contracts: ThemeDraftPublisher.
- Listeners activate approved drafts.
- Rules validate safe CSS colours.

## Operational Notes

Lets teams adjust theme presentation through an admin surface while keeping draft, approval, and publish status visible.

- Adds Theme Studio admin page.
- Adds theme publishing actions.
- No package-owned database tables.
- May depend on Theme Studio Core settings migration.
- No public routes are registered here.

## Data And Retention

- No migrations are present in this package.
- It works with Theme Studio settings from Theme Studio Core and optional PublishingStudio state.
- Deletion and retention for staged drafts should be verified against publishing policy.

## Screenshot Plan

- Theme Studio admin page.
- Theme draft form.
- Theme preview URL.
- Publishing readiness state.
- Approval or publish action state.

## Pitfalls

- Install Theme Studio Core before the admin package.
- Use PublishingStudio integration only where PublishingStudio is installed and configured.
- Validate custom colours before publishing.

## Verification

- Run `vendor/bin/pest packages/theme-studio-admin/tests` when package tests exist.
- Run the relevant host-app migration or package install flow in a disposable database.
- Open the listed admin or frontend surface and compare it with the screenshot plan.

## Package Manifest

- Composer name: `capell-app/theme-studio-admin`
- Product group: Capell Theme Studio
- Kind: package
- Tier: premium
- Bundle: theme-studio
- Contexts: `admin`
- Requires: `capell-app/admin`, `capell-app/core`, `capell-app/theme-studio-core`
- Optional dependencies: None listed.

## Admin Surfaces

- ThemeStudioPage (packages/theme-studio-admin/src/Filament/Pages/ThemeStudioPage.php, slug `theme-studio`)

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

Deployment should read [screenshots.json](screenshots.json), install the package with demo data, resolve each admin surface or frontend URL, and write images to `public/docs/screenshots/packages/theme-studio-admin`.

- Theme Studio admin page.
- Theme draft form.
- Theme preview URL.
- Publishing readiness state.
- Approval or publish action state.
