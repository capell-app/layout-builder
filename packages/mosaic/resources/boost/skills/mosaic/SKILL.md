---
name: mosaic
description: Use when working on the Capell Mosaic package — Mosaic's own widget/section layout builder. Covers Widget and Section models, CapellLayoutManager container system, WidgetAsset two-tier asset model, Filament resources (LayoutResource, SectionResource, WidgetResource), EloquentJsonRelations-backed layout composition, LayoutTypeEnum, widget type classes, and how Mosaic differs architecturally from the standard Capell layout system.
---

# Mosaic — Flexible Widget & Section Layout Builder

Mosaic is the most powerful content composition system in the Capell ecosystem. It provides a **fully flexible, schema-less layout builder** built on independently publishable `Widget` and `Section` entities — a system that gives editors and developers complete control over page composition without predefined templates.

> **Critical distinction:** Capell core has a separate `Layout` model and a `layout` package. Mosaic is **architecturally independent** — it uses `CapellLayoutManager` and JSON-backed relationships, not the core Layout infrastructure. Do not conflate the two systems.

---

## Why Mosaic Exists

The standard Capell `Layout` model assigns a predefined template to a page. That works for simple sites — but Mosaic was built for the real world:

- **Widgets are first-class entities** — independently publishable, typed, translatable, and reusable across multiple pages and sections
- **No predefined templates** — containers are named dynamically; any widget can be placed in any slot
- **Multiple occurrences** — the same container slot can hold multiple widget instances
- **Two-tier asset model** — primary image via MorphOne, plus unlimited additional assets via `WidgetAsset`
- **Sections as organisational units** — group widgets and child pages without imposing a rigid hierarchy
- **JSON composition** — layout config lives in JSON relationships, so there are no extra join tables or rigid schema migrations for every layout variant

The result is a layout system that can power anything from a simple hero+content page to a fully custom-designed editorial spread, all managed through Filament with no custom code per layout variant.

---

## Two Layout Systems — Choosing the Right One

| System              | Package  | Key entities                       | Container mechanism                                  | When to use                                                            |
| ------------------- | -------- | ---------------------------------- | ---------------------------------------------------- | ---------------------------------------------------------------------- |
| **Standard layout** | `layout` | `Content`, `Layout` model          | Predefined page templates                            | Simple sites with fixed template structures                            |
| **Mosaic**          | `mosaic` | `Widget`, `Section`, `WidgetAsset` | `CapellLayoutManager` — named container slots + JSON | Complex editorial layouts, reusable widgets, flexible page composition |

When working in Mosaic, ignore the `Layout` model. When working with standard layouts, ignore Mosaic.

---

## Key Models

### Widget

The primary content entity in Mosaic. Widgets are **independently publishable** — they have their own type, status, publish dates, and lifecycle, separate from the pages they appear on. A single widget can appear on multiple pages and multiple sections simultaneously.

**Key fields:** `name`, `type_id`, `language_id`, `status`, `publish_at`, `publish_status`

**Relationships:**

- `image()` — MorphOne to Media (primary image)
- `backgroundImage()` — MorphOne to Media (background image)
- `assets()` / `widgetAssets()` — HasMany `WidgetAsset` (unlimited additional assets with metadata)
- `pages()` — MorphToMany (widget appears on multiple pages)
- `sections()` — MorphToMany (widget belongs to multiple sections)
- `layouts()` — HasManyJson (JSON-stored layout container configuration)

**Traits:** `HasType`, `HasStatus`, `Publishable`, `Statusable`, `HasCapellMedia`, `HasMetaData`, `HasUserstamps`, `LogsActivity`, `SoftDeletes`

### Section

An organisational container that groups widgets and/or child pages. Sections sit above widgets in the composition hierarchy and can link to a page via polymorphic relationship.

**Relationships:**

- `site()` — BelongsTo
- `linkedPage()` — MorphTo (optionally links to a Capell page)
- `related()` — BelongsToJson (JSON relationship for related items)
- `widgetAssets()` — HasMany
- `pages()` — HasMany child pages within this section
- `widgets()` — HasMany child widgets within this section

