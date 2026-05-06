# Block Library

Status: **Available, schema-owning** · Kind: **package** · Tier: **free** · Bundle: **foundation** · Contexts: **admin, frontend** · Product group: **Capell Foundation**

This page is the consolidated implementation overview for the Block Library package. It is extracted from the package README, service providers, migrations, config files, routes, resources, models, actions, and the shared Capell ERD notes where available.

## What This Plugin Adds

Block Library adds reusable content records that can be managed in Filament and rendered through LayoutBuilder-style assets and configurators.

- Content block Filament resource.
- Reusable block creation, replication, and form mutation actions.
- Default, hero, testimonial, accordion, call to action, comparison, counter, divider, FAQ, features, logos, pricing, stats, table, tabs, team, and timeline block configurators.
- Registry-backed block definitions for admin discovery and future package registration.
- Asset relation manager support.
- Content select and repeater form components.

## Developer Notes

Gives packages a structured content model with typed configurators and asset relations rather than pushing shared content into page JSON.

- BlockLibraryServiceProvider registers the package.
- Migration creates block_library.
- Model: ContentBlock.
- Filament resource: ContentBlockResource.
- Actions create, replicate, and mutate content state.
- LayoutBuilder support component handles content block assets.
- Content block definitions are resolved from the default provider plus any providers tagged with `ContentBlockDefinitionProvider::TAG`.

## Package Extension Point

Optional packages should register advanced blocks through `Capell\BlockLibrary\Contracts\ContentBlockDefinitionProvider`. The provider returns `ContentBlockDefinitionData` instances containing the key, labels, icon, configurator class, frontend component, defaults, group, and configurator type.

```php
use Capell\BlockLibrary\Contracts\ContentBlockDefinitionProvider;
use Capell\BlockLibrary\Data\ContentBlockDefinitionData;
use Filament\Support\Icons\Heroicon;

final class VideoBlockDefinitionProvider implements ContentBlockDefinitionProvider
{
    /**
     * @return iterable<ContentBlockDefinitionData>
     */
    public function definitions(): iterable
    {
        return [
            new ContentBlockDefinitionData(
                key: 'video',
                label: __('capell-video-block::block.video.label'),
                description: __('capell-video-block::block.video.description'),
                icon: Heroicon::OutlinedPlayCircle,
                group: 'media',
                configurator: VideoContentBlockConfigurator::class,
                component: 'capell-video-block::content-block.video',
            ),
        ];
    }
}
```

Tag the provider from the optional package service provider:

```php
use Capell\BlockLibrary\Contracts\ContentBlockDefinitionProvider;

public function register(): void
{
    $this->app->tag([
        VideoBlockDefinitionProvider::class,
    ], ContentBlockDefinitionProvider::TAG);
}
```

Laravel package discovery loads the optional package service provider in the local app. Once Block Library boots, it reads every tagged definition provider, registers those definitions with the registry, contributes their configurators to Filament, and resolves frontend rendering from the registry instead of a hard-coded match expression. The optional package remains responsible for its own configurator class, translations, Blade component, assets, settings, and external dependencies.

## Operational Notes

Lets editors manage reusable pieces of content once and place them across structured websites.

- Adds block_library table.
- Adds content block admin resource.
- Adds Filament form components for choosing content blocks.
- No public route is registered by this package.

## Data And Retention

- block_library stores reusable content and metadata.
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

- Run `vendor/bin/pest packages/block-library/tests` when package tests exist.
- Run the relevant host-app migration or package install flow in a disposable database.
- Open the listed admin or frontend surface and compare it with the screenshot plan.

## Package Manifest

- Composer name: `capell-app/block-library`
- Product group: Capell Foundation
- Kind: package
- Tier: free
- Bundle: foundation
- Contexts: `admin`, `frontend`
- Requires: `capell-app/core`, `capell-app/admin`, `capell-app/frontend`
- Optional dependencies: None listed.

## Admin Surfaces

- ContentBlockResource (packages/block-library/src/Filament/Resources/BlockLibrary/ContentBlockResource.php)
- CreateContentBlock (packages/block-library/src/Filament/Resources/BlockLibrary/Pages/CreateContentBlock.php)
- EditContentBlock (packages/block-library/src/Filament/Resources/BlockLibrary/Pages/EditContentBlock.php)
- ListBlockLibrary (packages/block-library/src/Filament/Resources/BlockLibrary/Pages/ListBlockLibrary.php)

## Commands

- None proven in this package directory.

## Routes And Config

- None proven in this package directory.

## Permissions And Gates

- None proven in this package directory.

## Migrations

- Migration: create_block_library_table.php

## ERD Excerpt

This package has no committed ERD excerpt. Use implementation notes and extension points instead of inventing schema.

## Screenshot Automation

Deployment should read [screenshots.json](screenshots.json), install the package with demo data, resolve each admin surface or frontend URL, and write images to `public/docs/screenshots/packages/block-library`.

- Content blocks admin index.
- Create/edit content block form.
- Content block asset relation manager.
- Widget or page selector using a content block.
