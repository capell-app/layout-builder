# AIOrchestrator

Status: **Available, no schema impact** · Kind: **package** · Tier: **premium** · Bundle: **commercial** · Contexts: **admin** · Product group: **Capell Commercial**

This page is the consolidated implementation overview for the AIOrchestrator package. It is extracted from the package README, service providers, migrations, config files, routes, resources, models, actions, and the shared Capell ERD notes where available.

## What This Plugin Adds

AIOrchestrator provides the orchestration layer for Capell ai-orchestrator modules and capability execution.

- AIOrchestrator module registry.
- Contracts for modules and provider connectors.
- Actions for listing, registering, and running capabilities.
- LayoutBuilder integration module for layout planning preview.

## Developer Notes

Defines the module and capability contracts other packages can use without putting AI workflow logic into resources or controllers.

- AIOrchestratorServiceProvider registers ai-orchestrator services.
- Contracts: AIOrchestratorModule and AIOrchestratorProviderConnector.
- Actions: ListAIOrchestratorCapabilitiesAction, RegisterAIOrchestratorModuleAction, RunAIOrchestratorCapabilityAction.
- Data objects describe capabilities and runs.
- Enums model approval level.

## Operational Notes

Lets Capell installations add assisted workflows while keeping approvals and capability boundaries explicit.

- Adds ai-orchestrator service bindings and module registry.
- No migrations.
- No routes in this package.
- No Filament resource is registered by this package alone.

## Data And Retention

- This package does not own database tables.
- State is passed through data objects and consuming package integrations.
- Persistence, if needed, belongs to the package that runs the capability.

## Screenshot Plan

- Capability list or prompt surface where provided by a consuming package.
- LayoutBuilder layout preview workflow if LayoutBuilder integration is enabled.
- Approval state where a capability requires review.

## Pitfalls

- Install the package that supplies the ai-orchestrator surface before expecting UI.
- Treat capability output as reviewable draft data unless the consuming package proves otherwise.
- Provider connector configuration belongs to the consuming ai-orchestrator integration.

## Verification

- Run `vendor/bin/pest packages/ai-orchestrator/tests` when package tests exist.
- Run the relevant host-app migration or package install flow in a disposable database.
- Open the listed admin or frontend surface and compare it with the screenshot plan.

## Package Manifest

- Composer name: `capell-app/ai-orchestrator`
- Product group: Capell Commercial
- Kind: package
- Tier: premium
- Bundle: commercial
- Contexts: `admin`
- Requires: `capell-app/admin`
- Optional dependencies: None listed.

## Admin Surfaces

- None proven in this package directory.

## Commands

- None proven in this package directory.

## Routes And Config

- None proven in this package directory.

## Permissions And Gates

- None proven in this package directory.

## Migrations

- None proven in this package directory.

## ERD Excerpt

This package has no committed ERD excerpt. Use implementation notes and extension points instead of inventing schema.

## Screenshot Automation

Deployment should read [screenshots.json](screenshots.json), install the package with demo data, resolve each admin surface or frontend URL, and write images to `public/docs/screenshots/packages/ai-orchestrator`.

- Capability list or prompt surface where provided by a consuming package.
- LayoutBuilder layout preview workflow if LayoutBuilder integration is enabled.
- Approval state where a capability requires review.
