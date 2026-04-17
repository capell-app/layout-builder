# Database Reference — Capell Layout

Layout ships three tables plus one alter on the core `layouts` table.

## Migrations

| File                                                             | Effect                                                        |
| ---------------------------------------------------------------- | ------------------------------------------------------------- |
| `database/migrations/create_contents_table.php`                  | Create `contents`                                             |
| `database/migrations/create_widgets_table.php`                   | Create `widgets`                                              |
| `database/migrations/create_widget_assets_table.php`             | Create `widget_assets`                                        |
| `database/migrations/add_container_widgets_to_layouts_table.php` | Add container/widget JSON columns to the core `layouts` table |

Run them via `php artisan capell:layout-install`, or directly with `php artisan migrate` after the package is registered.

## `contents`

Reusable content records. Workspace-scoped, translatable, hierarchical (nested set).

Key columns: `id`, `workspace_id`, `site_id`, `type_id`, `parent_id`, `lft`, `rgt`, `key`, `name`, `status`, `start_date`, `end_date`, `meta` (JSON), translation columns (via `HasTranslations`), userstamps, soft deletes.

Behaviour:

- **Workspace-aware** via `BelongsToWorkspace` — live rows use `workspace_id = 0`; edits live in non-zero workspace copies.
- **Translatable** — translatable fields stored through the Capell translations layer.
- **Nested set** — `parent_id` + `lft`/`rgt` enable tree structures (used for grouping related content).
- **Publishable** — `status`, `start_date`, `end_date` drive visibility.
- **Typeable** — `type_id` points at a Capell Type row whose schema defines the editable fields.
- **Cloneable** — supports the Capell page/content clone action.

## `widgets`

A placed UI component instance.

Key columns: `id`, `workspace_id`, `site_id`, `type_id`, `key` (unique per workspace), `status`, `order`, `meta` (JSON), userstamps, soft deletes.

Behaviour:

- Workspace-aware, translatable, publishable, typeable, cloneable (same traits as Content, minus nested set and assets).
- `key` is a human-readable handle used by builder JSON and by content lookups.
- Widgets are **placed** on a layout — their container + occurrence positions live in the layout JSON (see below), not on this table.

## `widget_assets`

Polymorphic links from widgets to other records (usually media) with multi-slot positioning.

Key columns: `id`, `workspace_id`, `widget_id`, `asset_id`, `asset_type`, `pageable_id`, `pageable_type`, `container`, `occurrence`, `order`.

Used by any widget that needs to hold _multiple_ references — e.g. a carousel's slides, a feature grid's cards, a testimonials set.

## `layouts` (altered)

`add_container_widgets_to_layouts_table` adds JSON columns to the core layouts table so a layout can store:

- Its container tree (rows, columns, nested containers).
- The widget graph (which widget key lives at which container/occurrence).

This is what `Layout::layoutWidgets()` returns.

## Factories

Every model ships a factory in `database/factories/`:

- `ContentFactory`
- `WidgetFactory`
- `WidgetAssetFactory`

Use them in tests and seeders:

```php
use Capell\Layout\Models\Collection;

$content = Content::factory()->for($site)->create(['name' => 'Welcome']);
```

## Runtime relationships

Registered in `LayoutServiceProvider`:

- `Page::contents()` — `HasMany` through the page/content join
- `Page::widgets()` — widgets placed via the page's layout
- `Page::widgetAssets()` — assets reached through those widgets
- `Site::contents()` — every content on the site
- `Type::contents()` / `Type::widgets()` — type-scoped accessors
- `Layout::layoutWidgets()` — the JSON-stored widget graph (not a DB relation — resolved from the layout JSON)
