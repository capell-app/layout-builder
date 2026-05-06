# AIOrchestrator

Status: **Available, no schema impact** · Kind: **package** · Tier: **premium** · Bundle: **commercial** · Contexts: **admin** · Product group: **Capell Commercial**

## What This Plugin Adds

AIOrchestrator provides the orchestration layer for Capell ai-orchestrator modules and capability execution.

- AIOrchestrator module registry.
- Contracts for modules and provider connectors.
- Actions for listing, registering, and running capabilities.
- LayoutBuilder integration module for layout planning preview.

## Why It Matters

**For developers:** Defines the module and capability contracts other packages can use without putting AI workflow logic into resources or controllers.

**For teams:** Lets Capell installations add assisted workflows while keeping approvals and capability boundaries explicit.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Admin](https://github.com/capell-app/admin)
- [Capell Core](https://github.com/capell-app/core)

**Open-source packages used here**

- [Laravel Actions](https://github.com/lorisleiva/laravel-actions) - single-purpose action classes that keep package workflows out of controllers and Filament resources.
- [Spatie Laravel Data](https://github.com/spatie/laravel-data) - typed data objects for package boundaries, form state, settings, and structured results.
- [Spatie Laravel Package Tools](https://github.com/spatie/laravel-package-tools) - Laravel package bootstrapping for config, migrations, commands, translations, and service provider setup.

**Linked package previews**

[![Laravel Actions GitHub preview](https://opengraph.githubassets.com/capell-readme/lorisleiva/laravel-actions)](https://github.com/lorisleiva/laravel-actions)

[![Spatie Laravel Data GitHub preview](https://opengraph.githubassets.com/capell-readme/spatie/laravel-data)](https://github.com/spatie/laravel-data)

[![Spatie Laravel Package Tools GitHub preview](https://opengraph.githubassets.com/capell-readme/spatie/laravel-package-tools)](https://github.com/spatie/laravel-package-tools)

## Screens And Workflow

Screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment.

- Capability list or prompt surface where provided by a consuming package.
- LayoutBuilder layout preview workflow if LayoutBuilder integration is enabled.
- Approval state where a capability requires review.

## Technical Shape

- AIOrchestratorServiceProvider registers ai-orchestrator services.
- Contracts: AIOrchestratorModule and AIOrchestratorProviderConnector.
- Actions: ListAIOrchestratorCapabilitiesAction, RegisterAIOrchestratorModuleAction, RunAIOrchestratorCapabilityAction.
- Data objects describe capabilities and runs.
- Enums model approval level.

## Data Model

- This package does not own database tables.
- State is passed through data objects and consuming package integrations.
- Persistence, if needed, belongs to the package that runs the capability.

## Install Impact

- Adds ai-orchestrator service bindings and module registry.
- No migrations.
- No routes in this package.
- No Filament resource is registered by this package alone.

## Commands

- None proven in this package directory.

## Admin And Access

- None proven in this package directory.

- None proven in this package directory.

## Common Pitfalls

- Install the package that supplies the ai-orchestrator surface before expecting UI.
- Treat capability output as reviewable draft data unless the consuming package proves otherwise.
- Provider connector configuration belongs to the consuming ai-orchestrator integration.

## Quick Start

1. Install the package with `composer require capell-app/ai-orchestrator`.
2. Register the package provider through Composer discovery and clear cached config if the host app uses config caching.
3. Open the new admin surface or integration point and verify the result.

## Next Steps

- [docs/overview.md](docs/overview.md)
- [../layout-builder/README.md](../layout-builder/README.md)
- [../agent-bridge/README.md](../agent-bridge/README.md)
- [docs/credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
