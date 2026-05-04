# Workspaces

Status: **Available, schema-owning** · Kind: **package** · Tier: **premium** · Bundle: **publishing-pro** · Contexts: **admin, console** · Product group: **Capell Publishing Pro**

## What This Plugin Adds

Workspaces is Capell's flagship editorial timeline workflow. It gives content teams a premium, Statamic-style publishing experience for Capell: preview, compare, approve, schedule, publish, and rollback every meaningful content change without editing live records directly.

- Draft workspaces with copy-on-write editing for Draftable content.
- Signed live preview links and a frontend workspace preview banner.
- Compare and readiness views with diff, comments, review assignments, and publish checks.
- Approval history for approve, reject, and request-changes decisions.
- Scheduled publishing, immediate publishing, version history, rollback, and restore.
- Activity timeline widgets and pages for audit-friendly editorial history.

## Why It Matters

**For developers:** Adds copy-on-write, draftable model support, workspace events, review policies, preview signing, and page resource extenders without moving domain logic into Filament pages.

**For teams:** Gives editors the confidence of a content history product: they can see what changed, preview it safely, gather approval, schedule the release, publish when ready, and restore a previous version if production needs to move back.

## Screens And Workflow

Screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment.

- Editorial timeline dashboard.
- Live preview and preview banner.
- Compare and publish readiness panel.
- Approval history and reviewer decisions.
- Scheduled publishing queue.
- Activity history and field comments.
- Rollback and restore flow.

## Technical Shape

- WorkspacesServiceProvider, AdminServiceProvider, ConsoleServiceProvider register package surfaces.
- Routes include capell/preview/exit.
- Migrations create workspaces, versions, preview links, approvals, field comments, review assignments, and workspace columns on core/external tables.
- Events track state changes and version rollback.
- Publish checks include accessibility, broken links, missing alt text, and SEO meta.

## Data Model

- workspaces stores uuid, slug, status, base version, cloned-from workspace, submitted/approved/publish timestamps, and timeline status metadata.
- versions stores uuid, number, live flag, manifest, source workspace, and rollback links.
- preview_links, workspace_approvals, workspace_review_assignments, and workspace_field_comments support the preview, compare, approval, and activity workflow.
- Core tables receive workspace_id columns.

## Install Impact

- Adds workspace and versioning tables.
- Adds workspace_id columns to core and external tables.
- Adds admin resources/pages/widgets and frontend preview route.
- Adds middleware to resolve workspace context.
- Adds commands for install, load testing, and pruning abandoned workspaces.

## Commands

- `capell:workspaces-install` (packages/workspaces/src/Console/Commands/InstallCommand.php)
- `capell:workspaces:load-test {--workspaces=10 : Number of workspaces to create} {--rows-per-workspace=100 : Fixture rows per workspace} {--fresh : Truncate the fixture workspace tables first} {--publish= : Publish the first N workspaces after populating (defaults to 0)} {--force : Allow running outside local/testing environments}` (packages/workspaces/src/Console/Commands/LoadTestWorkspacesCommand.php)
- `capell:workspaces:prune {--id=* : Prune a specific workspace id instead of every abandoned workspace} {--dry-run : Report what would be pruned without making changes}` (packages/workspaces/src/Console/Commands/PruneAbandonedWorkspacesCommand.php)

## Admin And Access

- ActivityTrailPage (packages/workspaces/src/Filament/Pages/ActivityTrailPage.php, slug `reports/activity-trail`)
- ImportPagesPage (packages/workspaces/src/Filament/Pages/ImportPagesPage.php, slug `recovery-center/import-pages`)
- ScheduledPublishingPage (packages/workspaces/src/Filament/Pages/ScheduledPublishingPage.php, slug `scheduled-publishing`)
- StaleDraftsPage (packages/workspaces/src/Filament/Pages/StaleDraftsPage.php, slug `stale-drafts`)
- PageVersionHistoryPage (packages/workspaces/src/Filament/Resources/Pages/Pages/PageVersionHistoryPage.php, slug `{record}/history`)
- ManagePreviewLinks (packages/workspaces/src/Filament/Resources/PreviewLinks/Pages/ManagePreviewLinks.php)
- PreviewLinkResource (packages/workspaces/src/Filament/Resources/PreviewLinks/PreviewLinkResource.php)
- CompareVersionPage (packages/workspaces/src/Filament/Resources/Workspaces/Pages/CompareVersionPage.php, slug `{record}/compare`)
- ManageWorkspaces (packages/workspaces/src/Filament/Resources/Workspaces/Pages/ManageWorkspaces.php)
- WorkspaceResource (packages/workspaces/src/Filament/Resources/Workspaces/WorkspaceResource.php)

- Policy: WorkspacePolicy (packages/workspaces/src/Policies/WorkspacePolicy.php)
- Gate: ContentSchedulerOverviewWidget: `admin`, `super_admin`
- Gate: ImportPagesPage: Filament Shield page permissions
- Gate: ScheduledPublishingPage: Filament Shield page permissions
- Gate: StaleDraftsPage: Filament Shield page permissions
- Gate: WorkspaceActivityWidgetAbstract: `admin`, `super_admin`
- Gate: WorkspaceMergeHistoryWidgetAbstract: `super_admin`

## Common Pitfalls

- Models participating in draft/publish must implement Draftable and be registered.
- Run migrations in order before using copy-on-write.
- Publish checks can block publishing.
- Preview links need expiry and revocation review.
- Schedule release windows and embargo rules must match site operations.

## Quick Start

1. Install the package with `composer require capell-app/workspaces`.
2. Run the package migrations or the Capell package installer required by the host app.
3. Open the new admin surface or integration point and verify the result.

## Next Steps

- [docs/overview.md](docs/overview.md)
- [../seo-tools/README.md](../seo-tools/README.md)
- [../navigation/README.md](../navigation/README.md)
- [../filament-peek/README.md](../filament-peek/README.md)
