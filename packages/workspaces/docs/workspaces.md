# Workspaces & Versions

Capell uses a **Workspace / Version** editorial model.

> 📖 For an end-to-end walkthrough of the **page creation and approval flow** — with a state diagram, the three user roles, and the email notifications sent at each transition — see [Page Creation & Approval Flow](page-creation-and-approval-flow.md).

---

## Concepts

- **Workspace** — a named, isolated sandbox. All edits that should ship together (a single page edit, or 40 pages + menus + settings for a coordinated relaunch) live in one workspace. Many workspaces can exist in parallel.
- **Version** — an immutable snapshot of the published manifest at a point in time. Exactly one version is `is_live = true`. Publishing a workspace creates a new version and flips it live.
- **Live** — whatever rows the current live version references, identified by `workspace_id = 0` on every draftable table.

A one-page edit is a workspace containing one draft record. A site-wide relaunch is a workspace containing hundreds. Same primitive, same machinery.

---

## The `workspace_id` sentinel

Every draftable table now has a `workspace_id BIGINT UNSIGNED NOT NULL DEFAULT 0` column.

- `workspace_id = 0` → the row is **live**.
- `workspace_id > 0` → the row is **draft**, scoped to that workspace.

Using `0` rather than `NULL` is deliberate: MySQL treats `NULL` as distinct in unique indexes, which would break composite keys like `page_urls(site_id, language_id, url)` where live and draft copies can legitimately share the same URL at the same time.

**Tables carrying `workspace_id`:**

Core: `pages`, `page_urls`, `navigations`, `sites`, `site_domains`, `types`, `layouts`, `languages`, `themes`, `translations`, `asset_relations`, `media`, `settings`.

Packages: `contents`, `widgets`, `widget_assets` (Layout); `articles`, `tags`, `taggables` (Blog).

Unique indexes on URL / slug / handle columns include `workspace_id` so a workspace can hold `/about` while live also holds `/about` without collision.

---

## BelongsToWorkspace trait

Draftable models use `Capell\Workspaces\BelongsToWorkspace`. It adds:

| API                                            | Behavior                                        |
| ---------------------------------------------- | ----------------------------------------------- |
| `$model->workspace()`                          | `BelongsTo<Workspace>` relation                 |
| `$query->live()`                               | filters to `workspace_id = 0`                   |
| `$query->inWorkspace($workspace)`              | filters to a specific workspace                 |
| `$query->forContext($workspace)`               | live + given workspace (or live only if `null`) |
| `$query->withoutWorkspaceScope()`              | escape the global scope                         |
| `$model->isLive()` / `$model->isInWorkspace()` | row-level ownership check                       |

The global `WorkspaceContextScope` unions live + active workspace rows when a workspace is set via `WorkspaceContext::set($workspace)`. When no workspace is active, queries see only live rows — existing code paths continue to return exactly what they did before.

---

## WorkspaceRegistry

Packages opt in from their `ServiceProvider::register()`:

```php
use Capell\Workspaces\WorkspaceRegistry;

WorkspaceRegistry::register(
    Widget::class,
    cloneUsing: fn (Widget $source, Workspace $workspace) => /* custom clone */,
    finalizeOnPublish: fn (Widget $row) => /* optional publish hook */,
);
```

The default `cloneUsing` replicates the row and stamps the workspace id. Custom callables are only needed when a model has relationships that must be cloned together (e.g. nested sets).

Registered models are what the `Publisher` flips and the `Rebaser` analyses.

---

## Admin surface

The Filament `WorkspaceResource` is the single editorial entry point:

- **List / edit page** — workspaces with status, approval counts, and per-user review assignments.
- **Actions** — `Submit for approval`, `Approve`, `Reject`, `Validate` (dry-run), `Publish`, `Schedule`, `Unschedule`, `Clone`, `Rollback`.
- **`WorkspaceSwitcher`** — header Livewire control that sets the active workspace on the session key `cms_workspace_id`. All admin queries then union live + active workspace rows automatically.
- **`WorkspaceContextBanner`** — persistent banner that reminds editors they are authoring in a workspace, with a one-click exit to live.
- **`CompareVersionPage`** — side-by-side diff page powered by `WorkspaceDiffService` (jfcherng/php-diff), surfaced via the `Compare` action. A diff-tree view supports field-level threaded comments.

## Preview

Preview is driven by `Capell\Workspaces\Http\Middleware\ResolveWorkspaceContext` (alias `workspace.context`):

