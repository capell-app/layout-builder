# Admin Preview

Status: **Available, no schema impact** · Kind: **package** · Tier: **premium** · Bundle: **publishing-pro** · Contexts: **admin, frontend** · Product group: **Capell Publishing Pro**

This page is the consolidated implementation overview for the Admin Preview package. It is extracted from the package README, service providers, migrations, config files, routes, resources, models, actions, and the shared Capell ERD notes where available.

## What This Plugin Adds

Admin Preview adds optional iframe preview actions for Capell admin and PublishingStudio draft review.

- Admin panel extender for Admin Preview.
- Workspace preview action contributor.
- Peek preview action for publishing-studio.

## Developer Notes

Integrates preview actions through admin extenders and PublishingStudio contributors instead of changing core resources directly.

- AdminPreviewServiceProvider and AdminServiceProvider register the package.
- AdminPreviewAdminPanelExtender connects into the admin panel.
- WorkspacePeekPreviewActionContributor contributes preview actions when PublishingStudio is present.
- No migrations, config, or routes are present in this package.

## Operational Notes

Lets editors preview draft content from the admin workflow before publishing or approving it.

- Adds preview action integration to the admin surface.
- No database changes.
- No public routes in this package.
- Requires the host app to include the relevant Admin Preview dependency/configuration.

## Data And Retention

- This package does not own data.
- It depends on existing page, workspace, and preview URL state supplied by Capell and PublishingStudio.

## Screenshot Plan

- Page or workspace edit screen with preview action.
- Peek iframe preview panel.
- Workspace draft review screen with preview action.

## Pitfalls

- Install PublishingStudio before expecting workspace-specific preview actions.
- Iframe preview must be allowed by the rendered frontend response.

## Verification

- Run `vendor/bin/pest packages/admin-preview/tests` when package tests exist.
- Run the relevant host-app migration or package install flow in a disposable database.
- Open the listed admin or frontend surface and compare it with the screenshot plan.

## Package Manifest

- Composer name: `capell-app/admin-preview`
- Product group: Capell Publishing Pro
- Kind: package
- Tier: premium
- Bundle: publishing-pro
- Contexts: `admin`, `frontend`
- Requires: `capell-app/core`, `capell-app/admin`, `capell-app/frontend`, `capell-app/publishing-studio`
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

Deployment should read [screenshots.json](screenshots.json), install the package with demo data, resolve each admin surface or frontend URL, and write images to `public/docs/screenshots/packages/admin-preview`.

- Page or workspace edit screen with preview action.
- Peek iframe preview panel.
- Workspace draft review screen with preview action.
