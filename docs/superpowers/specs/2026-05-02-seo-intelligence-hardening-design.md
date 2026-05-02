# SEO Intelligence Hardening Design

## Goal

Make Capell SEO Tools feel powerful enough for serious editorial and technical SEO work while keeping the implementation small, predictable, and Capell-native.

This is a follow-up to `docs/superpowers/specs/2026-05-01-seo-tools-expansion-design.md`. The first expansion introduced the editor report, audit surface, schema templates, redirect opportunities, Search Console boundaries, publish checks, and AI briefs. This pass hardens the unfinished edges so the package works well at site scale instead of doing expensive report work inside Filament tables.

## Product Principles

- Editors should see clear SEO guidance without understanding technical SEO internals.
- Admin reports should be site-wide and filterable, including healthy pages and passed checks.
- Tables should read queryable stored state where site-wide filtering or trend comparison is required.
- Redirects remain owned by `packages/redirects`; SEO Tools integrates through public Actions or class-checked optional boundaries.
- Search Console remains optional, but any sync action that exists should produce useful stored/reportable data.
- The package should avoid unnecessary architecture. Use one SEO snapshot table, one Search Console metrics table, and one redirect health cache only if existing redirect storage cannot hold the needed state cleanly.

## Current Gaps

The existing implementation already has `BuildPageSeoReportAction`, `PageSeoReportData`, `PageSeoPanel`, `SEOAuditTable`, `CreateRedirectFromBrokenLinkAction`, Search Console client contracts, and a publish report adapter.

The gaps to address are:

- `PageSeoPanel` renders only a compact overview. It does not expose links, schema, Search Console, redirect opportunities, robots/canonical, and passed checks as first-class sections.
- `CreateRedirectFromBrokenLinkAction` opens the Redirects resource, but does not prefill source, site, language, or target defaults from the `BrokenLink` record.
- `BuildSEOAuditQueryAction` still filters to pages with missing metadata, so the audit is not truly site-wide.
- SEO issue categories are partly computed inside table state callbacks, which makes filtering and reporting weak.
- `SeoCheckModeEnum` exists but is not wired into `capell-seo-tools.publish_gates`.
- `SyncSearchConsoleInsightsAction` calls `decliningPages()`, but `GoogleSearchConsoleClient::decliningPages()` currently returns an empty array.
- `RedirectsTable` computes chain warnings per row through `ValidateRedirectAction`, which is not suitable for larger redirect sets.

## Recommended Architecture

Add a lean SEO intelligence layer centered on one queryable page snapshot.

### Page SEO Snapshots

Create a `PageSeoSnapshot` model and migration in SEO Tools.

The snapshot stores:

- `page_id`
- `site_id`
- `language_id`
- `score`
- `critical_count`
- `warning_count`
- `notice_count`
- `passed_count`
- `issue_keys`
- `passed_check_keys`
- `schema_status`
- `robots_status`
- `canonical_status`
- `internal_link_suggestions_count`
- `redirect_opportunities_count`
- `search_console_status`
- `computed_at`

Use JSON columns only for small structured keys and summary state. Do not store full report prose as the source of truth. Full detail still comes from `BuildPageSeoReportAction`.

Add Actions:

- `PersistPageSeoSnapshotAction`: accepts `Page`, `Site`, `Language`, and `PageSeoReportData`, then upserts one snapshot.
- `RefreshPageSeoSnapshotAction`: builds a fresh report and persists the snapshot.
- `RefreshSiteSeoSnapshotsAction`: refreshes pages for a site in chunks.

`BuildPageSeoReportAction` remains the canonical single-page report builder. The snapshot is only a query and filtering projection.

### Site-Wide Audit

Change `BuildSEOAuditQueryAction` so it returns all eligible pages within `SiteScope`, eager-loaded with site, language, translations, page URL, and the latest matching snapshot.

Move issue-category filtering into `SEOAuditTable` through snapshot-backed filters:

- severity
- issue category
- score range
- schema status
- robots status
- canonical status
- redirect opportunity present
- Search Console decline present
- healthy pages
- stale snapshot

If a page lacks a snapshot, the table should show an explicit "Not scanned" state and offer a refresh action. It should not silently compute every missing report during row rendering.

### Editor Page SEO Panel

Expand `PageSeoPanel` into a richer layout using compact sections or tabs:

- Overview: score, issue counts, SERP preview, social preview.
- Links: internal-link suggestions and redirect/broken-link opportunities.
- Schema: template status, missing fields, schema type.
- Search Console: setup state, clicks, impressions, CTR, average position, and declining-page signal when stored metrics exist.
- Robots and Canonical: indexability, follow directives, canonical URL, sitemap and `llms.txt` state.
- Passed Checks: collapsed by default.

The Blade view should only render grouped report data. Add explicit helpers to `PageSeoReportData` for severity buckets, passed-check groups, schema summaries, link summaries, Search Console summaries, and robots/canonical summaries. Do not add report-building logic to Blade or Filament callbacks.

