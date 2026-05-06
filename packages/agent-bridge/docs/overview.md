# Agent Bridge

Status: **Available, schema-owning**

This page is the consolidated implementation overview for the Agent Bridge package. It is extracted from the package README, service providers, migrations, config files, routes, resources, models, actions, and the shared Capell ERD notes where available.

## What This Plugin Adds

Agent Bridge exposes Capell knowledge and site capabilities through Laravel Agent Bridge servers with token authentication, confirmations, previews, and audit records.

- Capell knowledge and site Agent Bridge servers.
- Token, confirmation, and audit models.
- Capability registry and capability actions.
- Prompt builder Filament page.
- Tools for knowledge lookup, package recommendation, site inspection, capability listing, confirmation, and execution.

## Developer Notes

Provides a typed capability contract so Agent Bridge tools can preview, confirm, run, and audit changes instead of directly mutating site state.

- AgentBridgeServiceProvider registers routes, servers, resources, and capabilities.
- Config file: capell-agent-bridge.php.
- Routes file registers Agent Bridge endpoints through Laravel Agent Bridge.
- Middleware: AuthenticateCapellAgentBridgeToken.
- Models: CapellAgentBridgeToken, CapellAgentBridgeConfirmation, CapellAgentBridgeAuditEntry.
- Servers: CapellKnowledgeServer and CapellSiteServer.
- Laravel Boost discovers Capell package guidance from installed package `resources/boost` directories.
- When Boost is installed, AgentBridgeServiceProvider appends Capell bridge tools to `boost.agent-bridge.tools.include`.
- Boost bridge tools list and preview registered capabilities; authenticated confirmation remains on the Capell Site Agent Bridge server.

## Operational Notes

Lets trusted ai-orchestrator clients inspect Capell and request controlled site operations with reviewable confirmation records.

- Adds Agent Bridge token, confirmation, and audit tables.
- Adds configurable Agent Bridge routes.
- Default config enables site route agent-bridge/capell and disables home/knowledge route registration.
- Adds prompt builder admin page.
- Adds token prefix and auth guard configuration.

## Data And Retention

- capell_agent-bridge_tokens stores Agent Bridge client tokens.
- capell_agent-bridge_confirmations stores pending or completed confirmations.
- capell_agent-bridge_audit_entries stores capability invocation records.
- Confirmation TTL defaults to 10 minutes.

## Screenshot Plan

- Agent Bridge prompt builder page.
- Token management or setup surface.
- Capability preview and confirmation flow.
- Audit entry review.
- Agent Bridge server health output.

## Pitfalls

- Enable only the Agent Bridge routes you intend to expose.
- Protect site capabilities with token auth and confirmation flow.
- Keep public_docs_paths scoped to documentation safe for Agent Bridge clients.
- Run migrations before creating tokens.
- Do not expect Boost to discover Capell Agent Bridge if the host app has not installed `capell-app/agent-bridge`.

## Verification

- Run `vendor/bin/pest packages/agent-bridge/tests` when package tests exist.
- Run the relevant host-app migration or package install flow in a disposable database.
- Open the listed admin or frontend surface and compare it with the screenshot plan.

## Package Manifest

- Composer name: No Composer manifest is present.
- Product group: Not declared.
- Kind: Not declared.
- Tier: Not declared.
- Bundle: Not declared.
- Contexts: Not declared.
- Requires: Not declared.
- Optional dependencies: None listed.

## Admin Surfaces

- CapellAgentBridgePromptBuilderPage (packages/agent-bridge/src/Filament/Pages/CapellAgentBridgePromptBuilderPage.php, slug `capell-agent-bridge/prompt-builder`)

## Commands

- None proven in this package directory.

## Routes And Config

- Config: packages/agent-bridge/config/capell-agent-bridge.php
- Route file: packages/agent-bridge/routes/agent-bridge.php

## Permissions And Gates

- None proven in this package directory.

## Migrations

- Migration: 2026_05_02_000001_create_capell_agent-bridge_tokens_table.php
- Migration: 2026_05_02_000002_create_capell_agent-bridge_confirmations_table.php
- Migration: 2026_05_02_000003_create_capell_agent-bridge_audit_entries_table.php

## ERD Excerpt

This package has no committed ERD excerpt. Use implementation notes and extension points instead of inventing schema.

## Screenshot Automation

Deployment should read [screenshots.json](screenshots.json), install the package with demo data, resolve each admin surface or frontend URL, and write images to `public/docs/screenshots/packages/agent-bridge`.

- Agent Bridge prompt builder page.
- Token management or setup surface.
- Capability preview and confirmation flow.
- Audit entry review.
- Agent Bridge server health output.
