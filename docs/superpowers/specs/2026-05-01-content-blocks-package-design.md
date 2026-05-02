# Content Blocks Package Design

## Goal

Extract Mosaic sections into a fully optional `capell-app/content-blocks` package with namespace `Capell\ContentBlocks`, while preserving existing section functionality when Content Blocks and Mosaic are both installed.

## Architecture

Content Blocks owns reusable content block storage, admin management, rendering contracts, and its package manifest. It must not import Mosaic classes. Mosaic remains responsible for layouts and widgets, and integrates with Content Blocks only through a small bridge contract/registry that can be absent at runtime.

The extraction keeps the current section behavior available by moving the section model/resource/configurators/Livewire selector into Content Blocks and registering a Mosaic adapter from the Content Blocks package when Mosaic classes are present. Mosaic will no longer require sections to boot.

## Package Boundary

- New package path: `packages/content-blocks`.
- Composer package: `capell-app/content-blocks`.
- Namespace: `Capell\ContentBlocks`.
- Required dependencies: PHP, `capell-app/core`, `capell-app/admin`, `capell-app/frontend`.
- Optional integration: Mosaic bridge enabled only when `Capell\Mosaic\Providers\MosaicServiceProvider` or relevant Mosaic contracts/classes are available.
- No package should require Content Blocks unless it directly uses reusable content blocks.
- Content Blocks must not import `Capell\Mosaic\...`.
- Mosaic core code should not directly import `Capell\ContentBlocks\...`; integration should be registry/configuration based.

## Content Blocks Ownership

Move the current section concepts into Content Blocks:

- `ContentBlock` model backed by the `content_blocks` table.
- Section factory, observer, admin resource, pages, tables, relation managers, configurators, and Livewire asset table.
- Content block translations, media, related asset behavior, type defaults, and workspace registration.
- A package service provider that registers resources, configurators, page types/assets, model events, relationships, Livewire components, Blade views, translations, migrations, and package metadata.

## Mosaic Adapter

The adapter preserves both-installed behavior:

- Content Blocks registers its block asset with the same layout/widget asset capabilities Mosaic currently expects.
- Mosaic exposes an adapter/bridge registry for layout assets instead of hard-coding `Section::class`.
- Existing page/layout builder flows that currently list, attach, and render sections should continue to work with Content Blocks installed.
- Mosaic demos and type creators that create section-backed widgets become conditional bridge behavior or move to Content Blocks.
- If Content Blocks is not installed, Mosaic still boots and supports layouts/widgets without reusable section assets.

## Migration Strategy

Use a dedicated `content_blocks` table and `content_block` morph alias. Existing section data should be migrated explicitly by an upgrade step rather than keeping the old table as the package boundary.

## Tests

- Content Blocks package tests cover install registration, model behavior, Filament resource pages, configurators, asset selection, and rendering.
- Mosaic tests prove Mosaic boots without Content Blocks.
- Integration tests prove Mosaic plus Content Blocks keeps the old section asset workflow: create block, select block in layout builder, attach to widget assets, render the asset component.
- Arch tests enforce no `Capell\Mosaic\...` imports inside Content Blocks and no hard Content Blocks imports inside Mosaic core bridge points.
