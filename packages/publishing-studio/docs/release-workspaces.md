# Release Workspaces

Release Workspaces are coordinated PublishingStudio workspaces for grouped editorial releases. They use the same copy-on-write, preview, compare, approval, schedule, publish, version, restore, and rollback mechanics as normal workspaces.

Use a Release Workspace when an editor needs page changes, navigation changes, SEO changes, media changes, layout changes, or package-owned draftable changes to move live together.

## What Release Workspaces Guarantee

- Draft rows stay workspace-scoped until publish.
- Preview uses the active workspace context.
- Readiness uses the existing dry-run publish pipeline.
- Approval uses the existing workspace approval history and review assignments.
- Scheduled release uses `publish_at`, embargo windows, unpublish dates, and release-window guards.
- Publish remains the same atomic workspace publish that creates a new live version.

## Package Integration

Packages should integrate in two layers:

1. Make publishable models Draftable with `BelongsToWorkspace`, `workspace_id`, and `shadowed_by_workspace_id`.
2. Register a `ReleaseWorkspaceItemContributor` when the package can provide richer item labels, statuses, or admin URLs.

The generic PublishingStudio contributor lists every registered draftable row. Package contributors should only add information that the generic layer cannot know, such as "SEO meta missing canonical" or "Navigation item moved under Products".
