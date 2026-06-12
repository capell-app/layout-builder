# Capell Layout Builder

`capell-app/layout-builder` owns Capell's visual layout composition layer: layout containers, widgets, widget assets, public layout graphs, content-first editing, and the Filament layout editor.

Core still owns sites, pages, languages, URLs, themes, and base content models. Admin still owns the Filament panel shell. Layout Builder plugs into both through package registrars and exposes its public API from the `Capell\LayoutBuilder` namespace.

## At A Glance

| Field               | Value                                                  |
| ------------------- | ------------------------------------------------------ |
| Composer package    | `capell-app/layout-builder`                            |
| Namespace           | `Capell\LayoutBuilder`                                 |
| Surfaces            | Admin, Livewire editor, public rendering, console      |
| Provider            | `Capell\LayoutBuilder\LayoutBuilderServiceProvider`    |
| Public graph Action | `BuildPublicLayoutGraphAction`                         |
| Editor component    | `Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder` |
| Install command     | `capell:layout-builder-install` in a host Capell app   |

## Why It Helps Your Capell Workflow

- Provides the visual composition layer for Capell: layouts, containers, widgets, assets, public render graphs, and editor mutations.
- Helps editors assemble pages without storing theme-specific presentation markup in database content fields.
- Gives developers Actions and registries for public-safe layout payloads, reusable presets, layout areas, and content-first editing.
- Lets admins make reviewed bulk layout changes across many layouts, with a stored preview, exact per-layout diffs, page impact counts, warnings, approval, and hash-guarded apply.

## Best Used With

- [Block Library](../block-library/README.md)
- [Content Sections](../content-sections/README.md)
- [Foundation Theme](../foundation-theme/README.md)

## What It Adds

- Layout, widget, widget asset, reusable preset, and bulk-change models.
- Filament layout resources, schemas, configurators, and Livewire editor surfaces.
- Public layout graph and widget payload Actions.
- Layout areas for theme-owned placement zones such as headers.
- Content-first editor inventory and widget asset persistence.
- Reviewed bulk layout mutations with preview, approval, drift detection, and revert support.

## Install

In a host Capell app:

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

The visual editor preview uses breakpoint-aware canvas width variables and keeps save controls in a sticky preview action bar so desktop, tablet, and mobile frames stay usable inside narrower admin panels.
| `widget.skip_render_empty` | Skip empty widgets in public rendering. |
| `default_widget` | Default renderable key for new widgets. |

## Boundaries

Layout Builder owns visual composition, layout graphs, editor mutations, widget assets, reusable presets, and public layout rendering. Core owns sites, pages, languages, URLs, themes, and base content models.

Public Blade must not query the database, lazy-load relationships, expose raw widget meta, include authoring selectors, or leak signed admin URLs, diagnostics, package internals, schema labels, or preview/admin view names.

## Main Surfaces

| Surface                        | Package path                                                                                                       |
| ------------------------------ | ------------------------------------------------------------------------------------------------------------------ |
| Public graph building          | `src/Actions/BuildPublicLayoutGraphAction.php`                                                                     |
| Public widget payloads         | `src/Contracts/PublicWidgetPayloadContributor.php`, `src/Support/DefaultPublicWidgetPayloadResolver.php`           |
| Layout areas                   | `src/Support/LayoutAreas/LayoutAreaRegistry.php`, `src/Actions/ResolveLayoutAreaContainersAction.php`              |
| Widget presentation projection | `src/Actions/ResolveWidgetPresentationDataAction.php`                                                              |
| Layout health checks           | `src/Actions/AnalyzeLayoutHealthAction.php`                                                                        |
| Reusable layout presets        | `src/Models/LayoutPreset.php`, `src/Actions/SaveLayoutPresetAction.php`, `src/Actions/ApplyLayoutPresetAction.php` |
| Content-first inventory        | `src/Actions/BuildLayoutContentInventoryAction.php`                                                                |
| Layout mutations               | `src/Actions/Mutations/`                                                                                           |
| Bulk layout changes            | `src/Actions/BulkChanges/`, `src/Console/Commands/LayoutBulkChangeCommand.php`                                     |
| Filament resources and schemas | `src/Filament/`                                                                                                    |
| Livewire editor                | `src/Livewire/Filament/LayoutBuilder.php`                                                                          |
| Admin views and components     | `resources/views/`                                                                                                 |

## Runtime Surface

The table above is the package code map. Start with `BuildPublicLayoutGraphAction` for public rendering, `src/Actions/Mutations/` for editor mutations, `src/Actions/BulkChanges/` for reviewed broad edits, and `src/Livewire/Filament/LayoutBuilder.php` for the admin editor.

## Bulk Layout Changes With Review Approval

Large Capell sites often need a layout change that is simple in one page and risky across dozens of shared layouts: move breadcrumbs below the hero, remove a widget, swap two widgets, or move a widget from a sidebar container into the main container.

Layout Builder handles that as a reviewed mutation workflow instead of a one-off script:

1. Define criteria and a guided operation.
2. Store a preview run with the exact target layouts, page counts, before and after container state, structured diffs, warnings, skipped records, and hashes.
3. Review the preview in the Filament action or from the Artisan command.
4. Approve the stored run when the result is correct.
5. Apply with hash guards so layouts changed after preview are skipped as drifted instead of overwritten.

