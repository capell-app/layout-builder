# Content Blocks

Status: **Available, schema-owning** · Kind: **package** · Tier: **free** · Bundle: **foundation** · Contexts: **admin, frontend** · Product group: **Capell Foundation**

This page is the consolidated implementation overview for the Content Blocks package. It is extracted from the package README, service providers, migrations, config files, routes, resources, models, actions, and the shared Capell ERD notes where available.

## What This Plugin Adds

Content Blocks adds reusable content records that can be managed in Filament and rendered through Mosaic-style assets and configurators.

- Content block Filament resource.
- Reusable block creation, replication, and form mutation actions.
- Default, hero, testimonial, accordion, call to action, comparison, counter, divider, FAQ, features, logos, pricing, stats, table, tabs, team, and timeline block configurators.
- Registry-backed block definitions for admin discovery and future package registration.
- Asset relation manager support.
- Content select and repeater form components.

## Developer Notes

Gives packages a structured content model with typed configurators and asset relations rather than pushing shared content into page JSON.

- ContentBlocksServiceProvider registers the package.
- Migration creates content_blocks.
- Model: ContentBlock.
- Filament resource: ContentBlockResource.
- Actions create, replicate, and mutate content state.
- Mosaic support component handles content block assets.

## Operational Notes

Lets editors manage reusable pieces of content once and place them across structured websites.

- Adds content_blocks table.
- Adds content block admin resource.
- Adds Filament form components for choosing content blocks.
- No public route is registered by this package.

## Data And Retention

- content_blocks stores reusable content and metadata.
- Content block factories and type factories support tests and demos.
- Assets are managed through relation manager behaviour.
- Deletion behaviour for reused content should be verified before removing shared records.

## Future Optional Blocks

Advanced blocks should live in their own packages and register into the content block registry:

- `capell-block-before-after`
- `capell-block-code-snippet`
- `capell-block-map`
- `capell-block-video`
- `capell-block-speed-dial`
- `capell-block-parallax`
- `capell-block-document-list`
- `capell-block-media-gallery`
- `capell-block-posts`
- `capell-block-contact-form`

## Screenshot Plan

- Content blocks admin index.
- Create/edit content block form.
- Content block asset relation manager.
- Widget or page selector using a content block.

## Pitfalls

- Check where a content block is reused before deleting it.
- Keep configurator types aligned with registered content types.
- Run migrations before opening the resource.

## Verification

- Run `vendor/bin/pest packages/content-blocks/tests` when package tests exist.
- Run the relevant host-app migration or package install flow in a disposable database.
- Open the listed admin or frontend surface and compare it with the screenshot plan.

## Package Manifest

- Composer name: `capell-app/content-blocks`
- Product group: Capell Foundation
- Kind: package
- Tier: free
- Bundle: foundation
- Contexts: `admin`, `frontend`
- Requires: `capell-app/core`, `capell-app/admin`, `capell-app/frontend`
- Optional dependencies: None listed.

## Admin Surfaces

- ContentBlockResource (packages/content-blocks/src/Filament/Resources/ContentBlocks/ContentBlockResource.php)
- CreateContentBlock (packages/content-blocks/src/Filament/Resources/ContentBlocks/Pages/CreateContentBlock.php)
- EditContentBlock (packages/content-blocks/src/Filament/Resources/ContentBlocks/Pages/EditContentBlock.php)
- ListContentBlocks (packages/content-blocks/src/Filament/Resources/ContentBlocks/Pages/ListContentBlocks.php)

## Commands

- None proven in this package directory.

## Routes And Config

- None proven in this package directory.

## Permissions And Gates

- None proven in this package directory.

## Migrations

- Migration: create_content_blocks_table.php

## ERD Excerpt

This package has no committed ERD excerpt. Use implementation notes and extension points instead of inventing schema.

## Screenshot Automation

Deployment should read [screenshots.json](screenshots.json), install the package with demo data, resolve each admin surface or frontend URL, and write images to `public/docs/screenshots/packages/content-blocks`.

- Content blocks admin index.
- Create/edit content block form.
- Content block asset relation manager.
- Widget or page selector using a content block.
