# Capell Content Blocks

Content Blocks provides shared block primitives that richer content-editing packages can register and render without reaching into each other's internals.

It is intentionally small: a typed block definition DTO, a block registry, provider contracts, and actions for registering/listing/resolving blocks. It does not own migrations, admin resources, frontend output, or authoring markup.

## Current Surface

| Surface                 | Status                                                                                                |
| ----------------------- | ----------------------------------------------------------------------------------------------------- |
| Namespace               | `Capell\ContentBlocks\`                                                                               |
| Provider                | `Capell\ContentBlocks\Providers\ContentBlocksServiceProvider`                                         |
| Commands                | None                                                                                                  |
| Migrations              | None                                                                                                  |
| Config                  | None                                                                                                  |
| Actions                 | `ListBlockDefinitionsAction`, `RegisterBlockDefinitionProviderAction`, `ResolveBlockDefinitionAction` |
| Public extension points | `BlockDefinitionProvider::TAG`, `BlockRenderer`, `BlockRegistry`                                      |
| Tests                   | Package manifest, registry, provider registration, action resolution                                  |

## Registering Blocks

Packages register blocks by tagging a `BlockDefinitionProvider` implementation with `BlockDefinitionProvider::TAG`.

```php
use Capell\ContentBlocks\Contracts\BlockDefinitionProvider;
use Capell\ContentBlocks\Data\BlockDefinitionData;

final class MarketingBlockProvider implements BlockDefinitionProvider
{
    public function definitions(): iterable
    {
        yield new BlockDefinitionData(
            key: 'marketing.hero',
            label: 'Marketing hero',
            description: 'A campaign-ready hero block.',
            category: 'marketing',
            view: 'vendor-package::blocks.marketing-hero',
            defaults: ['alignment' => 'center'],
        );
    }
}
```

Block views must render ordinary public HTML. Authoring metadata, selectors, model IDs, signed URLs, and editor scripts belong behind the authenticated frontend authoring beacon, not in block definitions or public output.
