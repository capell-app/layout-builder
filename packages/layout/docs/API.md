# API Reference — Capell Layout

Browse `src/` for full source. This page is a map of the key entry points.

## Service provider

- `src/Providers/LayoutServiceProvider.php` — registers Filament resources, schemas, commands, publishes, listeners, and Blade view namespaces.

## Models

All workspace-aware and factory-backed:

- `src/Models/Content.php` — `contents` table
- `src/Models/Widget.php` — `widgets` table
- `src/Models/WidgetAsset.php` — `widget_assets` table

## Filament resources

- `src/Filament/Resources/ContentResource/` — Contents CRUD + relation managers (Assets, Pages, Widgets)
- `src/Filament/Resources/WidgetResource/` — Widgets CRUD + Layouts relation manager
- `src/Filament/Resources/LayoutResource/` — extends the core Admin LayoutResource with the layout builder table configuration

## Form components (the layout builder)

Found under `src/Filament/Components/Forms/`. The main surfaces are:

- The layout builder itself (container + widget arrangement UI)
- Widget selection and settings editors
- Content selectors for linking widgets to content records
- Translation tabs for translatable schemas

## Actions

Single-purpose invokables under `src/Actions/`:

| Action | Purpose |
| --- | --- |
| `AddWidgetToLayoutContainerAction` | Place a widget in a named container at an occurrence |
| `CreateContentAction` | Create a Content with its Type and initial translations |
| `GetWidgetContainerWidthAction` | Resolve the rendered width class for a widget based on its container |
| `InstallPackageAction` | Underlying work for `capell:layout-install` |
| `ModifyContentSelectCreateAction` | Customise the "create new" flow inside content selectors |
| `MutateContentDataBeforeFillAction` | Transform content data before a Filament form fills |
| `ReplicateContentAction` | Deep-clone a content (for the clone page/widget action) |
| `SaveFormComponentRelationshipAction` | Persist relationships from nested form components |

## Enums

Registered schema and component identifiers under `src/Enums/`:

- `ContentSchemaEnum`, `ContentTypeEnum` — Content type/schema keys
- `WidgetSchemaEnum`, `WidgetTypeEnum`, `WidgetTypeGroupEnum` — Widget type/schema keys
- `LayoutContainerSchemaEnum`, `LayoutTypeEnum`, `LayoutWidgetSchemaEnum` — Layout builder schemas
- `TypeSchemaEnum`, `TypeEnum`, `SchemaExtenderEnum` — registry identifiers shared with the Admin package
- `AssetComponentEnum`, `AssetEnum`, `WidgetAssetSchemaEnum`, `WidgetComponentEnum` — asset/widget component identifiers
- `ActionLinkEnum`, `ComponentTypeEnum`, `LivewireComponentsEnum`, `ModelEnum`, `ResourceEnum`, `CapellLayoutCacheKeyEnum` — misc. registry identifiers

## Blade view namespace

Views are exposed under the `capell::` namespace. Components ship under `resources/views/components/` and include:

- `capell::layout.main` — top-level layout renderer
- `capell::layout.container`, `capell::layout.widget` — structural renderers
- `capell::components.widget.*` — widget templates (asset, navigation, page/children, page/content, page/latest, page/siblings)
- `capell::components.asset.*` — asset templates (accordion, carousel, features, media, pages, blocks, banners, testimonials)

Widgets from other packages follow the same pattern: e.g. the Hero package publishes `capell-hero::components.widget.hero`.

## Listeners

Registered in the service provider:

- `AfterRecordSavedListener` — keeps widget/content cache coherent after admin saves
- `LayoutLoadedListener` — hydrates the layout builder on edit
- `LayoutSavingListener` — persists the widget graph when a layout saves
- `SiteTreeRebuiltListener` — refreshes layout JSON when the site tree rebuilds
- `TypeValidatedListener` — updates Type-driven references after a Type edit

## Commands

Under `src/Console/Commands/`:

- `InstallCommand` (`capell:layout-install`)
- `SetupCommand` (`capell:layout-setup`)
- `UpgradeCommand` (`capell:layout-upgrade`)
- `DemoCommand` (`capell:layout-demo`)

## Extending Layout

To add your own widget or content type:

1. Define a schema class (e.g. `YourContentSchema`) that implements the Capell admin schema contract.
2. Add a new case to `ContentSchemaEnum` (or `WidgetSchemaEnum`) and bind it to your schema in your service provider via `CapellAdmin::registerSchema(...)`.
3. Publish a Blade view under your package's view namespace matching the widget key.

For the full narrative walkthrough see [Extending Capell](../../../../capell-4/docs/extending-capell.md) in the main repo.

## Quick links

- Source directory: [`./src`](../src)
- Database reference: [Database.md](Database.md)
- Package README: [../README.md](../README.md)
