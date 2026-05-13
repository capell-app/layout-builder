# AI Orchestrator

AI Orchestrator coordinates AI providers, prompts, structured requests, and package integrations that need AI-assisted workflows.

## At A Glance

- Package: `capell-app/ai-orchestrator`
- Namespace: `Capell\AIOrchestrator\`
- Service providers: `packages/ai-orchestrator/src/Providers/AIOrchestratorServiceProvider.php`
- Capell dependencies: `capell-app/admin`, `capell-app/core`
- Third-party dependencies: `lorisleiva/laravel-actions`, `spatie/laravel-data`, `spatie/laravel-package-tools`

## What It Adds

AIOrchestrator provides the orchestration layer for Capell ai-orchestrator modules and capability execution.

- AIOrchestrator module registry.
- Contracts for modules and provider connectors.
- Actions for listing, registering, and running capabilities.
- core layout builder integration module for layout planning preview.

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
- core layout builder preview workflow when the integration is enabled.
- Approval state where a capability requires review.

## Technical Shape

- AIOrchestratorServiceProvider registers ai-orchestrator services.
- Contracts: AIOrchestratorModule and AIOrchestratorProviderConnector.
- Actions: ListAIOrchestratorCapabilitiesAction, RegisterAIOrchestratorModuleAction, RunAIOrchestratorCapabilityAction.
- Data objects describe capabilities and runs.
- Enums model approval level.

## Code Map

| Area      | Path                                     | Purpose                                                             |
| --------- | ---------------------------------------- | ------------------------------------------------------------------- |
| Actions   | `packages/ai-orchestrator/src/Actions`   | Domain operations. Test these directly where possible.              |
| Data      | `packages/ai-orchestrator/src/Data`      | Structured payloads, form state, view models, and integration data. |
| Enums     | `packages/ai-orchestrator/src/Enums`     | Persisted states and Filament option values.                        |
| Providers | `packages/ai-orchestrator/src/Providers` | Registration, extension hooks, routes, migrations, and resources.   |
| Resources | `packages/ai-orchestrator/resources`     | Views, translations, assets, and package resources.                 |
| Tests     | `packages/ai-orchestrator/tests`         | Package-level Pest coverage.                                        |

## Data And Persistence

- This package does not own database tables.
- State is passed through data objects and consuming package integrations.
- Persistence, if needed, belongs to the package that runs the capability.

- Data objects live in `src/Data/`; use them for payloads, form state, and view models.

## Extension Points

- Contracts: `AIOrchestratorModule`, `AIOrchestratorProviderConnector`.
- Register Capell extension points, routes, migrations, settings, render hooks, and resources from service providers.

## Install Impact

- Adds ai-orchestrator service bindings and module registry.
- No migrations.
- No routes in this package.
- No Filament resource is registered by this package alone.

## Install And Setup

- Install with `composer require capell-app/ai-orchestrator` in the host Capell application.
- In this repository, verify package changes with `vendor/bin/pest`; do not use `php artisan`.

## Admin And Access

- None proven in this package directory.

- None proven in this package directory.

## Common Pitfalls

- Install the package that supplies the ai-orchestrator surface before expecting UI.
- Treat capability output as reviewable draft data unless the consuming package proves otherwise.
- Provider connector configuration belongs to the consuming ai-orchestrator integration.

## Docs

- [credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
- [overview.md](docs/overview.md)

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/ai-orchestrator/tests --configuration=phpunit.xml
```

## Maintenance Notes

- Put behaviour changes in `src/Actions/`; UI classes, commands, and controllers should call actions instead of owning domain logic.
- Use package `Data` classes at boundaries instead of passing anonymous arrays between layers.
- Use backed enums for persisted values and enum labels for Filament options.