**Traits:** same set as Widget

### WidgetAsset

A lightweight asset wrapper — wraps a Spatie Media item with widget-level metadata. Supports per-page asset assignment via `pageAssets()`.

Use `WidgetAsset` when a widget needs more than the single `image()` morph — e.g. a carousel, a gallery, or assets that vary per page.

---

## Container System: CapellLayoutManager

This is the heart of Mosaic's flexibility. Instead of assigning a Layout model to a page, Mosaic places widgets into **named container slots** identified by a compound key:

```
(container_key, widget_key, occurrence)
```

- **container_key** — names the region on the page (e.g. `'main'`, `'sidebar'`, `'hero'`)
- **widget_key** — names the widget's role in that container
- **occurrence** — allows multiple instances of the same widget type in one slot (0-indexed)

Container configuration is stored as JSON in the widget's `layouts` relationship, powered by Staudenmeir's `EloquentJsonRelations` (`HasManyJson`). There are no extra join tables — the container assignment lives in the widget record itself.

```php
// Place a widget into a container slot
AddWidgetToLayoutContainerAction::run($widget, 'main', 'hero', 0);

// Place a second widget in the same container
AddWidgetToLayoutContainerAction::run($otherWidget, 'main', 'promo', 0);

// Resolve a container's width constraint (for responsive rendering)
GetWidgetContainerWidthAction::run('main');
```

### What makes this powerful

Traditional CMS layouts define a template: "this page has a hero, a three-column grid, and a footer". Mosaic inverts this — any widget can be placed in any named slot, and the template is assembled dynamically. You can:

- Place the same widget on 20 different pages without duplicating data
- Add a new container slot without a migration
- Reorder, remove, or replace widgets per page without touching template files
- Stack multiple widget instances in one slot using `occurrence`

---

## Key Actions

| Action                                | Purpose                                                                  |
| ------------------------------------- | ------------------------------------------------------------------------ |
| `MakeWidgetAction`                    | Scaffolds a new widget: generates Blade view template and seeder snippet |
| `AddWidgetToLayoutContainerAction`    | Places a widget into a named container slot                              |
| `AddHeroWidgetToLayoutAction`         | Adds hero widgets to their designated container                          |
| `CreateHeroWidgetAction`              | Instantiates a Hero widget entity                                        |
| `CreateHeroContentTypeAction`         | Bootstraps Hero-specific content types in the type registry              |
| `ReplicateContentAction`              | Clones widget/section content across pages or languages                  |
| `MutateContentDataBeforeFillAction`   | Hook to modify widget data before Filament form fill                     |
| `SaveFormComponentRelationshipAction` | Persists form-component relationships after save                         |
| `GetWidgetContainerWidthAction`       | Resolves the width constraint for a named container                      |

---

## Widget Types

Widget types follow the same pattern as Capell page types — a class defining Filament schema fields and a Blade view. Each type is registered via `CapellCore::registerWidget()` and auto-discovered from registered namespaces.

```php
<?php

declare(strict_types=1);

namespace MyPackage\Widgets;

use Capell\Core\Types\AbstractWidget;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CallToActionWidget extends AbstractWidget
{
    public static function getName(): string
    {
        return 'Call To Action';
    }

    public static function schema(Schema $schema): array
    {
        return [
            TextInput::make('title')->required(),
            TextInput::make('button_text')->required(),
            TextInput::make('button_url')->url()->required(),
            ColorPicker::make('background_color'),
        ];
    }

    public static function getView(): string
    {
        return 'my-package::widgets.call-to-action';
    }
}
```

Register in your service provider:

```php
CapellCore::registerWidget(CallToActionWidget::class);
```

Scaffold the Blade view with:

```bash
php artisan capell:mosaic-make-widget CallToAction
```

---

## Filament Resources

### LayoutResource

Manages layout configurations for Mosaic — uses `LayoutSchemaExtender` to extend the page and content schemas with Mosaic-specific container options.

