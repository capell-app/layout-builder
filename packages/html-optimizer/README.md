# HTML Optimizer

Status: **Available, no schema impact** · Kind: **package** · Tier: **free** · Bundle: **foundation** · Contexts: **frontend** · Product group: **Capell Foundation**

## What This Plugin Adds

HTML Optimizer adds middleware and support code for reducing frontend HTML output and page-cache writes.

- HtmlOptimizerMiddleware.
- HtmlMinifier support class.
- Service provider for registration.

## Why It Matters

**For developers:** Provides a small rendering concern that can be attached to frontend responses without changing page or layout models.

**For teams:** Reduces HTML payload size where the site wants smaller cached responses and cleaner output.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Frontend](https://github.com/capell-app/frontend)

**Open-source packages used here**

- [Spatie Laravel Package Tools](https://github.com/spatie/laravel-package-tools) - Laravel package bootstrapping for config, migrations, commands, translations, and service provider setup.
- [voku HtmlMin](https://github.com/voku/HtmlMin) - HTML minification used by the optimizer package before cached frontend output is served.

**Linked package previews**

[![Spatie Laravel Package Tools GitHub preview](https://opengraph.githubassets.com/capell-readme/spatie/laravel-package-tools)](https://github.com/spatie/laravel-package-tools)

[![voku HtmlMin GitHub preview](https://opengraph.githubassets.com/capell-readme/voku/HtmlMin)](https://github.com/voku/HtmlMin)

## Screens And Workflow

Screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment.

- Frontend page before/after HTML output inspection.
- Middleware configuration or service provider registration proof.

## Technical Shape

- HtmlOptimizerServiceProvider registers the package.
- Http middleware: HtmlOptimizerMiddleware.
- Support class: HtmlMinifier.
- No migrations, config file, routes, resources, or models are present.

## Data Model

- This package does not own data.
- It transform-builder response content at render time.

## Install Impact

- Adds middleware capability.
- No database changes.
- No admin navigation.
- No public routes.

## Commands

- None proven in this package directory.

## Admin And Access

- None proven in this package directory.

- None proven in this package directory.

## Common Pitfalls

- Do not minify responses that contain whitespace-sensitive content without testing.
- Confirm middleware order with page cache middleware.
- Inspect HTML comments or inline scripts if output changes unexpectedly.

## Quick Start

1. Install the package with `composer require capell-app/html-optimizer`.
2. Register the package provider through Composer discovery and clear cached config if the host app uses config caching.
3. Open the new admin surface or integration point and verify the result.

## Next Steps

- [docs/overview.md](docs/overview.md)
- [../foundation-theme/README.md](../foundation-theme/README.md)
- [docs/credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
