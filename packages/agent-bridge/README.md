# Agent Bridge

Status: **Available, schema-owning**

## What This Plugin Adds

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

## Laravel Boost Integration

Capell Agent Bridge integrates with Laravel Boost when both packages are installed in the host app. Boost discovers lightweight package guidance from `vendor/capell-app/*/resources/boost/*`, while `capell-app/agent-bridge` registers bridge tools into `boost.agent-bridge.tools.include` so Boost can list and preview Capell Agent Bridge capabilities.

See [docs/boost-integration.md](docs/boost-integration.md) for host-app setup, `capell-ruby` verification, and the difference between Boost's local Agent Bridge server and Capell's authenticated Site Agent Bridge server.

## Data Model

- capell_agent-bridge_tokens stores Agent Bridge client tokens.
- capell_agent-bridge_confirmations stores pending or completed confirmations.
- capell_agent-bridge_audit_entries stores capability invocation records.
- Confirmation TTL defaults to 10 minutes.

## Install Impact

- Adds Agent Bridge token, confirmation, and audit tables.
- Adds configurable Agent Bridge routes.
- Default config enables site route agent-bridge/capell and disables home/knowledge route registration.
- Adds prompt builder admin page.
- Adds token prefix and auth guard configuration.

## Commands

- None proven in this package directory.

## Admin And Access

- CapellAgentBridgePromptBuilderPage (packages/agent-bridge/src/Filament/Pages/CapellAgentBridgePromptBuilderPage.php, slug `capell-agent-bridge/prompt-builder`)

- None proven in this package directory.

## Common Pitfalls

- Enable only the Agent Bridge routes you intend to expose.
- Protect site capabilities with token auth and confirmation flow.
- Keep public_docs_paths scoped to documentation safe for Agent Bridge clients.
- Run migrations before creating tokens.

## Quick Start

1. Install the package with `composer require capell-app/agent-bridge`.
2. Run the package migrations or the Capell package installer required by the host app.
3. Open the new admin surface or integration point and verify the result.

## Next Steps

- [docs/overview.md](docs/overview.md)
- [../ai-orchestrator/README.md](../ai-orchestrator/README.md)
- [../diagnostics/README.md](../diagnostics/README.md)
- [docs/credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
