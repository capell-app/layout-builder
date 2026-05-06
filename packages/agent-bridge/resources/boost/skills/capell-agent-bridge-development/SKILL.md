---
name: capell-agent-bridge-development
description: Use when editing Capell Agent Bridge servers, tokens, capabilities, previews, or Boost bridge tools.
---

# Capell Agent Bridge

Agent Bridge servers and capability adapters with token auth, previews, confirmations, and audit records.

## Look

- `packages/agent-bridge/src`
- `packages/agent-bridge/docs/boost-integration.md`
- `packages/agent-bridge/README.md`

## Rules

- Register package operations through capability providers.
- Mutating site operations need preview, confirmation, scopes, and audit.
- Boost tools stay local-development bridges, not privileged bypasses.
- Run `vendor/bin/pest packages/agent-bridge/tests`.
