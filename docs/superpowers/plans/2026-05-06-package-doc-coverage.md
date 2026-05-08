# Package Documentation Coverage Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add substantive package docs for every current Capell package that only has generated support docs.

**Architecture:** Treat `docs/overview.md` as the minimum public package doc. Add database and workflow docs only where the package owns tables, settings, sync flows, or demo assets that need more than an overview. Keep docs package-local and link them from the package README.

**Tech Stack:** Markdown package docs, Capell package metadata, Composer package metadata, local link verification with Node.

---

## Scope

The current audit found 36 package directories with `composer.json`. Four packages have no substantive package docs beyond `credits-and-acknowledgements.md`:

| Package             | Reason it is in scope                                                                             | Docs to create                                                                                                                                                        |
| ------------------- | ------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `dashboard-reports` | Only has credits docs.                                                                            | `packages/dashboard-reports/docs/overview.md`                                                                                                                         |
| `ga4-reports`       | Only has credits docs and owns metrics/sync tables.                                               | `packages/ga4-reports/docs/overview.md`, `packages/ga4-reports/docs/ga4-reports-database.md`, `packages/ga4-reports/docs/sync-workflow.md`                            |
| `password-policy`   | Only has credits docs and owns settings, user columns, password history, and enforcement actions. | `packages/password-policy/docs/overview.md`, `packages/password-policy/docs/password-policy-database.md`, `packages/password-policy/docs/settings-and-enforcement.md` |
| `demo-kit`          | Only has credits docs and owns demo assets/content provider behaviour.                            | `packages/demo-kit/docs/overview.md`, `packages/demo-kit/docs/demo-content.md`                                                                                        |

## File Structure

- Modify: `docs/internal/package-doc-gaps.md`
    - Keep the live audit list and point at this plan.
- Create: `packages/dashboard-reports/docs/overview.md`
    - Explain purpose, admin surface, provider shape, data ownership, install impact, and maintenance notes.
- Create: `packages/ga4-reports/docs/overview.md`
    - Explain GA4 reporting role, actions, settings, data client boundary, and dashboard outputs.
- Create: `packages/ga4-reports/docs/ga4-reports-database.md`
    - Document metrics tables, sync runs, settings migration, and retention considerations.
- Create: `packages/ga4-reports/docs/sync-workflow.md`
    - Document config resolution, client contract, sync action sequence, failure handling, and testing with the fake client.
- Create: `packages/password-policy/docs/overview.md`
    - Explain password expiry, forced change, compromised-password validation, password history, and disabled-by-default behaviour.
- Create: `packages/password-policy/docs/password-policy-database.md`
    - Document user columns, password history table, settings migration, and privacy/security notes.
- Create: `packages/password-policy/docs/settings-and-enforcement.md`
    - Document settings resolver, evaluation action, update action, and enforcement behaviour.
- Create: `packages/demo-kit/docs/overview.md`
    - Explain the package role, provider, demo use case, and install impact.
- Create: `packages/demo-kit/docs/demo-content.md`
    - Document demo media folders, sample video, content intent, and asset maintenance rules.
- Modify: `packages/dashboard-reports/README.md`
    - Add `docs/overview.md` under Package Docs.
- Modify: `packages/ga4-reports/README.md`
    - Add the three new docs under Package Docs.
- Modify: `packages/password-policy/README.md`
    - Add the three new docs under Package Docs.
- Modify: `packages/demo-kit/README.md`
    - Add the two new docs under Package Docs.

### Task 1: Dashboard Reports Overview

**Files:**

- Create: `packages/dashboard-reports/docs/overview.md`
- Modify: `packages/dashboard-reports/README.md`

- [ ] **Step 1: Create the overview doc**

Write `packages/dashboard-reports/docs/overview.md` with this structure:

```markdown
# Dashboard Reports Overview

Dashboard Reports provides shared reporting widget foundations for Capell operations dashboards.

## What It Adds

- Package and admin service providers for registering dashboard reporting surfaces.
- A place for generic dashboard report widgets that are not owned by a narrower feature package.
- Shared conventions for future operations reporting packages.

## Architecture

- `DashboardReportsServiceProvider` registers the package.
- `AdminServiceProvider` owns admin-facing registration.
- Reporting behaviour should stay in actions and data objects as the package grows.

## Data Ownership

This package does not currently own migrations or settings. It should stay focused on dashboard presentation and reusable reporting query shapes until a concrete report needs package-owned storage.

## Install Impact

- Adds operations reporting capabilities to the admin context.
- Does not add frontend routes.
- Does not add package-owned database tables.

## Maintenance Notes

When new dashboard widgets are added, document the source query, cache policy, permissions, and refresh expectations in this file or a focused companion doc.
```

- [ ] **Step 2: Link the overview from the README**

