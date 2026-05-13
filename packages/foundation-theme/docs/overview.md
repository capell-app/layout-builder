# Foundation Theme

Status: **Available, no schema impact except settings** · Kind: **theme** · Tier: **free** · Bundle: **foundation** · Contexts: **frontend, admin** · Product group: **Capell Foundation**

This page is the consolidated implementation overview for the Foundation Theme package. It is extracted from the package README, service providers, migrations, config files, routes, resources, models, actions, and the shared Capell ERD notes where available.

## What This Package Adds

Foundation Theme is the default Capell theme package. It ships the shared runtime that child themes use: theme registration, renderer contracts, preview context, token CSS generation, Tailwind assets, Blade directives, media URL handling, and theme settings.

- Default theme service provider.
- `themeKey: "default"` for new installs.
- Theme registry, renderer contracts, preview signing, and token CSS support.
- Tailwind asset generation command.
- Theme settings schema and settings migration.
- SVG media component and Capell URL generator.
- Blade directives for frontend rendering.
- Frontend beacon client that can call shared beacon routes after page load.

## Developer Notes

Provides the baseline Laravel view and asset pipeline that child themes and frontend packages can target.

- FoundationThemeServiceProvider registers theme services and settings.
- Config file: capell-foundation-theme.php.
- Settings migration creates default theme settings.
- Registers the `capell` Blade namespace and anonymous `capell::...` components.
- Registers core layout builder frontend rendering views and widget components.
- Runtime theme data layers parent defaults, child defaults, and database edits in that order.
- GenerateTailwindAssetsCommand writes one frontend Tailwind directive file; runtime theme colours are emitted as CSS variables by the theme head tokens.
- core layout builder JavaScript is registered as a conditional vendor build asset and only loads when the resolved frontend layout contains widgets.
- BladeDirectives and CapellUrlGenerator support rendering.
- The beacon client is generic. It must not ship authoring controls or authoring metadata in theme HTML; `capell-app/frontend-authoring` owns the admin-only response that decorates the page.

## Operational Notes

Gives each Capell installation a standard frontend foundation before a custom or theme renderer is added.

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

- Regenerate assets after changing source paths, Tailwind plugins, or package CSS imports. Theme colour edits are runtime CSS variables and do not require a rebuild.
- Match asset_build_tool to the host app.
- Set media URL config before production media rendering.
- Treat Foundation Theme as the shared runtime, not the place for client-specific branding.
- Add branded page wrappers and section views in child theme packages such as `theme-agency`, `theme-corporate`, or `theme-saas`.
- Keep authoring behaviour in `capell-app/frontend-authoring`; themes should expose stable presentation selectors, not hidden editor metadata.
- Keep child themes on shared `capell::...` views unless they need their own section markup.

## Verification

- Run `vendor/bin/pest packages/foundation-theme/tests` when package tests exist.
- Run the relevant host-app migration or package install flow in a disposable database.
- Open the listed admin or frontend surface and compare it with the screenshot plan.

## Package Manifest

- Composer name: `capell-app/foundation-theme`
- Theme key: `default`
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

- `capell:frontend-tailwind-assets {--report : Print the aggregated assets report instead of writing files} {--output-path= : Absolute path or directory for the generated frontend CSS entrypoint}` (packages/foundation-theme/src/Console/Commands/GenerateTailwindAssetsCommand.php)

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
