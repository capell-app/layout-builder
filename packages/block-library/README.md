# Block Library

Status: **Available, schema-owning** · Kind: **package** · Tier: **free** · Bundle: **foundation** · Contexts: **admin, frontend** · Product group: **Capell Foundation**

## What This Plugin Adds

Block Library adds reusable content records that can be managed in Filament and rendered through LayoutBuilder-style assets and configurators.

- Content block Filament resource.
- Reusable block creation, replication, and form mutation actions.
- Default, hero, testimonial, accordion, call to action, comparison, counter, divider, FAQ, features, logos, pricing, stats, table, tabs, team, and timeline block configurators.
- Registry-backed block definitions for admin discovery and future package registration.
- Asset relation manager support.
- Content select and repeater form components.

## Why It Matters

**For developers:** Gives packages a structured content model with typed configurators and asset relations rather than pushing shared content into page JSON.

**For teams:** Lets editors manage reusable pieces of content once and place them across structured websites.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Admin](https://github.com/capell-app/admin)
- [Capell Core](https://github.com/capell-app/core)
- [Capell Frontend](https://github.com/capell-app/frontend)

**Open-source packages used here**

- No extra third-party Composer package beyond the Capell package stack is required here.

## Screens And Workflow

Screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment.

- Content blocks admin index.
- Create/edit content block form.
- Content block asset relation manager.
- Widget or page selector using a content block.

## Technical Shape

- BlockLibraryServiceProvider registers the package.
- Migration creates block_library.
- Model: ContentBlock.
- Filament resource: ContentBlockResource.
- Actions create, replicate, and mutate content state.
- LayoutBuilder support component handles content block assets.
- Content block definitions are loaded from the default provider plus any package providers tagged with `ContentBlockDefinitionProvider::TAG`.

## Extending From Another Package

Other packages can add their own content blocks without changing this package. Create a provider that implements `ContentBlockDefinitionProvider`, return one or more `ContentBlockDefinitionData` objects, and tag that provider in the package service provider.

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

```php
use Capell\BlockLibrary\Contracts\ContentBlockDefinitionProvider;

public function register(): void
{
    $this->app->tag([
        VideoBlockDefinitionProvider::class,
    ], ContentBlockDefinitionProvider::TAG);
}
```

The package owns its configurator, views, translations, and any dependencies. Block Library discovers the tagged provider when the local app boots, registers the block definition, contributes the configurator to the admin surface, and uses the definition component when rendering frontend assets.

## Data Model

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

## Install Impact

- Adds block_library table.
- Adds content block admin resource.
- Adds Filament form components for choosing content blocks.
- No public route is registered by this package.

## Commands

- None proven in this package directory.

## Admin And Access

- ContentBlockResource (packages/block-library/src/Filament/Resources/BlockLibrary/ContentBlockResource.php)
- CreateContentBlock (packages/block-library/src/Filament/Resources/BlockLibrary/Pages/CreateContentBlock.php)
- EditContentBlock (packages/block-library/src/Filament/Resources/BlockLibrary/Pages/EditContentBlock.php)
- ListBlockLibrary (packages/block-library/src/Filament/Resources/BlockLibrary/Pages/ListBlockLibrary.php)

- None proven in this package directory.

## Common Pitfalls

- Check where a content block is reused before deleting it.
- Keep configurator types aligned with registered content types.
- Run migrations before opening the resource.

## Quick Start

1. Install the package with `composer require capell-app/block-library`.
2. Run the package migrations or the Capell package installer required by the host app.
3. Open the new admin surface or integration point and verify the result.

## Next Steps

- [docs/overview.md](docs/overview.md)
- [../layout-builder/README.md](../layout-builder/README.md)
- [docs/credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
