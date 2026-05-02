# Mosaic API Reference

This page is a map of the classes you're most likely to touch when working
with — or extending — the Mosaic package. For anything not listed here,
`packages/mosaic/src/` in the `capell-packages-4` repository is the source of
truth.

## Service provider

`Capell\Mosaic\Providers\MosaicServiceProvider` is the single entry point.
It registers the Filament resources, form components, Blade view namespaces,
commands, listeners, and runtime relationships, and publishes the admin and
frontend assets.

## Models

All three models are workspace-aware and ship a factory.

| Model                              | Table           | Notes                                                                              |
| ---------------------------------- | --------------- | ---------------------------------------------------------------------------------- |
| `Capell\Mosaic\Models\Section`     | `sections`      | Reusable, translatable content record. Organised as a nested set.                  |
| `Capell\Mosaic\Models\Widget`      | `widgets`       | A placed UI component instance — the thing editors drag onto a layout.             |
| `Capell\Mosaic\Models\WidgetAsset` | `widget_assets` | Polymorphic link between a widget and another record (usually media or a section). |

> Mosaic still exposes the `Page::contents()`, `Site::contents()`, and
> `Type::contents()` relationships by name. Under the hood they now point at
> the `Section` model — "content" is the legacy label, "section" is the
> current one.

## Filament resources

Under `src/Filament/Resources/`:

- `Sections/SectionResource` — Contents CRUD, plus relation managers for
  pages and widgets.
- `Widgets/WidgetResource` — Widgets CRUD, plus a relation manager for the
  layouts a widget appears on.
- `Layouts/LayoutResource` — the layout builder table configuration,
  layered on top of the core Admin layout resource.
- `Types/` — schema editors for content and widget types.
- `Pages/` — page schema extenders that wire the builder into the page
  edit screen.

## Form components

The drag-and-drop builder lives under `src/Filament/Components/Forms/`.
The big pieces are:

- The layout builder itself — the container + widget arrangement UI.
- Widget pickers and per-widget settings editors.
- Content selectors that let a widget reference one or more sections.
- Translation tabs for schemas that opt into multiple languages.

## Actions

Every piece of business logic sits in a single-purpose invokable under
`src/Actions/`.

| Action                                | Purpose                                                                                               |
| ------------------------------------- | ----------------------------------------------------------------------------------------------------- |
| `AddHeroWidgetToLayoutAction`         | Place the hero widget into the first row of a layout.                                                 |
| `AddWidgetToLayoutContainerAction`    | Place any widget in a named container at a given occurrence.                                          |
| `CreateContentAction`                 | Create a `Section` with its type and initial translations.                                            |
| `CreateHeroContentTypeAction`         | Register the hero section type during setup.                                                          |
| `CreateHeroWidgetAction`              | Register the hero widget type during setup.                                                           |
| `GetWidgetContainerWidthAction`       | Resolve the rendered width class for a widget based on its container.                                 |
| `HeroWidgetHasPrimaryHeadingAction`   | Check whether the hero widget provides the page's primary heading (so other schemas can defer to it). |
| `InstallPackageAction`                | Underlying work for `capell:mosaic-install`.                                                          |
| `ModifyContentSelectCreateAction`     | Customise the "create new" flow inside content selectors.                                             |
| `MutateContentDataBeforeFillAction`   | Transform content data before a Filament form fills.                                                  |
| `ReplicateContentAction`              | Deep-clone a section (used by the clone page / clone widget actions).                                 |
| `SaveFormComponentRelationshipAction` | Persist relationships from nested form components.                                                    |

## Enums

Mosaic uses enums as registries — a single place to look up schema keys,
component identifiers, and class names. They live under `src/Enums/`.

- `ContentSchemaEnum`, `ContentTypeEnum` — content type and schema keys.
- `WidgetSchemaEnum`, `WidgetTypeEnum`, `WidgetTypeGroupEnum` — widget type
  and schema keys.
- `LayoutContainerSchemaEnum`, `LayoutTypeEnum`, `LayoutWidgetSchemaEnum` —
  layout builder schemas.
- `TypeSchemaEnum`, `TypeEnum`, `SchemaExtenderEnum` — registry identifiers
  shared with the Admin package.
- `AssetComponentEnum`, `AssetEnum`, `WidgetAssetSchemaEnum`,
  `WidgetComponentEnum` — asset and widget component identifiers.
- `ActionLinkEnum`, `ComponentTypeEnum`, `LivewireComponentsEnum`,
  `ModelEnum`, `ResourceEnum`, `CapellLayoutCacheKeyEnum` — registry
  identifiers for the remaining moving parts (Livewire components, cache
  keys, model classes, etc.).

## Blade view namespaces

Mosaic publishes two Blade namespaces:

- `capell-mosaic::` — layout and widget templates. Highlights:
    - `capell-mosaic::layout.main` — top-level layout renderer.
    - `capell-mosaic::layout.container`, `capell-mosaic::layout.widget` —
      structural renderers.
    - `capell-mosaic::components.widget.*` — widget templates (asset,
      navigation, page/children, page/content, page/latest, page/siblings).
    - `capell-mosaic::components.asset.*` — section/asset templates
      (accordion, carousel, features, media, pages, blocks, banners,
      testimonials).
- `capell-hero::` — hero widget template. Third-party packages can publish
  their own namespace the same way.

## Listeners

Registered in `MosaicServiceProvider`:

- `AfterRecordSaved` — keeps widget and content caches coherent after any
  admin save.
- `LayoutLoaded` — hydrates the layout builder on edit.
- `LayoutSavingListener` — persists the widget graph when a layout saves.
- `SiteTreeRebuilt` — refreshes layout JSON when the site tree rebuilds.
- `TypeValidated` — updates type-driven references after a type edit.

## Commands

All under `src/Console/Commands/`:

- `InstallCommand` — `capell:mosaic-install`
- `SetupCommand` — `capell:mosaic-setup`
- `UpgradeCommand` — `capell:mosaic-upgrade`
- `DemoCommand` — `capell:mosaic-demo`
- `Hero\SetupCommand` — `capell:hero-setup`
- `Hero\DemoCommand` — `capell:hero-demo`

## Adding your own widget or content type

1. Write a schema class (e.g. `YourContentSchema`) that implements the
   Capell admin schema contract.
2. Add a case to `ContentSchemaEnum` or `WidgetSchemaEnum` and bind it to
   your schema in your service provider via
   `CapellAdmin::registerSchema(...)`.
3. Publish a Blade view under your package's view namespace that matches
   the widget or content key.

For the full walkthrough, see [Extending Capell](extending-capell.md).

## See also

- [Mosaic database reference](mosaic-database.md)
- [Packages & add-ons](packages.md#mosaic-builder)
- `packages/mosaic/README.md` in the `capell-packages-4` repository.
