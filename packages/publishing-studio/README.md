# PublishingStudio

Status: **Available, schema-owning** · Kind: **package** · Tier: **premium** · Bundle: **publishing-pro** · Contexts: **admin, console** · Product group: **Capell Publishing Pro**

## What This Plugin Adds

PublishingStudio is Capell's flagship editorial timeline workflow. It gives content teams a premium, Statamic-style publishing experience for Capell: preview, compare, approve, schedule, publish, and rollback every meaningful content change without editing live records directly.

- Draft publishing-studio with copy-on-write editing for Draftable content.
- Signed live preview links, expiry/revocation management, access tracking, and a frontend workspace preview banner.
- Compare and readiness views with diff, dry-run validation, field comments, review assignments, URL-collision checks, stale workspace warnings, and publish checks.
- Release Workspaces for grouping coordinated content, navigation, SEO, media, layout, and package-owned draftable changes into one previewable, approvable, schedulable, atomic publish.
- Approval history for submit, approve, reject, and request-changes decisions, including reviewer notes and required approval levels.
- Scheduled publishing with release-window guards, unpublish dates, embargo windows, review reminders, immediate publishing, version history, rollback, and entity-level restore.
- Activity timeline widgets, stale draft management, import recovery screens, load-test fixtures, and prune commands for audit-friendly editorial operations.

## Why It Matters

**For developers:** Adds copy-on-write, draftable model support, workspace events, review policies, preview signing, and page resource extenders without moving domain logic into Filament pages.

**For teams:** Gives editors the confidence of a content history product: they can see what changed, preview it safely, gather approval, schedule the release, publish when ready, and restore a previous version if production needs to move back.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Admin](https://github.com/capell-app/admin)
- [Capell Migration Assistant](../migration-assistant/README.md)
- [Capell Core](https://github.com/capell-app/core)
- [Capell Navigation](../navigation/README.md)

**Open-source packages used here**

- [php-diff](https://github.com/jfcherng/php-diff) - diff generation that powers editorial comparisons and publishing review workflows.

**Linked package previews**

[![php-diff GitHub preview](https://opengraph.githubassets.com/capell-readme/jfcherng/php-diff)](https://github.com/jfcherng/php-diff)

## Screens And Workflow

Screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment.

- Editorial timeline dashboard.
- Live preview, preview link management, and preview banner.
- Compare, dry-run validation, and publish readiness panel.
- Approval history, reviewer assignments, and field comments.
- Scheduled publishing queue with embargo, unpublish, and review-reminder metadata.
- Stale drafts, recovery imports, activity history, and audit trail.
- Rollback, entity restore, and version history flow.

## Technical Shape

- PublishingStudioServiceProvider, AdminServiceProvider, ConsoleServiceProvider register package surfaces.
- Routes include capell/preview/exit.
- Migrations create publishing-studio, versions, preview links, approvals, field comments, review assignments, and workspace columns on core/external tables.
- Events track state changes and version rollback.
- Publish checks include accessibility, broken links, missing alt text, SEO meta, stale workspace state, URL collisions, and release-window rules.

## Data Model

- publishing-studio stores uuid, slug, status, base version, cloned-from workspace, submitted/approved/publish timestamps, and timeline status metadata.
- versions stores uuid, number, live flag, manifest, source workspace, and rollback links.
- preview_links, workspace_approvals, workspace_review_assignments, and workspace_field_comments support preview, compare, approval, comments, assignments, and activity history.
- Core tables receive workspace_id columns.

## Install Impact

- Adds workspace and versioning tables.
- Adds workspace_id columns to core and external tables.
- Adds admin resources/pages/widgets and frontend preview route.
- Adds middleware to resolve workspace context.
- Adds commands for install, load testing, and pruning abandoned publishing-studio.
- Adds recovery-center import screens for moving imported pages through validation, relation resolution, execution, and rollback reporting.

## Commands

- `capell:publishing-studio-install` (packages/publishing-studio/src/Console/Commands/InstallCommand.php)
- `capell:publishing-studio:load-test {--publishing-studio=10 : Number of publishing-studio to create} {--rows-per-workspace=100 : Fixture rows per workspace} {--fresh : Truncate the fixture workspace tables first} {--publish= : Publish the first N publishing-studio after populating (defaults to 0)} {--force : Allow running outside local/testing environments}` (packages/publishing-studio/src/Console/Commands/LoadTestPublishingStudioCommand.php)
- `capell:publishing-studio:prune {--id=* : Prune a specific workspace id instead of every abandoned workspace} {--dry-run : Report what would be pruned without making changes}` (packages/publishing-studio/src/Console/Commands/PruneAbandonedPublishingStudioCommand.php)

## Admin And Access

- ActivityTrailPage (packages/publishing-studio/src/Filament/Pages/ActivityTrailPage.php, slug `dashboard-dashboard_reports/activity-trail`)
- ImportPagesPage (packages/publishing-studio/src/Filament/Pages/ImportPagesPage.php, slug `recovery-center/import-pages`)
- ScheduledPublishingPage (packages/publishing-studio/src/Filament/Pages/ScheduledPublishingPage.php, slug `scheduled-publishing`)
- StaleDraftsPage (packages/publishing-studio/src/Filament/Pages/StaleDraftsPage.php, slug `stale-drafts`)
- PageVersionHistoryPage (packages/publishing-studio/src/Filament/Resources/Pages/Pages/PageVersionHistoryPage.php, slug `{record}/history`)
- ManagePreviewLinks (packages/publishing-studio/src/Filament/Resources/PreviewLinks/Pages/ManagePreviewLinks.php)
- PreviewLinkResource (packages/publishing-studio/src/Filament/Resources/PreviewLinks/PreviewLinkResource.php)
- CompareVersionPage (packages/publishing-studio/src/Filament/Resources/PublishingStudio/Pages/CompareVersionPage.php, slug `{record}/compare`)
- ManagePublishingStudio (packages/publishing-studio/src/Filament/Resources/PublishingStudio/Pages/ManagePublishingStudio.php)
- WorkspaceResource (packages/publishing-studio/src/Filament/Resources/PublishingStudio/WorkspaceResource.php)

- Policy: WorkspacePolicy (packages/publishing-studio/src/Policies/WorkspacePolicy.php)
- Gate: ContentSchedulerOverviewWidget: `admin`, `super_admin`
- Gate: ImportPagesPage: Filament Shield page permissions
- Gate: ScheduledPublishingPage: Filament Shield page permissions
- Gate: StaleDraftsPage: Filament Shield page permissions
- Gate: WorkspaceActivityWidgetAbstract: `admin`, `super_admin`
- Gate: WorkspaceMergeHistoryWidgetAbstract: `super_admin`

## Common Pitfalls

- Models participating in draft/publish must implement Draftable and be registered.
- Run migrations in order before using copy-on-write.
- Publish checks, stale workspace analysis, URL collisions, and release windows can block publishing.
- Preview links need expiry, revocation, and access-count review.
- Schedule release windows, unpublish dates, embargo rules, and review reminders must match site operations.
- Import recovery screens depend on the MigrationAssistant-backed import session tables when page import workflows are enabled.

## Quick Start

1. Install the package with `composer require capell-app/publishing-studio`.
2. Run the package migrations or the Capell package installer required by the host app.
3. Open the new admin surface or integration point and verify the result.

## Next Steps

- [docs/overview.md](docs/overview.md)
- [docs/release-workspaces.md](docs/release-workspaces.md)
- [../seo-suite/README.md](../seo-suite/README.md)
- [../navigation/README.md](../navigation/README.md)
- [docs/credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