Add this line under `## Package Docs` in `packages/dashboard-reports/README.md`:

```markdown
- [docs/overview.md](docs/overview.md)
```

- [ ] **Step 3: Verify Dashboard Reports docs**

Run:

```bash
test -f packages/dashboard-reports/docs/overview.md
rg -n "docs/overview.md" packages/dashboard-reports/README.md
```

Expected: both commands exit with code 0.

### Task 2: GA4 Reports Docs

**Files:**

- Create: `packages/ga4-reports/docs/overview.md`
- Create: `packages/ga4-reports/docs/ga4-reports-database.md`
- Create: `packages/ga4-reports/docs/sync-workflow.md`
- Modify: `packages/ga4-reports/README.md`

- [ ] **Step 1: Create the overview doc**

Write `packages/ga4-reports/docs/overview.md` with this structure:

```markdown
# GA4 Reports Overview

GA4 Reports brings Google Analytics 4 reporting data into Capell dashboards.

## What It Adds

- Settings for GA4 property and credential configuration.
- Sync run records for tracking import attempts and failures.
- Daily metric and page metric models for dashboard reporting.
- Actions for overview cards, trends, top pages, config resolution, and metric persistence.

## Architecture

- `GA4ReportsServiceProvider` registers package services.
- `AdminServiceProvider` owns admin registration.
- `GA4ReportsDataClientInterface` is the boundary for talking to GA4.
- `FakeGA4ReportsDataClient` supports package tests without reaching Google.

## Admin And Console Use

The package is intended for admin dashboards and console/scheduled sync flows. Dashboard actions should read local metric tables rather than calling GA4 directly during page render.

## Install Impact

- Adds GA4 settings.
- Adds metric and sync-run tables.
- Requires credentials before real syncs can run.
```

- [ ] **Step 2: Create the database doc**

Write `packages/ga4-reports/docs/ga4-reports-database.md` with this structure:

```markdown
# GA4 Reports Database

GA4 Reports stores synced analytics data locally so dashboards can render consistently.

## Tables

- `ga4_reports_daily_metrics`: day-level metric snapshots.
- `ga4_reports_page_metrics`: page-level metric snapshots.
- `ga4_reports_sync_runs`: sync attempts, status, timing, and failure details.

## Settings

`create_ga4_reports_settings.php` creates package settings used by `GA4ReportsSettings` and `ResolveGA4ReportsConfigAction`.

## Write Path

- `PersistGA4ReportsDailyMetricAction` writes daily metric rows.
- `PersistGA4ReportsPageMetricAction` writes page metric rows.
- `SyncGA4ReportsMetricsAction` records sync state through sync-run data.

## Maintenance Notes

Keep indexes aligned with dashboard query windows. When a new dashboard range is added, check whether the metric tables need a matching index or retention note.
```

- [ ] **Step 3: Create the sync workflow doc**

Write `packages/ga4-reports/docs/sync-workflow.md` with this structure:

```markdown
# GA4 Reports Sync Workflow

The sync workflow turns GA4 API responses into local metric records.

## Flow

1. `ResolveGA4ReportsConfigAction` reads package settings and validates required GA4 configuration.
2. `GA4ReportsDataClientInterface` fetches metric data from the configured provider.
3. `SyncGA4ReportsMetricsAction` coordinates the sync window.
4. Persistence actions write daily and page metric rows.
5. Sync-run state records success or failure for admin review.

## Failure Handling

`GA4ReportsApiException` should be used for provider/API failures. Failed syncs should leave enough sync-run state for an operator to see what happened.

## Testing

Use `FakeGA4ReportsDataClient` in package tests. Tests should cover config resolution, sync result data, persistence actions, and dashboard action output from local rows.
```

- [ ] **Step 4: Link the GA4 docs from the README**

Add these lines under `## Package Docs` in `packages/ga4-reports/README.md`:

```markdown
- [docs/overview.md](docs/overview.md)
- [docs/ga4-reports-database.md](docs/ga4-reports-database.md)
- [docs/sync-workflow.md](docs/sync-workflow.md)
```

- [ ] **Step 5: Verify GA4 docs**

Run:

```bash
test -f packages/ga4-reports/docs/overview.md
test -f packages/ga4-reports/docs/ga4-reports-database.md
test -f packages/ga4-reports/docs/sync-workflow.md
rg -n "docs/(overview|ga4-reports-database|sync-workflow)\\.md" packages/ga4-reports/README.md
```

Expected: all commands exit with code 0.

### Task 3: Password Policy Docs

**Files:**

- Create: `packages/password-policy/docs/overview.md`
- Create: `packages/password-policy/docs/password-policy-database.md`
- Create: `packages/password-policy/docs/settings-and-enforcement.md`
- Modify: `packages/password-policy/README.md`

