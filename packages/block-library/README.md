# Capell Content Blocks

Content Blocks is a reserved package namespace for shared block primitives that may be used by richer content-editing packages later.

At the moment this package is a skeleton. It is autoloaded as `Capell\ContentBlocks\`, but it does not ship migrations, config, Actions, commands, Filament resources, registries, or a package manifest yet. Treat it as an internal placeholder, not as an installable feature package.

## Current Surface

| Surface                 | Status                                                                         |
| ----------------------- | ------------------------------------------------------------------------------ |
| Namespace               | `Capell\ContentBlocks\`                                                        |
| Provider                | `Capell\ContentBlocks\Providers\ContentBlocksServiceProvider`, currently empty |
| Commands                | None                                                                           |
| Migrations              | None                                                                           |
| Config                  | None                                                                           |
| Actions                 | None                                                                           |
| Public extension points | None yet                                                                       |
| Tests                   | None yet                                                                       |

## Before Adding Features

When this package starts carrying real block behavior, document the first public surface in the same change:

- register any schemas, widgets, settings, render hooks, or admin extension points in this README;
- add a short copy-paste example for host packages that need to register a block;
- add package-level tests for the first behavior rather than relying on downstream packages;
- keep frontend output free of authoring metadata unless it is guarded behind the frontend authoring beacon.

The package should stay boring: small block DTOs, Actions for mutations, and explicit extension points rather than package-to-package reach-ins.