### SectionResource

Full CRUD for sections. Relation managers:

- `PagesRelationManager` — child pages within this section
- `WidgetsRelationManager` — child widgets within this section
- `SectionAssetsRelationManager` — assets belonging to this section

Widget: `SectionAlertsWidget` — status indicators for the section.

### WidgetResource

Full CRUD for widgets. Includes:

- `WidgetsTable` — main list view
- `WidgetAssetsTable` — assets attached to a widget
- `WidgetSelectionTable` — specialised table for picking a widget inside form builders

### LayoutTypeEnum

Provides polymorphic handling for both `Section` and `Widget` as "layoutable" types. Each enum case maps to its model, Filament resource, and database table — allowing a single admin interface to manage both entity types through one consistent pattern.

---

## Two-Tier Asset System

| Tier              | Accessor                     | Backed by           | When to use                                           |
| ----------------- | ---------------------------- | ------------------- | ----------------------------------------------------- |
| Primary image     | `$widget->image()`           | MorphOne to Media   | Single hero or feature image                          |
| Background image  | `$widget->backgroundImage()` | MorphOne to Media   | Background / decorative image                         |
| Additional assets | `$widget->widgetAssets()`    | HasMany WidgetAsset | Carousel, gallery, multiple files, or per-page assets |

`WidgetAsset` carries widget-level metadata beyond what Spatie Media provides. The `pageAssets()` method on `WidgetAsset` supports assigning different assets to the same widget on a per-page basis.

---

## Integration Points

| Hook                                           | How                                                                |
| ---------------------------------------------- | ------------------------------------------------------------------ |
| Register a widget type                         | `CapellCore::registerWidget(MyWidget::class)`                      |
| Extend page form with Mosaic container options | Implement `PageSchemaExtender`, tag with `PageSchemaExtender::TAG` |
| React to layout loading                        | Listen to `LayoutLoaded` event                                     |
| React to layout saving                         | Listen to `LayoutSavingListener` event                             |
| Hook into site tree rebuild                    | Listen to `SiteTreeRebuilt` event                                  |

---

## Commands

| Command                                        | Purpose                                       |
| ---------------------------------------------- | --------------------------------------------- |
| `php artisan capell:mosaic-make-widget {name}` | Scaffold widget Blade view and seeder snippet |
| `php artisan mosaic:install`                   | Package installation                          |
| `php artisan mosaic:setup`                     | Post-install configuration                    |
| `php artisan mosaic:demo`                      | Seed demo widget and section data             |
| `php artisan mosaic:faker`                     | Generate fake widget data for testing         |
| `php artisan mosaic:hero:setup`                | Initialise hero widget configuration          |
| `php artisan mosaic:hero:demo`                 | Seed hero demo content                        |
| `php artisan mosaic:upgrade`                   | Run version upgrade steps                     |

---

## Testing Mosaic

Test actions with real data — JSON relationships and the container system require a real DB to resolve correctly. Mocks miss `EloquentJsonRelations` behaviour.

```php
it('places a widget into a named container slot', function () {
    $widget = Widget::factory()->create();

    AddWidgetToLayoutContainerAction::run($widget, 'main', 'hero', 0);

    expect($widget->fresh()->layouts)->not->toBeEmpty();
});

it('supports multiple widget occurrences in the same container', function () {
    $widgetA = Widget::factory()->create();
    $widgetB = Widget::factory()->create();

    AddWidgetToLayoutContainerAction::run($widgetA, 'main', 'promo', 0);
    AddWidgetToLayoutContainerAction::run($widgetB, 'main', 'promo', 1);

    $container = CapellLayoutManager::getContainer('main');

    expect($container)->toHaveCount(2);
});

it('replicates widget content across pages', function () {
    $widget = Widget::factory()->create();
    $targetPage = Page::factory()->create();

    ReplicateContentAction::run($widget, $targetPage);

    expect(Widget::where('name', $widget->name)->count())->toBe(2);
});
```
