# Publishing Studio

PublishingStudio is Capell's flagship editorial timeline workflow. It gives content teams a premium, Statamic-style publishing experience for Capell: preview, compare, approve, schedule, publish, and rollback every meaningful content change without editing live records directly.

## At A Glance

- Package: `capell-app/publishing-studio`
- Namespace: `Capell\PublishingStudio\`
- Surfaces: Filament admin, Livewire, console, HTTP, database
- Service providers: `packages/publishing-studio/src/Providers/AdminServiceProvider.php`, `packages/publishing-studio/src/Providers/ConsoleServiceProvider.php`, `packages/publishing-studio/src/Providers/PublishingStudioServiceProvider.php`
- Capell dependencies: `capell-app/admin`, `capell-app/core`, `capell-app/html-cache`, `capell-app/migration-assistant`, `capell-app/navigation`
- Third-party dependencies: `jfcherng/php-diff`

## What It Adds

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

## Code Map

| Area      | Path                                       | Purpose                                                             |
| --------- | ------------------------------------------ | ------------------------------------------------------------------- |
| Actions   | `packages/publishing-studio/src/Actions`   | Domain operations. Test these directly where possible.              |
| Data      | `packages/publishing-studio/src/Data`      | Structured payloads, form state, view models, and integration data. |
| Enums     | `packages/publishing-studio/src/Enums`     | Persisted states and Filament option values.                        |
| Models    | `packages/publishing-studio/src/Models`    | Eloquent records owned by the package.                              |
| Filament  | `packages/publishing-studio/src/Filament`  | Admin resources, pages, widgets, and settings UI.                   |
| Livewire  | `packages/publishing-studio/src/Livewire`  | Interactive frontend or admin components.                           |
| HTTP      | `packages/publishing-studio/src/Http`      | Controllers, middleware, and request handling.                      |
| Providers | `packages/publishing-studio/src/Providers` | Registration, extension hooks, routes, migrations, and resources.   |
| Resources | `packages/publishing-studio/resources`     | Views, translations, assets, and package resources.                 |
| Routes    | `packages/publishing-studio/routes`        | Route files loaded by the service provider.                         |
| Database  | `packages/publishing-studio/database`      | Migrations, seeders, and settings migrations.                       |
| Tests     | `packages/publishing-studio/tests`         | Package-level Pest coverage.                                        |

## Admin Surface

- Resources: `PreviewLinkResource`, `WorkspaceResource`.
- Pages: `ActivityTrailPage`, `ActivityTrailTable`, `CompareVersionPage`, `DiscardDraftsBulkAction`, `ImportPagesPage`, `ManagePreviewLinks`, `ManagePublishingStudio`, `PageVersionHistoryPage`, `PublishPageAction`, `PublishingWorkflowPage`, `RequestReviewBulkAction`, `ResubmitForReviewAction`, `SaveAsDraftFormAction`, `ScheduledPublishingPage`, and related pages.
- Widgets: `ContentSchedulerCalendarWidget`, `ContentSchedulerOverviewWidget`, `PageAlertsWidget`, `WorkspaceActivityWidgetAbstract`, `WorkspaceMergeHistoryWidgetAbstract`.
- Settings: `PublishingStudioSettings`.

## Runtime Surface

- Livewire: `DiffPanel`, `FieldCommentThread`, `PageApprovalStatus`, `PublishStatusPanel`, `ReleaseWorkspaceSummaryPanel`, `WorkspaceApprovalHistory`, `WorkspaceContextBanner`, `WorkspaceSwitcher`.
- Controllers: `ExitWorkspacePreviewController`.
- Routes: `packages/publishing-studio/routes/web.php`.

## Commands

- `capell:publishing-studio-install` (packages/publishing-studio/src/Console/Commands/InstallCommand.php)
- `capell:publishing-studio:load-test {--publishing-studio=10 : Number of publishing-studio to create} {--rows-per-workspace=100 : Fixture rows per workspace} {--fresh : Truncate the fixture workspace tables first} {--publish= : Publish the first N publishing-studio after populating (defaults to 0)} {--force : Allow running outside local/testing environments}` (packages/publishing-studio/src/Console/Commands/LoadTestPublishingStudioCommand.php)
- `capell:publishing-studio:prune {--id=* : Prune a specific workspace id instead of every abandoned workspace} {--dry-run : Report what would be pruned without making changes}` (packages/publishing-studio/src/Console/Commands/PruneAbandonedPublishingStudioCommand.php)

## Data And Persistence

- publishing-studio stores uuid, slug, status, base version, cloned-from workspace, submitted/approved/publish timestamps, and timeline status metadata.
- versions stores uuid, number, live flag, manifest, source workspace, and rollback links.
- preview_links, workspace_approvals, workspace_review_assignments, and workspace_field_comments support preview, compare, approval, comments, assignments, and activity history.
- Core tables receive workspace_id columns.

- Models: `PreviewLink`, `Version`, `Workspace`, `WorkspaceApproval`, `WorkspaceFieldComment`, `WorkspaceReviewAssignment`.
- Migrations: `2026_05_10_190866_01_create_preview_links_table.php`, `2026_05_10_190866_02_create_publishing-studio_table.php`, `2026_05_10_190866_03_create_versions_table.php`, `2026_05_10_190866_04_create_workspace_approvals_table.php`, `2026_05_10_190866_05_create_workspace_field_comments_table.php`, `2026_05_10_190866_06_create_workspace_review_assignments_table.php`, `2026_05_10_190866_07_seed_bootstrap_workspace_version.php`, `2026_05_10_190866_08_z_add_workspace_columns_to_core_tables.php`, `2026_05_10_190866_09_z_add_workspace_id_to_external_tables.php`, `2026_05_10_190866_10_z_add_workspace_id_to_import_sessions_table.php`.
- Data objects live in `src/Data/`; use them for payloads, form state, and view models.

## Extension Points

- Contracts: `ReleaseWorkspaceItemContributor`, `WorkspaceTableActionContributor`.
- Events: `VersionRolledBack`, `WorkspaceEventDispatcher`, `WorkspaceEventSubscriber`, `WorkspaceStateChanged`.
- Listeners: `SendWorkspaceStateNotification`, `StampWorkspaceOnActivity`.
- Register Capell extension points, routes, migrations, settings, render hooks, and resources from service providers.

## Install Impact

- Adds workspace and versioning tables.
- Adds workspace_id columns to core and external tables.
- Adds admin resources/pages/widgets and frontend preview route.
- Adds middleware to resolve workspace context.
- Adds commands for install, load testing, and pruning abandoned publishing-studio.
- Adds recovery-center import screens for moving imported pages through validation, relation resolution, execution, and rollback reporting.

## Install And Setup

- Install with `composer require capell-app/publishing-studio` in the host Capell application.
- Run migrations through the host application package install flow.
- In this repository, verify package changes with `vendor/bin/pest`; do not use `php artisan`.

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

## Docs

- [credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
- [extending-publishing-studio.md](docs/extending-publishing-studio.md)
- [overview.md](docs/overview.md)
- [page-creation-and-approval-flow.md](docs/page-creation-and-approval-flow.md)
- [page-drafts-and-publishing.md](docs/page-drafts-and-publishing.md)
- [publishing-studio-draftable-contract.md](docs/publishing-studio-draftable-contract.md)
- [publishing-studio.md](docs/publishing-studio.md)
- [publishing-workflow.md](docs/publishing-workflow.md)
- [release-workspaces.md](docs/release-workspaces.md)

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/publishing-studio/tests --configuration=phpunit.xml
```

## Maintenance Notes

- Put behaviour changes in `src/Actions/`; UI classes, commands, and controllers should call actions instead of owning domain logic.
- Use package `Data` classes at boundaries instead of passing anonymous arrays between layers.
- Use backed enums for persisted values and enum labels for Filament options.
