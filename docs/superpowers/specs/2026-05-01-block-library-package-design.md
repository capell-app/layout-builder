# Block Library Package Design

## Goal

Extract LayoutBuilder sections into a fully optional `capell-app/block-library` package with namespace `Capell\BlockLibrary`, while preserving existing section functionality when Block Library and LayoutBuilder are both installed.

## Architecture

Block Library owns reusable content block storage, admin management, rendering contracts, and its package manifest. It must not import LayoutBuilder classes. LayoutBuilder remains responsible for layouts and widgets, and integrates with Block Library only through a small bridge contract/registry that can be absent at runtime.

The extraction keeps the current section behavior available by moving the section model/resource/configurators/Livewire selector into Block Library and registering a LayoutBuilder adapter from the Block Library package when LayoutBuilder classes are present. LayoutBuilder will no longer require sections to boot.

## Package Boundary

- New package path: `packages/block-library`.
- Composer package: `capell-app/block-library`.
- Namespace: `Capell\BlockLibrary`.
- Required dependencies: PHP, `capell-app/core`, `capell-app/admin`, `capell-app/frontend`.
- Optional integration: LayoutBuilder bridge enabled only when `Capell\LayoutBuilder\Providers\LayoutBuilderServiceProvider` or relevant LayoutBuilder contracts/classes are available.
- No package should require Block Library unless it directly uses reusable content blocks.
- Block Library must not import `Capell\LayoutBuilder\...`.
- LayoutBuilder core code should not directly import `Capell\BlockLibrary\...`; integration should be registry/configuration based.

## Block Library Ownership

Move the current section concepts into Block Library:

- `ContentBlock` model backed by the `block_library` table.
- Section factory, observer, admin resource, pages, tables, relation managers, configurators, and Livewire asset table.
- Content block translations, media, related asset behavior, type defaults, and workspace registration.
- A package service provider that registers resources, configurators, page types/assets, model events, relationships, Livewire components, Blade views, translations, migrations, and package metadata.

## LayoutBuilder Adapter

The adapter preserves both-installed behavior:

- Block Library registers its block asset with the same layout/widget asset capabilities LayoutBuilder currently expects.
- LayoutBuilder exposes an adapter/bridge registry for layout assets instead of hard-coding `Section::class`.
- Existing page/layout builder flows that currently list, attach, and render sections should continue to work with Block Library installed.
- LayoutBuilder demos and type creators that create section-backed widgets become conditional bridge behavior or move to Block Library.
- If Block Library is not installed, LayoutBuilder still boots and supports layouts/widgets without reusable section assets.

## Migration Strategy

Use a dedicated `block_library` table and `content_block` morph alias. Existing section data should be migrated explicitly by an upgrade step rather than keeping the old table as the package boundary.

## Tests

- Block Library package tests cover install registration, model behavior, Filament resource pages, configurators, asset selection, and rendering.
- LayoutBuilder tests prove LayoutBuilder boots without Block Library.
- Integration tests prove LayoutBuilder plus Block Library keeps the old section asset workflow: create block, select block in layout builder, attach to widget assets, render the asset component.
- Arch tests enforce no `Capell\LayoutBuilder\...` imports inside Block Library and no hard Block Library imports inside LayoutBuilder core bridge points.
