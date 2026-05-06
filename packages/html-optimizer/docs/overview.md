# HTML Optimizer

Status: **Available, no schema impact** · Kind: **package** · Tier: **free** · Bundle: **foundation** · Contexts: **frontend** · Product group: **Capell Foundation**

This page is the consolidated implementation overview for the HTML Optimizer package. It is extracted from the package README, service providers, migrations, config files, routes, resources, models, actions, and the shared Capell ERD notes where available.

## What This Plugin Adds

HTML Optimizer adds middleware and support code for reducing frontend HTML output and page-cache writes.

- HtmlOptimizerMiddleware.
- HtmlMinifier support class.
- Service provider for registration.

## Developer Notes

Provides a small rendering concern that can be attached to frontend responses without changing page or layout models.

- HtmlOptimizerServiceProvider registers the package.
- Http middleware: HtmlOptimizerMiddleware.
- Support class: HtmlMinifier.
- No migrations, config file, routes, resources, or models are present.

## Operational Notes

Reduces HTML payload size where the site wants smaller cached responses and cleaner output.

- Adds middleware capability.
- No database changes.
- No admin navigation.
- No public routes.

## Data And Retention

- This package does not own data.
- It transform-builder response content at render time.

## Screenshot Plan

- Frontend page before/after HTML output inspection.
- Middleware configuration or service provider registration proof.

## Pitfalls

- Do not minify responses that contain whitespace-sensitive content without testing.
- Confirm middleware order with page cache middleware.
- Inspect HTML comments or inline scripts if output changes unexpectedly.

## Verification

- Run `vendor/bin/pest packages/html-optimizer/tests` when package tests exist.
- Run the relevant host-app migration or package install flow in a disposable database.
- Open the listed admin or frontend surface and compare it with the screenshot plan.

## Package Manifest

- Composer name: `capell-app/html-optimizer`
- Product group: Capell Foundation
- Kind: package
- Tier: free
- Bundle: foundation
- Contexts: `frontend`
- Requires: `capell-app/frontend`
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

Deployment should read [screenshots.json](screenshots.json), install the package with demo data, resolve each admin surface or frontend URL, and write images to `public/docs/screenshots/packages/html-optimizer`.

- Frontend page before/after HTML output inspection.
- Middleware configuration or service provider registration proof.
