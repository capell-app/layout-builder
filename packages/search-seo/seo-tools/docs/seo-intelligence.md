# SEO Intelligence

SEO Tools turns page metadata into an editor-friendly report instead of leaving checks scattered across forms and publish flows.

## Page report

`Capell\SeoTools\Actions\BuildPageSeoReportAction` returns `PageSeoReportData` for a page, site, and language. The report includes:

- Score from `CalculateSeoScoreAction`.
- Search and social previews.
- Critical and warning issues.
- Passed checks, so editors can see what is already healthy.
- Canonical URL and robots directives.
- Internal-link suggestions.
- Schema template coverage.
- Search Console insights.
- Redirect-opportunity slots for the current page.

Issue severity is backed by `SeoIssueSeverityEnum`. Missing title and description are critical because they block basic discoverability. Length problems, duplicate titles, and `noindex` directives are warnings because they need editorial attention but can still be intentional.

## Editor panel

`PageSeoPanel` embeds the report into the page form. Editors can review:

- Search-preview title, description, URL, and site name.
- Social-preview title, description, and image state.
- Issues grouped by severity.
- Passed checks.
- Canonical URL and robots directives.
- Internal-link suggestions scored from related page content.
- Schema-template coverage for the page type.
- Redirect opportunities from broken-link records attached to the page.
- Search Console setup or performance signals.
- AI content brief action when AI is enabled.

The panel is advisory. Saving and publishing remain owned by the normal Capell page workflow.

## Audit table

`BuildSEOAuditQueryAction` and `SEOAuditTable` provide an admin-wide view of page SEO health. The query includes all pages visible to the current admin site and language scope, then lets the report builder classify missing metadata, duplicate titles, robots issues, schema gaps, internal-link gaps, Search Console signals, sitemap or `llms.txt` setup, and redirect opportunities. Use it for content QA, launch reviews, and recurring editorial cleanup.

## Page SEO snapshots

Page SEO snapshots store filterable admin state for page SEO health. They are query projections, not the source SEO report. Use `BuildPageSeoReportAction` when an editor or integration needs the full report detail.

## Audit filters

Audit filters read from the snapshot state so admin tables can stay fast and sortable across pages, sites, and languages. When a filterable admin view needs fresh state, refresh the relevant snapshots before relying on the filter results.

## Manual refresh actions

Run `RefreshPageSeoSnapshotAction` after changing one page or `RefreshSiteSeoSnapshotsAction` when a site-wide admin view needs fresh SEO state. These actions rebuild the projection from the report pipeline; they do not replace `BuildPageSeoReportAction` as the detailed report source.

## Internal links

`SuggestInternalLinksAction` compares source-page tokens with candidate page titles and meta descriptions. Suggestions are intentionally simple and deterministic so editors can trust the reason shown beside each link. The action returns at most five suggestions.

## Redirect opportunities

`BuildRedirectOpportunityReportAction` groups recorded broken links by target URL and suggests a direct live target when it can resolve one in the same site and language. It can be scoped to a single page for the editor panel or run across a site for the audit table.

`CreateRedirectForBrokenLinkAction` creates a normal manual redirect in `page_urls` from a `BrokenLink` record. It preserves the broken link's page, site, language, and source URL context, then validates the target through the Redirects package before writing anything. The Redirects package remains the source of truth for persisted redirects.

## Publish checks

The Workspaces package can consume `SeoPublishReportProvider` through `SeoPublishReportProviderAdapter`. The adapter exposes SEO score and issue counts to publish validation without making SEO Tools depend on Workspaces internals outside the explicit bridge.

Publish gate modes are configurable in `capell-seo-tools.publish_gates`. Defaults map critical issues to blockers and warning or notice issues to warnings. Per-check overrides can set individual issue keys, such as `meta_title`, `schema`, or `search_console`, to `blocker`, `warning`, or `ignored`.

## AI content briefs

`GenerateAiContentBriefAction` sends the current report, page content, site, and language context to the configured provider and returns structured suggestions:

- Content angle.
- Missing topics.
- Suggested headings.
- FAQ ideas.
- Schema opportunities.
- Internal-link ideas.
- Meta title alternatives.
- Meta description alternatives.

The brief is a planning aid. It receives the same report context shown in the editor panel, including canonical and robots state, passed checks, schema reports, internal-link suggestions, redirect opportunities, and Search Console signals. It records generation history, but it does not automatically rewrite page content or metadata.
