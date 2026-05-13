# Agent Bridge

Agent Bridge exposes Capell knowledge and site capabilities through Laravel Agent Bridge servers with token authentication, confirmations, previews, and audit records.

## At A Glance

- Package: `capell-app/agent-bridge`
- Namespace: `Capell\AgentBridge\`
- Surfaces: Filament admin, HTTP, database
- Service providers: `packages/agent-bridge/src/Providers/AgentBridgeServiceProvider.php`
- Capell dependencies: `capell-app/admin`, `capell-app/core`
- Third-party dependencies: `laravel/framework`, `laravel/mcp`, `lorisleiva/laravel-actions`, `spatie/laravel-data`, `spatie/laravel-package-tools`

## What It Adds

Agent Bridge exposes Capell knowledge and site capabilities through Laravel Agent Bridge servers with token authentication, confirmations, previews, and audit records.

- Capell knowledge and site Agent Bridge servers.
- Token, confirmation, and audit models.
- Capability registry and capability actions.
- Prompt builder Filament page.
- Tools for knowledge lookup, package recommendation, site inspection, capability listing, confirmation, and execution.

## Why It Matters

**For developers:** Provides a typed capability contract so Agent Bridge tools can preview, confirm, run, and audit changes instead of directly mutating site state.

**For teams:** Lets trusted ai-orchestrator clients inspect Capell and request controlled site operations with reviewable confirmation records.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Admin](https://github.com/capell-app/admin)
- [Capell Core](https://github.com/capell-app/core)

**Open-source packages used here**

- [Laravel MCP](https://github.com/laravel/mcp) - Laravel MCP server primitives used by Agent Bridge to expose Capell capabilities to agent tooling.
- [Laravel Actions](https://github.com/lorisleiva/laravel-actions) - single-purpose action classes that keep package workflows out of controllers and Filament resources.
- [Spatie Laravel Data](https://github.com/spatie/laravel-data) - typed data objects for package boundaries, form state, settings, and structured results.
- [Spatie Laravel Package Tools](https://github.com/spatie/laravel-package-tools) - Laravel package bootstrapping for config, migrations, commands, translations, and service provider setup.

**Linked package previews**

[![Laravel MCP GitHub preview](https://opengraph.githubassets.com/capell-readme/laravel/mcp)](https://github.com/laravel/mcp)

[![Laravel Actions GitHub preview](https://opengraph.githubassets.com/capell-readme/lorisleiva/laravel-actions)](https://github.com/lorisleiva/laravel-actions)

[![Spatie Laravel Data GitHub preview](https://opengraph.githubassets.com/capell-readme/spatie/laravel-data)](https://github.com/spatie/laravel-data)

[![Spatie Laravel Package Tools GitHub preview](https://opengraph.githubassets.com/capell-readme/spatie/laravel-package-tools)](https://github.com/spatie/laravel-package-tools)

## Screens And Workflow

Screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment.

- Agent Bridge prompt builder page.
- Token management or setup surface.
- Capability preview and confirmation flow.
- Audit entry review.
- Agent Bridge server health output.

## Technical Shape

- AgentBridgeServiceProvider registers routes, servers, resources, and capabilities.
- Config file: capell-agent-bridge.php.
- Routes file registers Agent Bridge endpoints through Laravel Agent Bridge.
- Middleware: AuthenticateCapellAgentBridgeToken.
- Models: CapellAgentBridgeToken, CapellAgentBridgeConfirmation, CapellAgentBridgeAuditEntry.
- Servers: CapellKnowledgeServer and CapellSiteServer.

## Code Map

| Area      | Path                                  | Purpose                                                             |
| --------- | ------------------------------------- | ------------------------------------------------------------------- |
| Actions   | `packages/agent-bridge/src/Actions`   | Domain operations. Test these directly where possible.              |
| Data      | `packages/agent-bridge/src/Data`      | Structured payloads, form state, view models, and integration data. |
| Enums     | `packages/agent-bridge/src/Enums`     | Persisted states and Filament option values.                        |
| Models    | `packages/agent-bridge/src/Models`    | Eloquent records owned by the package.                              |
| Filament  | `packages/agent-bridge/src/Filament`  | Admin resources, pages, widgets, and settings UI.                   |
| HTTP      | `packages/agent-bridge/src/Http`      | Controllers, middleware, and request handling.                      |
| Providers | `packages/agent-bridge/src/Providers` | Registration, extension hooks, routes, migrations, and resources.   |
| Resources | `packages/agent-bridge/resources`     | Views, translations, assets, and package resources.                 |
| Routes    | `packages/agent-bridge/routes`        | Route files loaded by the service provider.                         |
| Config    | `packages/agent-bridge/config`        | Package configuration and publishable config.                       |
| Database  | `packages/agent-bridge/database`      | Migrations, seeders, and settings migrations.                       |
| Tests     | `packages/agent-bridge/tests`         | Package-level Pest coverage.                                        |

## Admin Surface

- Pages: `CapellAgentBridgePromptBuilderPage`.
- Settings: `AgentBridgeSettings`.

## Runtime Surface

- Routes: `packages/agent-bridge/routes/agent-bridge.php`.

## Data And Persistence

- capell_agent-bridge_tokens stores Agent Bridge client tokens.
- capell_agent-bridge_confirmations stores pending or completed confirmations.
- capell_agent-bridge_audit_entries stores capability invocation records.
- Confirmation TTL defaults to 10 minutes.

- Models: `CapellAgentBridgeAuditEntry`, `CapellAgentBridgeConfirmation`, `CapellAgentBridgeToken`.
- Migrations: `2026_05_10_190840_01_create_capell_agent-bridge_tokens_table.php`, `2026_05_10_190840_02_create_capell_agent-bridge_confirmations_table.php`, `2026_05_10_190840_03_create_capell_agent-bridge_audit_entries_table.php`.
- Config: `packages/agent-bridge/config/capell-agent-bridge.php`.
- Data objects live in `src/Data/`; use them for payloads, form state, and view models.

## Extension Points

- Contracts: `CapellAgentBridgeCapabilityAction`, `CapellAgentBridgeCapabilityProvider`.
- Register Capell extension points, routes, migrations, settings, render hooks, and resources from service providers.

## Install Impact

- Adds Agent Bridge token, confirmation, and audit tables.
- Adds configurable Agent Bridge routes.
- Default config enables site route agent-bridge/capell and disables home/knowledge route registration.
- Adds prompt builder admin page.
- Adds token prefix and auth guard configuration.

## Install And Setup

- Install with `composer require capell-app/agent-bridge` in the host Capell application.
- Run migrations through the host application package install flow.
- In this repository, verify package changes with `vendor/bin/pest`; do not use `php artisan`.

## Admin And Access

- CapellAgentBridgePromptBuilderPage (packages/agent-bridge/src/Filament/Pages/CapellAgentBridgePromptBuilderPage.php, slug `capell-agent-bridge/prompt-builder`)

- None proven in this package directory.

## Common Pitfalls

- Enable only the Agent Bridge routes you intend to expose.
- Protect site capabilities with token auth and confirmation flow.
- Keep public_docs_paths scoped to documentation safe for Agent Bridge clients.
- Run migrations before creating tokens.

## Docs

- [boost-integration.md](docs/boost-integration.md)
- [capabilities.md](docs/capabilities.md)
- [credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
- [overview.md](docs/overview.md)

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/agent-bridge/tests --configuration=phpunit.xml
```

## Maintenance Notes

- Put behaviour changes in `src/Actions/`; UI classes, commands, and controllers should call actions instead of owning domain logic.
- Use package `Data` classes at boundaries instead of passing anonymous arrays between layers.
- Use backed enums for persisted values and enum labels for Filament options.
