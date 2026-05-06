---
name: capell-block-library-development
description: Use when editing reusable Capell Block Library or their LayoutBuilder assets.
---

# Capell Block Library

Reusable content records rendered through LayoutBuilder-style assets and configurators.

## Look

- `packages/block-library/src`
- `packages/block-library/docs`
- `packages/block-library/README.md`

## Rules

- Keep blocks reusable; avoid page-specific content logic.
- Put creation, replication, and form mutation behaviour in Actions.
- Treat asset relation managers as LayoutBuilder integration, not standalone rendering.
- Run `vendor/bin/pest packages/block-library/tests`.
