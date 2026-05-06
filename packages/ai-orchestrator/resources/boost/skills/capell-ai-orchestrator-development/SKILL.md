---
name: capell-ai-orchestrator-development
description: Use when editing Capell AIOrchestrator modules, providers, capabilities, or orchestration.
---

# Capell AIOrchestrator

AIOrchestrator module registry, provider contracts, capability execution, and LayoutBuilder planning integration.

## Look

- `packages/ai-orchestrator/src`
- `packages/ai-orchestrator/docs`
- `packages/ai-orchestrator/README.md`

## Rules

- Keep provider connectors behind contracts.
- Capability execution belongs in Actions, not UI glue.
- AIOrchestrator modules should expose previewable, bounded operations.
- Run `vendor/bin/pest packages/ai-orchestrator/tests`.
