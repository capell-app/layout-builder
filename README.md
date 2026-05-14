# Capell Layout Builder

`capell-app/layout-builder` owns Capell's visual layout composition layer: layout containers, widgets, widget assets, public layout graphs, content-first editing, and the Filament layout editor.

Core still owns sites, pages, languages, URLs, themes, and base content models. Admin still owns the Filament panel shell. Layout Builder plugs into both through package registrars and keeps old `Capell\Core\LayoutBuilder\*` and `Capell\Admin\LayoutBuilder\*` namespaces available through compatibility adapters for one release.

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
| `widget.skip_render_empty`                | Skip empty widgets in public rendering.                               |
| `default_widget`                          | Default renderable key for new widgets.                               |

## Main Surfaces

| Surface                        | Package path                                                                                             |
| ------------------------------ | -------------------------------------------------------------------------------------------------------- |
| Public graph building          | `src/Actions/BuildPublicLayoutGraphAction.php`                                                           |
| Public widget payloads         | `src/Contracts/PublicWidgetPayloadContributor.php`, `src/Support/DefaultPublicWidgetPayloadResolver.php` |
| Content-first inventory        | `src/Actions/BuildLayoutContentInventoryAction.php`                                                      |
| Layout mutations               | `src/Actions/Mutations/`                                                                                 |
| Filament resources and schemas | `src/Filament/`                                                                                          |
| Livewire editor                | `src/Livewire/Filament/LayoutBuilder.php`                                                                |
| Admin views and components     | `resources/views/`                                                                                       |

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

## Editor Modes

`content_first` is the default mode. It shows editor-facing content groups from the current layout state and lets editors update assigned widget assets without navigating the full layout canvas.

`layout_first` opens the drag/drop layout builder directly. Keep it available for designers and implementers who need placement and structure control.

Both modes write through the same `WidgetAsset` persistence path.

## Tests

Run the package suite from the packages monorepo:

```bash
vendor/bin/pest packages/layout-builder/tests --compact
```

Run the focused public graph and package-boundary checks after changing public rendering or package ownership:

```bash
vendor/bin/pest packages/layout-builder/tests/Integration/PublicLayoutGraphActionTest.php packages/layout-builder/tests/Arch/LayoutBuilderPackageBoundaryTest.php --compact
```
