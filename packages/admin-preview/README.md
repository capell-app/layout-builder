# Admin Preview

Status: **Available, no schema impact** · Kind: **package** · Tier: **premium** · Bundle: **publishing-pro** · Contexts: **admin, frontend** · Product group: **Capell Publishing Pro**

## What This Plugin Adds

Admin Preview adds optional iframe preview actions for Capell admin and PublishingStudio draft review.

- Admin panel extender for Admin Preview.
- Workspace preview action contributor.
- Peek preview action for publishing-studio.

## Why It Matters

**For developers:** Integrates preview actions through admin extenders and PublishingStudio contributors instead of changing core resources directly.

**For teams:** Lets editors preview draft content from the admin workflow before publishing or approving it.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Admin](https://github.com/capell-app/admin)
- [Capell Frontend](https://github.com/capell-app/frontend)
- [Capell Publishing Studio](../publishing-studio/README.md)

**Open-source packages used here**

- [Filament Peek](https://github.com/pboivin/filament-peek) - the iframe preview action used to let editors inspect draft pages directly from Filament.

**Linked package previews**

[![Filament Peek GitHub preview](https://opengraph.githubassets.com/capell-readme/pboivin/filament-peek)](https://github.com/pboivin/filament-peek)

## Screens And Workflow

Screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment.

- Page or workspace edit screen with preview action.
- Peek iframe preview panel.
- Workspace draft review screen with preview action.

## Technical Shape

- AdminPreviewServiceProvider and AdminServiceProvider register the package.
- AdminPreviewAdminPanelExtender connects into the admin panel.
- WorkspacePeekPreviewActionContributor contributes preview actions when PublishingStudio is present.
- No migrations, config, or routes are present in this package.

## Data Model

- This package does not own data.
- It depends on existing page, workspace, and preview URL state supplied by Capell and PublishingStudio.

## Install Impact

- Adds preview action integration to the admin surface.
- No database changes.
- No public routes in this package.
- Requires the host app to include the relevant Admin Preview dependency/configuration.

## Commands

- None proven in this package directory.

## Admin And Access

- None proven in this package directory.

- None proven in this package directory.

## Common Pitfalls

- Install PublishingStudio before expecting workspace-specific preview actions.
- Iframe preview must be allowed by the rendered frontend response.

## Quick Start

1. Install the package with `composer require capell-app/admin-preview`.
2. Register the package provider through Composer discovery and clear cached config if the host app uses config caching.
3. Open the new admin surface or integration point and verify the result.

## Next Steps

- [docs/overview.md](docs/overview.md)
- [../publishing-studio/README.md](../publishing-studio/README.md)
- [docs/credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