1. Signed URL with `?__workspace=<uuid>` → drops a `cms_workspace` cookie (240 min, lax, http-only) so navigation stays in preview.
2. Existing `cms_workspace` cookie.
3. Session key `cms_workspace_id` (set by the admin switcher).
4. Null → live.

Signed preview links are minted through the Workspace admin with **per-link ACLs**: each link is an auditable token that can be revoked, expires, and is scoped to a specific workspace. While previewing, the frontend shows a preview pill and the HTML cache is bypassed.

## Dry-run, validation & diff

`Publisher::dryRun($workspace)` runs the same machinery as `publish()` inside a transaction that is rolled back at the end, returning a full report. `ValidateAction` surfaces the report in the admin — URL collisions, release-window status, failing publish checks, and the registered-model manifest that would ship.

`WorkspaceDiffService` compares a workspace against live (or against any historical `Version`) and drives both the `CompareVersionPage` and the diff-tree/comment UI.

## Publish pipeline

`Publisher::publish($workspace)` is an atomic flip guarded by:

1. **Freshness** — workspace must be `approved` and `base_version_id >= Version::liveId()` (otherwise `StaleWorkspaceException`).
2. **URL collisions** — any `(site_id, language_id, url)` tuple that would duplicate in live aborts publish (`UrlCollisionException`). `Publisher::detectUrlCollisions()` returns the list without throwing.
3. **`ReleaseWindowGuard`** — if a release window is configured and the current time is outside it, publish aborts with `ReleaseWindowClosedException`. A permission (`workspaces.bypass_release_window`) can override.
4. **Pluggable publish-check pipeline** — additional checks (`PublishCheck` implementations) are composed through `PublishCheckPipeline`, each returning a `PublishCheckResult` with a `PublishCheckSeverity`; any failing `blocking` check short-circuits the flip.
5. **Optimistic concurrency** — `SELECT … FOR UPDATE` on the live version row prevents two publishes racing.

Inside the transaction, each registered model's `finalizeOnPublish` callback runs, then `UPDATE … SET workspace_id = 0 WHERE workspace_id = $workspace->id` per table. A new `Version` row is created with a manifest of `{ModelClass => [ids]}` and flipped to `is_live = true`. The prior live version is demoted, workspace status moves to `published`, and a `WorkspaceStateChanged` event is dispatched.

## Scheduled publish & embargo

`SchedulePublishAction` stores a `publish_at` / `embargo_at` on the workspace and `PublishScheduledWorkspacesJob` (registered on the scheduler) fires due workspaces through the normal Publisher, honoring release windows and the publish-check pipeline.

## Rollback

Two levels of rollback ship:

- **Emergency rollback** (`Rollback` engine + `DryRunRollback`) — restores the previous live version by re-flipping `workspace_id` on the affected rows. Requires the `workspaces.rollback` permission. A dry-run variant reports what would change without writing.
- **Per-entity rollback** — `EntityRollbackAction` reverts individual draftable rows to an earlier workspace or version without touching the rest of the workspace, returning an `EntityRollbackReport`.

## Cloning

`CloneWorkspaceAction` creates a new workspace pre-populated with the source workspace's rows (via each model's `cloneUsing` callback). Useful for variant experiments or holding an in-review workspace while starting follow-up edits.

## Review assignments & policy resolver

Per-user review assignments on workspaces are backed by a policy resolver that surfaces the workspaces a user is expected to review, alongside the Filament actions they are allowed to take on each.

## Rebase flow

When another workspace publishes while yours is still open, yours becomes **stale** (`workspace->base_version_id < Version::liveId()`).

- `Rebaser::analyse($workspace)` returns a `RebaseReport` listing uuids whose live copy was updated after the workspace's `updated_at`.
- `Rebaser::resolve($workspace, $choices)` applies per-row resolution choices (keep-workspace / take-live / manual).
- `Rebaser::fastForward($workspace)` re-points `base_version_id` at the current live version once conflicts are resolved.

## Activity feed & audit

Every workspace action is stamped to the activity log with workspace scope. `WorkspaceActivityFeed` exposes a chronological feed of `WorkspaceActivityEntry`s for the admin, combining approvals, publish events, schedule changes, rollbacks, rebases, and per-field comments.

## Approval lifecycle

Workspace state machine (on `workspaces.status`):

```
open → in_review → approved → publishing → published
       ↓ reject → open
                  ↘ abandoned
```

- `submitForApproval($user, ?$notes)` → records a `Submitted` approval, flips to `in_review`.
- `approve($user, $level, ?$notes)` → records an `Approved` approval. When `$level >= required_approval_levels` (default 2, override via `settings.required_approval_levels`), status flips to `approved`.
- `reject($user, $level, $notes)` → records a `Rejected` approval, returns to `open`.
- `markAbandoned()` → workspace retired.