- [ ] **Step 1: Create the overview doc**

Write `packages/password-policy/docs/overview.md` with this structure:

```markdown
# Password Policy Overview

Password Policy adds opt-in password safety rules for Capell admin users.

## What It Adds

- Password expiry after a configured number of days.
- Forced password change markers for individual users.
- Optional compromised-password validation through Laravel password rules.
- Optional password history checks to prevent recent reuse.
- Package settings for enabling enforcement features.

## Defaults

All enforcement features are disabled by default. Teams must enable the policy settings before the package starts blocking password changes or requiring password rotation.

## Architecture

- `PasswordPolicySettingsResolver` resolves effective policy settings.
- `EvaluatePasswordPolicyAction` reports whether a user needs action.
- `ValidatePasswordChangeAction` validates proposed changes.
- `UpdatePasswordAction` updates the password and records history.
- `MarkUserForPasswordChangeAction` marks a user for reset on next login.
```

- [ ] **Step 2: Create the database doc**

Write `packages/password-policy/docs/password-policy-database.md` with this structure:

```markdown
# Password Policy Database

Password Policy stores only the state needed to enforce configured password rules.

## Schema Changes

- `2026_05_05_000001_add_password_policy_columns_to_users_table.php` adds password policy state to users.
- `2026_05_05_000002_create_password_policy_password_histories_table.php` stores password history records.
- `create_password_policy_settings.php` creates package settings.

## Data Notes

Password history must store hashes only. Do not store plain-text passwords or reversible password material.

## Maintenance Notes

When enforcement rules change, update this file with the affected columns, settings, and cleanup expectations. If history retention is added, document the pruning rule here.
```

- [ ] **Step 3: Create the settings and enforcement doc**

Write `packages/password-policy/docs/settings-and-enforcement.md` with this structure:

```markdown
# Password Policy Settings And Enforcement

Password Policy enforcement is driven by package settings and action classes.

## Settings

`PasswordPolicySettings` controls expiry, forced changes, compromised-password validation, and password history behaviour.

## Enforcement Flow

1. Resolve settings through `PasswordPolicySettingsResolver`.
2. Evaluate user state with `EvaluatePasswordPolicyAction`.
3. Validate a proposed password with `ValidatePasswordChangeAction`.
4. Update the password with `UpdatePasswordAction`.
5. Record the new password hash through `RecordPasswordHistoryAction`.

## Admin Operations

Use `MarkUserForPasswordChangeAction` when an operator needs a specific user to change their password at next login.
```

- [ ] **Step 4: Link the Password Policy docs from the README**

Add these lines under `## Package Docs` in `packages/password-policy/README.md`:

```markdown
- [docs/overview.md](docs/overview.md)
- [docs/password-policy-database.md](docs/password-policy-database.md)
- [docs/settings-and-enforcement.md](docs/settings-and-enforcement.md)
```

- [ ] **Step 5: Verify Password Policy docs**

Run:

```bash
test -f packages/password-policy/docs/overview.md
test -f packages/password-policy/docs/password-policy-database.md
test -f packages/password-policy/docs/settings-and-enforcement.md
rg -n "docs/(overview|password-policy-database|settings-and-enforcement)\\.md" packages/password-policy/README.md
```

Expected: all commands exit with code 0.

### Task 4: Demo Kit Docs

**Files:**

- Create: `packages/demo-kit/docs/overview.md`
- Create: `packages/demo-kit/docs/demo-content.md`
- Modify: `packages/demo-kit/README.md`

- [ ] **Step 1: Create the overview doc**

Write `packages/demo-kit/docs/overview.md` with this structure:

```markdown
# Demo Kit Overview

Demo Kit provides example content and media for validating a Capell install.

## What It Adds

- Demo image and video assets.
- A package provider for starter content registration.
- Example content intended for local demos, QA, and product walkthroughs.

## Architecture

- `DemoKitServiceProvider` registers the package.
- Demo assets live in `demo/img` and `demo/video`.
- The package depends on Capell Core, Admin, and Frontend because demo content needs to exercise both admin setup and public rendering.

## Install Impact

- Adds demo assets to the package.
- Does not add package-owned migrations.
- Should not be treated as production content.
```

- [ ] **Step 2: Create the demo content doc**

Write `packages/demo-kit/docs/demo-content.md` with this structure:

```markdown
# Demo Kit Demo Content

Demo Kit demo assets help teams see a working Capell site without starting from an empty admin panel.

## Asset Groups

- `demo/img`: sample images used by starter content.
- `demo/video`: sample video used for media and frontend checks.

## Maintenance Rules

- Keep demo assets small enough for the package repository.
- Prefer clear, reusable assets over one-off screenshots.
- Remove unused demo assets when starter content changes.
- Keep filenames stable when docs, seeds, or screenshots refer to them.

## QA Use

Use the package to confirm that media paths, frontend rendering, and admin package registration work after install.
```

