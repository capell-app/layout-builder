# Frontend Authoring

Frontend Authoring is Capell's admin bridge for rendered frontend pages. It owns the beacon response, in-page edit manifest, signed edit routes, and cache-aware field saves.

## What It Does

- Adds the `capell-frontend.beacon` route used after the public page has loaded.
- Returns authoring scripts and editable region metadata only after admin access is confirmed.
- Opens signed single-field edit screens from hover controls.
- Saves page title, page description, and page HTML content without exposing record metadata in cached HTML.
- Clears every cached URL recorded against the edited model in `CacheEnum::modelUrlCacheKey()`.

For the full workflow, screenshot contract, and browser-test checklist, see [In-page editing](docs/in-page-editing.md).

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Admin](https://github.com/capell-app/admin)
- [Capell Frontend](https://github.com/capell-app/frontend)

**Open-source packages used here**

- [Spatie Laravel Package Tools](https://github.com/spatie/laravel-package-tools) - Laravel package bootstrapping for config, migrations, commands, translations, and service provider setup.

**Linked package previews**

[![Spatie Laravel Package Tools GitHub preview](https://opengraph.githubassets.com/capell-readme/spatie/laravel-package-tools)](https://github.com/spatie/laravel-package-tools)

## HTML Cache Safety

The package does not render authoring attributes, containers, or scripts into cached Blade output. Public cached HTML stays ordinary theme HTML. After the page loads, the beacon resolves the current URL, checks the authenticated user, and returns selector-based edit regions with signed edit URLs only for admins.

That keeps direct static HTML serving from `public/page-cache` safe: anonymous and non-admin users never receive editor HTML, editor JavaScript, model IDs, field paths, labels, selectors, package hints, or signed authoring URLs.

## Extension

Packages can tag callables with `capell-frontend-authoring:editable-regions`. A callable receives the resolved `PageUrl` and returns additional `EditableRegionPayloadData` instances.

Use stable public selectors only. Do not add hidden authoring metadata to cached frontend markup.

## Install

```bash
composer require capell-app/frontend-authoring
```

Then install or enable the package through Capell's package workflow.

Screenshot/demo environments that need to prove editing works must also Composer require the frontend stack listed in [In-page editing](docs/in-page-editing.md#screenshot-package-requirements).

## Package Docs

- [docs/credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
