# Content Sections Package Design

## Goal

Extract the existing LayoutBuilder Sections domain into a completely independent `capell-app/content-sections` package. The new package keeps the `Section` model and `sections` table, and treats sections as a first-class way of organising reusable structured content across Capell.

## Naming

- Package path: `packages/content-sections`.
- Composer package: `capell-app/content-sections`.
- Namespace: `Capell\ContentSections`.
- Service provider: `Capell\ContentSections\Providers\ContentSectionsServiceProvider`.
- Public domain name: Content Sections.

## Package Boundary

Content Sections must be completely independent from LayoutBuilder:

- Content Sections must not import `Capell\LayoutBuilder\...`.
- Content Sections must not require `capell-app/layout-builder` in Composer or `capell.json`.
- LayoutBuilder must not import `Capell\ContentSections\...`.
- Packages that directly need sections must depend on `capell-app/content-sections`.
- Optional package integrations must use Core/Admin/Frontend registries, events, morph aliases, or `class_exists` guarded registration.

Content Sections depends only on the shared packages needed to manage and render content:

- `capell-app/core`
- `capell-app/admin`
- `capell-app/frontend`

Publishing/workspace support is optional and guarded at runtime.

## Ownership

Move Section ownership out of LayoutBuilder into Content Sections:

- `Capell\ContentSections\Models\Section` backed by the existing `sections` table.
- Existing `section` morph alias.
- Section factory and observer.
- Section type registration.
- Section asset registration.
- Section Filament resource, pages, tables, widgets, relation managers, and schemas.
- Section configurators.
- Section form components and table columns that are section-specific.
- Section Livewire/admin selection UI when it can be expressed without LayoutBuilder coupling.
- Section Blade rendering components and translations.
- Section tests.

LayoutBuilder should retain only layout, widget, widget asset, and layout container ownership.

## LayoutBuilder Relationship

LayoutBuilder should become section-agnostic. It can continue to render and attach assets through generic asset/type registries, but it must not know the Section class exists.

The integration rule is:

- Content Sections registers `section` as a Core/Admin/Frontend asset.
- LayoutBuilder consumes registered assets generically.
- If Content Sections is installed, sections appear as attachable/renderable assets.
- If Content Sections is not installed, LayoutBuilder still boots and supports pages, layouts, widgets, and non-section assets.

This keeps Content Sections independent while preserving the existing workflow when both packages are installed.

## Data and Migration Strategy

This is an extraction, not a data rename:

- Keep the `sections` table.
- Keep the `section` morph alias.
- Keep the model name `Section`.
- Move the migration into `packages/content-sections/database/migrations/`.

Existing sites should not need a data migration just because the domain moved packages. Any install/upgrade logic should focus on package registration and migration ownership.

## Existing Block Library Work

## Package Consumers

Known consumers must be updated intentionally:

- Blog should depend on `capell-app/content-sections` if it registers tag relations or content workflows against sections.
- Shared test bootstrap should map the `section` morph alias to `Capell\ContentSections\Models\Section`.
- Uninstalled package tests should prove LayoutBuilder no longer exposes Section resources or models when Content Sections is absent.
- Cross-package boot tests should prove Content Sections and LayoutBuilder can coexist without direct imports.

## Testing

Add and preserve focused tests for:

- Content Sections package manifest, provider registration, and install state.
- `Section` model behavior after namespace move.
- Section Filament resource pages, tables, schemas, and relation managers.
- Section asset registration through Core/Admin/Frontend registries.
- Section rendering components.
- LayoutBuilder booting without Content Sections installed.
- LayoutBuilder plus Content Sections preserving the existing section asset workflow through generic asset registration.
- Architecture boundaries: no LayoutBuilder imports inside Content Sections, and no Content Sections imports inside LayoutBuilder.

## Success Criteria

- `Capell\LayoutBuilder\Models\Section` no longer exists as the owning model.
- `Capell\ContentSections\Models\Section` owns the existing `sections` table.
- LayoutBuilder has no direct Content Sections dependency.
- Content Sections has no direct LayoutBuilder dependency.
- Existing section data remains compatible.
- Section admin and rendering workflows still work when Content Sections is installed.
- LayoutBuilder boots and tests pass without Content Sections installed.