- [ ] **Step 3: Link the Demo Kit docs from the README**

Add these lines under `## Package Docs` in `packages/demo-kit/README.md`:

```markdown
- [docs/overview.md](docs/overview.md)
- [docs/demo-content.md](docs/demo-content.md)
```

- [ ] **Step 4: Verify Demo Kit docs**

Run:

```bash
test -f packages/demo-kit/docs/overview.md
test -f packages/demo-kit/docs/demo-content.md
rg -n "docs/(overview|demo-content)\\.md" packages/demo-kit/README.md
```

Expected: all commands exit with code 0.

### Task 5: Coverage Verification

**Files:**

- Read: `packages/*/composer.json`
- Read: `packages/*/docs/*.md`
- Read: `packages/*/README.md`

- [ ] **Step 1: Verify every package has substantive docs**

Run:

```bash
node <<'NODE'
const fs = require('fs');
const path = require('path');
const missing = [];
for (const packageSlug of fs.readdirSync('packages').sort()) {
  if (!fs.existsSync(`packages/${packageSlug}/composer.json`)) continue;
  const docsDir = `packages/${packageSlug}/docs`;
  const files = fs.existsSync(docsDir) ? fs.readdirSync(docsDir) : [];
  const substantive = files.filter((file) => file.endsWith('.md') && !['credits-and-acknowledgements.md'].includes(file));
  if (substantive.length === 0) missing.push(packageSlug);
}
console.log(JSON.stringify({ missing }, null, 2));
if (missing.length > 0) process.exit(1);
NODE
```

Expected output:

```json
{
    "missing": []
}
```

- [ ] **Step 2: Verify package README docs links**

Run:

```bash
node <<'NODE'
const fs = require('fs');
const path = require('path');
const missingLinks = [];
for (const packageSlug of fs.readdirSync('packages').sort()) {
  const readmePath = `packages/${packageSlug}/README.md`;
  if (!fs.existsSync(`packages/${packageSlug}/composer.json`) || !fs.existsSync(readmePath)) continue;
  const readme = fs.readFileSync(readmePath, 'utf8');
  const docsDir = `packages/${packageSlug}/docs`;
  const docs = fs.readdirSync(docsDir).filter((file) => file.endsWith('.md') && file !== 'credits-and-acknowledgements.md');
  for (const doc of docs) {
    const link = `docs/${doc}`;
    if (!readme.includes(link)) missingLinks.push(`${packageSlug}: ${link}`);
  }
}
console.log(JSON.stringify({ missingLinks }, null, 2));
if (missingLinks.length > 0) process.exit(1);
NODE
```

Expected output:

```json
{
    "missingLinks": []
}
```

- [ ] **Step 3: Verify local Markdown links**

Run:

```bash
node <<'NODE'
const fs = require('fs');
const path = require('path');
const missing = [];
const files = [];
for (const packageSlug of fs.readdirSync('packages').sort()) {
  const packageDir = `packages/${packageSlug}`;
  if (!fs.existsSync(`${packageDir}/composer.json`)) continue;
  for (const relative of ['README.md']) {
    const file = `${packageDir}/${relative}`;
    if (fs.existsSync(file)) files.push(file);
  }
  const docsDir = `${packageDir}/docs`;
  if (fs.existsSync(docsDir)) {
    for (const doc of fs.readdirSync(docsDir)) {
      if (doc.endsWith('.md')) files.push(`${docsDir}/${doc}`);
    }
  }
}
for (const file of files) {
  const text = fs.readFileSync(file, 'utf8');
  for (const match of text.matchAll(/\]\((?!https?:)([^)]+)\)/g)) {
    const target = match[1].replace(/#.*/, '');
    if (target === '' || target.startsWith('mailto:')) continue;
    const resolved = path.normalize(path.join(path.dirname(file), target));
    if (!fs.existsSync(resolved)) missing.push(`${file}: ${match[1]}`);
  }
}
console.log(JSON.stringify({ missing }, null, 2));
if (missing.length > 0) process.exit(1);
NODE
```

Expected output:

```json
{
    "missing": []
}
```

- [ ] **Step 4: Commit**

Commit the docs changes:

```bash
git add docs/internal/package-doc-gaps.md docs/superpowers/plans/2026-05-06-package-doc-coverage.md packages/dashboard-reports/docs packages/ga4-reports/docs packages/password-policy/docs packages/demo-kit/docs packages/dashboard-reports/README.md packages/ga4-reports/README.md packages/password-policy/README.md packages/demo-kit/README.md
git commit -m "docs: plan package documentation coverage"
```