Every action is logged in the `workspace_approvals` table as an audit trail. State changes dispatch `WorkspaceStateChanged`, which routes mail notifications to role-targeted recipients.

## Operational commands

| Command                                    | Purpose                                                          |
| ------------------------------------------ | ---------------------------------------------------------------- |
| `capell:workspaces:prune`                  | Delete abandoned and old published workspaces past retention.    |
| `capell:workspaces:load-test`              | Publisher / Rebaser load-test harness used to validate indexing. |
| Scheduler: `PublishScheduledWorkspacesJob` | Publishes due scheduled workspaces each minute.                  |

Index tuning notes for the draftable tables live in [Workspaces Draftable Contract](workspaces-draftable-contract.md).

## Testing

Workspace machinery is exercised under `tests/src/Core/Integration/Workspaces/` and `tests/src/Core/Unit/Workspaces/`. A fixture model (`WorkspaceDraftableFixture`) backed by a per-test table isolates publisher / rebaser / scope tests from the real draftable models.

---

## Related files

| Concern                | File                                                                                                                  |
| ---------------------- | --------------------------------------------------------------------------------------------------------------------- |
| Workspace model        | `packages/core/src/Models/Workspace.php`                                                                              |
| Version model          | `packages/core/src/Models/Version.php`                                                                                |
| Approval audit         | `packages/core/src/Models/WorkspaceApproval.php`                                                                      |
| Registry               | `packages/core/src/Workspaces/WorkspaceRegistry.php`                                                                  |
| Trait + scope          | `packages/core/src/Workspaces/BelongsToWorkspace.php`, `WorkspaceContextScope.php`                                    |
| Context holder         | `packages/core/src/Workspaces/WorkspaceContext.php`                                                                   |
| Publisher              | `packages/core/src/Workspaces/Publisher.php`                                                                          |
| Rebaser                | `packages/core/src/Workspaces/Rebaser.php`, `RebaseReport.php`                                                        |
| Rollback               | `packages/core/src/Workspaces/Rollback.php`, `Rollback/EntityRollbackAction.php`, `DryRunRollback.php`                |
| Release windows        | `packages/core/src/Workspaces/ReleaseWindowGuard.php`                                                                 |
| Scheduled publish      | `packages/core/src/Workspaces/SchedulePublishAction.php`, `PublishScheduledWorkspacesJob.php`                         |
| Publish-check pipeline | `packages/core/src/Workspaces/Checks/{PublishCheckPipeline,PublishCheck,PublishCheckResult,PublishCheckSeverity}.php` |
| Diff service           | `packages/admin/src/Services/WorkspaceDiffService.php`                                                                |
| Activity feed          | `packages/core/src/Workspaces/Activity/{WorkspaceActivityFeed,WorkspaceActivityEntry}.php`                            |
| State-change event     | `packages/core/src/Workspaces/Events/WorkspaceStateChanged.php`                                                       |
| Clone                  | `packages/core/src/Workspaces/{CloneWorkspaceAction,CloneOptions}.php`                                                |
| Review policy          | `packages/core/src/Workspaces/Approvals/{RecordReviewDecisionAction,RequiredReviewer,ReviewPolicyResolver}.php`       |
| Preview middleware     | `packages/core/src/Http/Middleware/ResolveWorkspaceContext.php`                                                       |
| Admin resource         | `packages/admin/src/Filament/Resources/Workspaces/WorkspaceResource.php`                                              |
| Header switcher        | `packages/admin/src/Livewire/Header/WorkspaceSwitcher.php`                                                            |
| Status enum            | `packages/core/src/Enums/WorkspaceStatusEnum.php`                                                                     |
| Approval action enum   | `packages/core/src/Enums/WorkspaceApprovalActionEnum.php`                                                             |

## Single-page drafts

The `SinglePageDraft` workspace kind is used by the admin UI's "Save as Draft" flow when an editor chooses "Create a new draft for this page" on a live page edit. It's a regular workspace with these properties:

- `kind = WorkspaceKindEnum::SinglePageDraft`
- `status = WorkspaceStatusEnum::Open`
- `name` follows the pattern `Draft: :page_name · :Y-m-d H:i`

Cleanup: when the last draft row in a `SinglePageDraft` workspace is deleted via `DeletePageDraftAction`, the workspace itself is also deleted. Manual workspaces are never auto-deleted, even when empty.

See [Page Drafts & Publishing](page-drafts-and-publishing.md) for the editor-facing walk-through.
