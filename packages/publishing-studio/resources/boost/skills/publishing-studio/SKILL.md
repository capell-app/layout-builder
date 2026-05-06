---
name: publishing-studio
description: Use when editing Capell PublishingStudio drafts, approvals, previews, scheduling, or publishing.
---

# Capell PublishingStudio

Draft publishing-studio, approvals, preview links, scheduling, version history, rollback, and controlled publishing.

## Look

- `packages/publishing-studio/src`
- `packages/publishing-studio/docs`
- `packages/publishing-studio/README.md`

## Rules

- Draftable models must use registered morph maps and existing replication actions.
- Publishing, rollback, approval, and schedule changes belong in Actions.
- Preserve preview-link security and workspace isolation.
- Run `vendor/bin/pest packages/publishing-studio/tests`.
