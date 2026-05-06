# Theme Studio Admin

Status: **Available, no schema impact in this package** · Kind: **package** · Tier: **premium** · Bundle: **theme-studio** · Contexts: **admin** · Product group: **Capell Theme Studio**

## What This Plugin Adds

Theme Studio Admin adds the Filament admin experience for staging, reviewing, previewing, approving, and publishing theme drafts.

- Theme Studio Filament page.
- Actions for staging, publishing, readiness checks, labels, previews, and activation.
- Settings schema for Theme Studio.
- Standalone and workspace draft publishers.
- Safe CSS colour validation.

## Why It Matters

**For developers:** Keeps theme publishing behind explicit actions and publisher contracts, with optional PublishingStudio integration for review flow.

**For teams:** Lets teams adjust theme presentation through an admin surface while keeping draft, approval, and publish status visible.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Admin](https://github.com/capell-app/admin)
- [Capell Core](https://github.com/capell-app/core)
- [Capell Theme Studio Core](../theme-studio-core/README.md)

**Open-source packages used here**

- [Laravel Actions](https://github.com/lorisleiva/laravel-actions) - single-purpose action classes that keep package workflows out of controllers and Filament resources.
- [Spatie Laravel Package Tools](https://github.com/spatie/laravel-package-tools) - Laravel package bootstrapping for config, migrations, commands, translations, and service provider setup.

**Linked package previews**

[![Laravel Actions GitHub preview](https://opengraph.githubassets.com/capell-readme/lorisleiva/laravel-actions)](https://github.com/lorisleiva/laravel-actions)

[![Spatie Laravel Package Tools GitHub preview](https://opengraph.githubassets.com/capell-readme/spatie/laravel-package-tools)](https://github.com/spatie/laravel-package-tools)

## Screens And Workflow

Screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment.

- Theme Studio admin page.
- Theme draft form.
- Theme preview URL.
- Publishing readiness state.
- Approval or publish action state.

## Technical Shape

- ThemeStudioAdminServiceProvider registers admin services.
- Filament page: ThemeStudioPage.
- Actions stage, publish, preview, activate, and check readiness.
- Contracts: ThemeDraftPublisher.
- Listeners activate approved drafts.
- Rules validate safe CSS colours.

## Data Model

- No migrations are present in this package.
- It works with Theme Studio settings from Theme Studio Core and optional PublishingStudio state.
- Deletion and retention for staged drafts should be verified against publishing policy.

## Install Impact

- Adds Theme Studio admin page.
- Adds theme publishing actions.
- No package-owned database tables.
- May depend on Theme Studio Core settings migration.
- No public routes are registered here.

## Commands

- None proven in this package directory.

## Admin And Access

- ThemeStudioPage (packages/theme-studio-admin/src/Filament/Pages/ThemeStudioPage.php, slug `theme-studio`)

- None proven in this package directory.

## Common Pitfalls

- Install Theme Studio Core before the admin package.
- Use PublishingStudio integration only where PublishingStudio is installed and configured.
- Validate custom colours before publishing.

## Quick Start

1. Install the package with `composer require capell-app/theme-studio-admin`.
2. Register the package provider through Composer discovery and clear cached config if the host app uses config caching.
3. Open the new admin surface or integration point and verify the result.

## Next Steps

- [docs/overview.md](docs/overview.md)
- [../theme-studio-core/README.md](../theme-studio-core/README.md)
- [../publishing-studio/README.md](../publishing-studio/README.md)
- [docs/credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
