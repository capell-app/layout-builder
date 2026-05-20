# Capell Layout Builder

`capell-app/layout-builder` owns Capell's visual layout composition layer: layout containers, blocks, block assets, public layout graphs, content-first editing, and the Filament layout editor.

Core still owns sites, pages, languages, URLs, themes, and base content models. Admin still owns the Filament panel shell. Layout Builder plugs into both through package registrars and exposes its public API from the `Capell\LayoutBuilder` namespace.

## Install

```bash
composer require capell-app/layout-builder
php artisan capell:layout-builder-install
```

The install command publishes and runs the package migrations listed by `Capell\LayoutBuilder\Support\CapellLayoutBuilderManager`.

## Configuration

Configuration lives in `config/capell-layout-builder.php`.

| Key                                       | Purpose                                                               |
| ----------------------------------------- | --------------------------------------------------------------------- |
| `editor_mode.default`                     | Default editor mode. Defaults to `content_first`.                     |
| `editor_mode.allowed`                     | Allowed modes. Current values are `content_first` and `layout_first`. |
| `preview.match_frontend_container_layout` | Match admin preview container layout to frontend columns.             |
| `block.skip_render_empty`                 | Skip empty blocks in public rendering.                                |
| `default_block`                           | Default renderable key for new blocks.                                |

## Main Surfaces

| Surface                        | Package path                                                                                                       |
| ------------------------------ | ------------------------------------------------------------------------------------------------------------------ |
| Public graph building          | `src/Actions/BuildPublicLayoutGraphAction.php`                                                                     |
| Public block payloads          | `src/Contracts/PublicBlockPayloadContributor.php`, `src/Support/DefaultPublicBlockPayloadResolver.php`             |
| Layout areas                   | `src/Support/LayoutAreas/LayoutAreaRegistry.php`, `src/Actions/ResolveLayoutAreaContainersAction.php`              |
| Block presentation projection  | `src/Actions/ResolveBlockPresentationDataAction.php`                                                               |
| Layout health checks           | `src/Actions/AnalyzeLayoutHealthAction.php`                                                                        |
| Reusable layout presets        | `src/Models/LayoutPreset.php`, `src/Actions/SaveLayoutPresetAction.php`, `src/Actions/ApplyLayoutPresetAction.php` |
| Content-first inventory        | `src/Actions/BuildLayoutContentInventoryAction.php`                                                                |
| Layout mutations               | `src/Actions/Mutations/`                                                                                           |
| Filament resources and schemas | `src/Filament/`                                                                                                    |
| Livewire editor                | `src/Livewire/Filament/LayoutBuilder.php`                                                                          |
| Admin views and components     | `resources/views/`                                                                                                 |

## Public Rendering

Use `Capell\LayoutBuilder\Actions\BuildPublicLayoutGraphAction` when a public route, API, or package needs layout content without depending on the frontend renderer.

```php
$graph = BuildPublicLayoutGraphAction::run(
    layout: $layout,
    page: $page,
    language: $language,
    containers: ['main'],
    includeHtml: false,
);
```

Payload contributors are tagged with `Capell\LayoutBuilder\Contracts\PublicBlockPayloadContributor::TAG`. Contributors should return public-safe data or HTML only; do not expose admin state, editor-only metadata, private IDs, or unpublished content.

Block variants and settings are stored as authoring state in block meta, but public rendering receives only the sanitized `presentation` payload:

```php
[
    'variant' => 'split-media',
    'spacing' => 'normal',
    'background' => 'default',
    'mediaPosition' => 'top',
    'cardsPerRow' => 3,
    'showCta' => true,
    'headingWidth' => 'normal',
    'anchorId' => null,
]
```

Public Blade must not query the database, lazy-load relationships, resolve block contracts, expose raw meta, or include authoring selectors, signed URLs, diagnostics, package internals, schema, labels, or preview/admin view names.

## Layout Areas

Layout areas let a theme expose named places where normal Layout Builder blocks can render outside the main page-body loop. The storage model stays unchanged: blocks still live inside layout containers, and containers may set `meta.area`.

- Missing `meta.area` is treated as `main` for backwards compatibility.
- `main` is built in and rendered by the normal main-content hook.
- Themes and packages can register extra areas through `Capell\LayoutBuilder\Support\LayoutAreas\LayoutAreaRegistry`.
- The Foundation Theme registers `header`, so editors can place normal blocks into the site header without hidden containers or a separate data model.

Register areas from a package service provider after the registry resolves:

```php
use Capell\LayoutBuilder\Support\LayoutAreas\LayoutAreaRegistry;

$this->app->afterResolving(
    LayoutAreaRegistry::class,
    function (LayoutAreaRegistry $registry): void {
        $registry->register(
            key: 'header',
            label: __('capell-layout-builder::generic.header_area'),
        );
    },
);
```

If an area only applies to one active theme, pass the theme key:

```php
$registry->register(
    key: 'announcement',
    label: __('capell-theme-client::layout_areas.announcement'),
    themeKey: 'client',
);
```

Public area rendering should use the package renderer rather than querying from Blade:

```blade
<x-capell::layout.area area="header" />
```

The area component reads the already-resolved layout containers and uses the stored `CapellLayoutManager` block instances. Keep public Blade query-free and authoring-free; area keys are public placement data, but editor state, model IDs, field paths, signed URLs, and package/admin metadata must stay out of the HTML.

Apps and package seeders should use `AttachBlockToLayoutAreaAction` when placing blocks into a named area. The action creates the area container when needed, normalizes the area key, preserves existing container metadata, and avoids duplicate block/occurrence pairs.

```php
use Capell\LayoutBuilder\Actions\AttachBlockToLayoutAreaAction;

AttachBlockToLayoutAreaAction::run(
    layout: $layout,
    area: 'header',
    blockKey: 'announcement-bar',
    containerKey: 'site-announcement',
    containerMeta: ['container' => 'full'],
);
```

## Reusable Presets

Saved agency presets are persisted in `layout_presets` and scoped to a required `site_id` with optional `theme_key`. Presets are layout-only by default: they deep-copy structure, selected block variants, and settings without duplicating client content. Applying a preset revalidates site scope and regenerates duplicate anchors.

The older in-session `LayoutPresetRepository` remains only for temporary editor fragments; package and agency presets should use `SaveLayoutPresetAction` and `ApplyLayoutPresetAction`.

## Visual Regression Manifests

Use `capell:layout-builder-block-visual-regression capture` or `assert` to emit deterministic block/variant/viewport fixture entries for a screenshot runner. The command supports `--block`, `--theme`, `--variant`, `--changed`, `--concurrency`, and `--ci-limit`.

The command does not authenticate, generate signed routes, query tenant content, or use live media. Browser capture/compare remains the responsibility of the runner.

## Docs

- [overview.md](docs/overview.md)
- [screenshots.json](docs/screenshots.json)

## Editor Modes

`content_first` is the default mode. It shows editor-facing content groups from the current layout state and lets editors update assigned block assets without navigating the full layout canvas.

`layout_first` opens the drag/drop layout builder directly. Keep it available for designers and implementers who need placement and structure control.

Both modes write through the same `BlockAsset` persistence path.

## Tests

Run the package suite from the packages monorepo:

```bash
vendor/bin/pest packages/layout-builder/tests --configuration=phpunit.xml
```

Run the focused public graph and package-boundary checks after changing public rendering or package ownership:

```bash
vendor/bin/pest packages/layout-builder/tests/Integration/PublicLayoutGraphActionTest.php packages/layout-builder/tests/Arch/LayoutBuilderPackageBoundaryTest.php --configuration=phpunit.xml
```
