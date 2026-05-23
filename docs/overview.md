# Layout Builder

Status: **Available, schema-owning** · Kind: **package** · Tier: **free** · Bundle: **foundation** · Contexts: **admin, frontend, console** · Product group: **Capell Foundation**

Layout Builder owns Capell's visual composition layer: reusable layouts, blocks, block assets, content-first editing, public layout rendering, layout areas, presets, and block visual-regression manifests.

## Install

```bash
composer require capell-app/layout-builder
php artisan capell:layout-builder-install
```

The package requires `capell-app/admin`, `capell-app/content-blocks`, `capell-app/core`, and `capell-app/frontend` through Composer. The isolated audit harness confirmed that `content-blocks` is installed as a hard Composer dependency.

## Admin Surfaces

- `BlockResource` for reusable blocks, block metadata, block assets, and layout relationships.
- `LayoutResource`, extending the core Layouts admin resource with package-specific table and editor behaviour.
- Page schema extenders for layout/content-first editing and hero editing.
- Layout schema extender for package layout fields.
- Livewire layout builder component and Filament assets.
- Dashboard widgets for layout health and recent activity when enabled by the host admin surface.

## Frontend Surfaces

- Public layout components under `resources/views/components/layout`.
- Main-content and named layout-area rendering through the resolved layout graph.
- Public block payload resolution through `BuildPublicLayoutGraphAction`.
- No standalone public route is registered by this package.

Public Blade must stay query-free and authoring-free. Rendered HTML should not expose editor state, signed URLs, field paths, admin labels, internal model identifiers, or package diagnostics.

## Screenshot Plan

- Blocks admin index.
- Create/edit block form, including block assets.
- Block layouts relation manager.
- Layouts admin index with Layout Builder table extensions.
- Page form layout/content-first editor tab.
- Hero editor page extension.
- Public main content render.
- Public named layout area render.
- Layout health dashboard widget.
- Recent activity dashboard widget.

## Verification

- Package tests: `vendor/bin/pest packages/layout-builder/tests --configuration=phpunit.xml`.
- Harness install: `composer require capell-app/layout-builder:4.x-dev -W`, then `php artisan package:discover --ansi` and `php artisan migrate --graceful --ansi`.

## Known Risks

- `capell.json` lists core/frontend as hard dependencies, but Composer also requires `capell-app/admin` and `capell-app/content-blocks`; align the manifest before package catalog publication.
- Frontend screenshots need seeded layouts and blocks to prove public rendering coverage.
- Content-first and layout-first editor screenshots should be captured separately because they exercise different editor states.
