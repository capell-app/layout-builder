# Workspaces Publishing Workflow

This focused guide extends [Overview](overview.md) for the Workspaces package.

## Purpose

Workspaces controls Capell's editorial timeline for Draftable records: preview, compare, approve, schedule, publish, and rollback. It is the premium workflow layer for teams that want content history, readiness checks, and safe publishing without editing live records directly.

## Workflow

1. Create a draft workspace or page draft.
2. Preview the draft through signed preview links or the frontend preview banner.
3. Compare the draft against the live version and resolve field comments.
4. Request review and collect approval decisions.
5. Run readiness checks for accessibility, links, alt text, and SEO meta.
6. Publish immediately, schedule the release, or request changes.
7. Use version history, rollback, and restore when a published version needs to move back.

## Gates

- `submit_workspace`
- `approve_workspace`
- `publish_workspace`
- `rollback_workspace`
- `publish_outside_release_window`

## Draftable Contract

- Draft/publish models must implement the Capell Draftable contract.
- Models must be registered for workspace copy-on-write behaviour.
- Package integrations should use Workspaces actions instead of writing live rows directly.

## Screenshot Requirements

- Editorial timeline dashboard.
- Live preview and preview banner.
- Compare and publish readiness panel.
- Approval history and reviewer decisions.
- Scheduled publishing queue.
- Activity history and field comments.
- Rollback and restore flow.
