# Media Library

Status: **Available, no schema impact in this package** · Kind: **package** · Tier: **free** · Bundle: **foundation** · Contexts: **admin** · Product group: **Capell Foundation**

This page is the consolidated implementation overview for the Media Library package. It is extracted from the package README, service providers, migrations, config files, routes, resources, models, actions, and the shared Capell ERD notes where available.

## What This Plugin Adds

Media Library connects Capell to Awcodes Curator media, media health reporting, and Spatie Media migration support.

- Curator media model wrapper.
- Media health admin page and table.
- Curator media field factory.
- Migration command and action for moving Spatie media into Curator.
- InteractsWithCuratorMedia concern.

## Developer Notes

Centralises Curator integration behind actions, field factories, and model concerns so packages can use media fields consistently.

- MediaLibraryServiceProvider registers the package.
- Model: CuratorMedia.
- Command: MigrateSpatieToCuratorCommand.
- Action: MigrateSpatieMediaToCuratorAction.
- Page: MediaHealthPage.
- No migrations are present in this package.

## Operational Notes

Helps site operators audit media records and move legacy media into the current Capell media foundation.

- Adds Curator media field integration.
- Adds media health admin page.
- Adds migration command.
- No package-owned database changes.

## Data And Retention

- This package does not define its own migrations.
- It relies on Curator and existing media tables.
- Migration result data records counts and outcomes for Spatie-to-Curator moves.

## Screenshot Plan

- Media health page.
- Media health table.
- Curator media field inside a form.
- Migration command output or report.

## Pitfalls

- Install and migrate Curator before relying on CuratorMedia.
- Back up legacy Spatie media before migration.
- Check disk paths and conversions before bulk migration.

## Verification

- Run `vendor/bin/pest packages/media-library/tests` when package tests exist.
- Run the relevant host-app migration or package install flow in a disposable database.
- Open the listed admin or frontend surface and compare it with the screenshot plan.

## Package Manifest

- Composer name: `capell-app/media-library`
- Product group: Capell Foundation
- Kind: package
- Tier: free
- Bundle: foundation
- Contexts: `admin`
- Requires: `capell-app/core`, `capell-app/admin`
- Optional dependencies: None listed.

## Admin Surfaces

- MediaHealthPage (packages/media-library/src/Filament/Pages/MediaHealthPage.php, slug `media-health`)

## Commands

- None proven in this package directory.

## Routes And Config

- None proven in this package directory.

## Permissions And Gates

- Gate: MediaHealthPage: Filament Shield page permissions

## Migrations

- None proven in this package directory.

## ERD Excerpt

This package has no committed ERD excerpt. Use implementation notes and extension points instead of inventing schema.

## Screenshot Automation

Deployment should read [screenshots.json](screenshots.json), install the package with demo data, resolve each admin surface or frontend URL, and write images to `public/docs/screenshots/packages/media-library`.

- Media health page.
- Media health table.
- Curator media field inside a form.
- Migration command output or report.