### Redirect Create Defaults

Keep redirect creation inside the Redirects package.

Add a public Redirects Action that accepts optional defaults:

- source URL
- target URL
- site ID
- language ID
- status code

The Action returns the Redirects manager URL with normalized query defaults and a create-flow flag. `ManageRedirects` reads those defaults, mounts the existing create action, and fills the existing `RedirectForm`.

Then update `CreateRedirectFromBrokenLinkAction` so it passes defaults from `BrokenLink`:

- source URL from the broken target URL, normalized to a path when safe.
- site from the related page or broken-link site context.
- language from the related page URL or page translation.
- target left blank unless a safe suggestion exists.

If Redirects is not installed, the action remains hidden.

### Redirect Health Cache

Stop running redirect validation per table row.

Add a dedicated `redirect_health_snapshots` table in the Redirects package. Keep this projection out of Core `PageUrl` columns so redirect health can evolve without making the shared URL model carry SEO-specific cache state.

The stored state should include:

- page URL ID
- source URL
- target URL
- `has_chain`
- `has_loop`
- `warning_count`
- `error_count`
- `computed_at`

Add Actions:

- `RefreshRedirectHealthSnapshotAction`
- `RefreshRedirectHealthSnapshotsAction`

`RedirectsTable` reads chain and loop badges from the projection. It may offer a row or bulk refresh action, but it must not validate every row during render.

### Search Console Metrics

Keep the current `SearchConsoleClientInterface`, `NullSearchConsoleClient`, and `GoogleSearchConsoleClient` boundary.

Add a small `search_console_url_metrics` table because declining pages require historical comparison:

- `site_id`
- `url`
- `window_start`
- `window_end`
- `clicks`
- `impressions`
- `ctr`
- `average_position`
- `previous_clicks`
- `previous_impressions`
- `previous_ctr`
- `previous_average_position`
- `click_delta`
- `impression_delta`
- `position_delta`
- `synced_at`

Implement `decliningPages()` by querying stored metric rows. `SyncSearchConsoleInsightsAction` fetches current and previous windows from Google, persists the comparison, and then returns the stored declining-page summary.

`SyncSearchConsoleInsightsAction` stores metrics and returns useful counts. If credentials are missing, it returns `configured: false` without writing rows.

### Publish Gates

Wire `SeoCheckModeEnum` into `config/capell-seo-tools.php`:

```php
'publish_gates' => [
    'meta_title' => SeoCheckModeEnum::Blocker->value,
    'meta_description' => SeoCheckModeEnum::Blocker->value,
    'robots' => SeoCheckModeEnum::Blocker->value,
    'canonical' => SeoCheckModeEnum::Warning->value,
    'schema' => SeoCheckModeEnum::Warning->value,
    'internal_links' => SeoCheckModeEnum::Warning->value,
    'social_image' => SeoCheckModeEnum::Warning->value,
    'redirects' => SeoCheckModeEnum::Blocker->value,
    'search_console' => SeoCheckModeEnum::Ignored->value,
]
```

`SeoPublishReportProviderAdapter` should map issue keys through this config:

- `blocker` becomes publish error.
- `warning` becomes publish warning.
- `ignored` is skipped.

If a key is missing from config, default to warning for editorial checks and blocker only for technical checks that can break public indexing or redirect resolution.

### Complexity Guardrails

Do not add a separate table for every SEO concern.

Do not run live HTTP checks or redirect validation during table render.

Do not add external dependencies.

Do not duplicate Redirects package validation in SEO Tools.

Do not store generated AI content or full SEO report prose in snapshots.

Prefer manual/admin-triggered refresh actions first. Add event-driven refresh only where the triggering model change is obvious and cheap, such as page translation metadata updates and redirect create/update/delete.

## Testing Strategy

Add focused Pest coverage:

- snapshot persistence upserts one row per page/site/language.
- site refresh chunks pages and persists snapshots.
- SEO audit query includes healthy and unhealthy pages.
- SEO audit filters use snapshot issue keys and severity counts.
- `PageSeoPanel` receives section-ready report data and renders empty/setup states safely.
- broken-link redirect action builds a prefilled Redirects URL only when Redirects is installed.
- redirect table no longer invokes `ValidateRedirectAction` from row state callbacks.
- redirect health refresh detects chain and loop state.
- Search Console sync persists metric windows and reports declining pages.
- publish gate adapter respects blocker, warning, and ignored config.

Run package tests with:

```bash
vendor/bin/pest packages/seo-tools/tests
vendor/bin/pest packages/redirects/tests
vendor/bin/pest packages/workspaces/tests/Unit/Checks/SeoMetaCheckTest.php
```

Use `composer preflight` before committing the implementation branch.

## Out Of Scope

- Full SEO crawler.
- Live external URL health checks during admin rendering.
- Ranking guarantees or broad analytics dashboards.
- A second redirect system inside SEO Tools.
- AI-generated content publishing.
- Complex scheduling UI. Host apps can schedule refresh Actions later.