The first typed operations are `move_widget`, `remove_widget`, `swap_widgets`, and `move_widget_to_container`. Criteria can filter by site, theme, group, layout key, active state, and required widget. Specific widget occurrences can be targeted when a repeated widget appears more than once.

Page-scoped `widget_assets` follow moved widgets when container or occurrence changes. Remove operations can warn about page-scoped assets or delete them explicitly. Default widget assets are not rewritten globally; ambiguous default asset moves are blocked so editors do not accidentally change shared content assignments.

The same Actions power both surfaces:

In a host Capell app:

```bash
php artisan capell:layouts:bulk-change --spec=/path/change.json --preview
php artisan capell:layouts:bulk-change --approve=run-uuid
php artisan capell:layouts:bulk-change --revert=run-uuid
```

Marketplace screenshots for this workflow live in `docs/assets/marketplace/` and are declared in `capell.json` so the Capell website can show the criteria, review, approval, content-first editor, layout areas, and public output states.

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

Payload contributors are tagged with `Capell\LayoutBuilder\Contracts\PublicWidgetPayloadContributor::TAG`. Contributors should return public-safe data or HTML only; do not expose admin state, editor-only metadata, private IDs, or unpublished content.

Widget variants and settings are stored as authoring state in widget meta, but public rendering receives only the sanitized `presentation` payload:

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

Public Blade must not query the database, lazy-load relationships, resolve widget contracts, expose raw meta, or include authoring selectors, signed URLs, diagnostics, package internals, schema, labels, or preview/admin view names.

## Layout Areas

Layout areas let a theme expose named places where normal Layout Builder widgets can render outside the main page-body loop. The storage model stays unchanged: widgets still live inside layout containers, and containers may set `meta.area`.

- Missing `meta.area` is treated as `main` for backwards compatibility.
- `main` is built in and rendered by the normal main-content hook.
- Themes and packages can register extra areas through `Capell\LayoutBuilder\Support\LayoutAreas\LayoutAreaRegistry`.
- The Foundation Theme registers `header`, so editors can place normal widgets into the site header without hidden containers or a separate data model.

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

The area component reads the already-resolved layout containers and uses the stored `CapellLayoutManager` widget instances. Keep public Blade query-free and authoring-free; area keys are public placement data, but editor state, model IDs, field paths, signed URLs, and package/admin metadata must stay out of the HTML.

Apps and package seeders should use `AttachWidgetToLayoutAreaAction` when placing widgets into a named area. The action creates the area container when needed, normalizes the area key, preserves existing container metadata, and avoids duplicate widget/occurrence pairs.

```php
use Capell\LayoutBuilder\Actions\AttachWidgetToLayoutAreaAction;

AttachWidgetToLayoutAreaAction::run(
    layout: $layout,
    area: 'header',
    widgetKey: 'announcement-bar',
    containerKey: 'site-announcement',
    containerMeta: ['container' => 'full'],
);
```

For full-bleed sections, separate the two width settings deliberately. The layout container owns the section band and background, so set its container meta to `full`. The widget or widget inside owns readable content, so keep its own `container` meta at the default contained width. This gives edge-to-edge backgrounds while text, media, and controls stay aligned with the site container.

Avoid solving this in widget Blade with `w-screen`, negative margins, or translate hacks. Those make the widget fight the layout system and usually break once the same widget is reused in another theme or container.

## Reusable Presets

Saved agency presets are persisted in `layout_presets` and scoped to a required `site_id` with optional `theme_key`. Presets are layout-only by default: they deep-copy structure, selected widget variants, and settings without duplicating client content. Applying a preset revalidates site scope and regenerates duplicate anchors.

The older in-session `LayoutPresetRepository` remains only for temporary editor fragments; package and agency presets should use `SaveLayoutPresetAction` and `ApplyLayoutPresetAction`.

## Visual Regression Manifests

Use `capell:layout-builder-widget-visual-regression capture` or `assert` to emit deterministic widget/variant/viewport fixture entries for a screenshot runner. The command supports `--widget`, `--theme`, `--variant`, `--changed`, `--concurrency`, and `--ci-limit`.

The command does not authenticate, generate signed routes, query tenant content, or use live media. Browser capture/compare remains the responsibility of the runner.

## Docs

- [docs index](docs/README.md)
- [overview.md](docs/overview.md)
- [screenshots.json](docs/screenshots.json)

## Editor Modes

`content_first` is the default mode. It shows editor-facing content groups from the current layout state and lets editors update assigned widget assets without navigating the full layout canvas.

`layout_first` opens the drag/drop layout builder directly. Keep it available for designers and implementers who need placement and structure control.

Both modes write through the same `WidgetAsset` persistence path.

## Testing

Run the package suite from the packages monorepo:

```bash
vendor/bin/pest packages/layout-builder/tests --configuration=phpunit.xml
```

Run the focused public graph and package-boundary checks after changing public rendering or package ownership:

```bash
vendor/bin/pest packages/layout-builder/tests/Integration/PublicLayoutGraphActionTest.php packages/layout-builder/tests/Arch/LayoutBuilderPackageBoundaryTest.php --configuration=phpunit.xml
```
