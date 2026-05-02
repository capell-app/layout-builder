# SEO Intelligence

SEO Tools turns page metadata into an editor-friendly report instead of leaving checks scattered across forms and publish flows.

## Page report

`Capell\SeoTools\Actions\BuildPageSeoReportAction` returns `PageSeoReportData` for a page, site, and language. The report includes:

- Score from `CalculateSeoScoreAction`.
- Search and social previews.
- Critical and warning issues.
- Internal-link suggestions.
- Schema template coverage.
- Search Console insights.
- Redirect-opportunity slots for admin workflows.

Issue severity is backed by `SeoIssueSeverityEnum`. Missing title and description are critical because they block basic discoverability. Length problems, duplicate titles, and `noindex` directives are warnings because they need editorial attention but can still be intentional.

## Editor panel

`PageSeoPanel` embeds the report into the page form. Editors can review:

- Search-preview title, description, URL, and site name.
- Social-preview title, description, and image state.
- Issues grouped by severity.
- Internal-link suggestions scored from related page content.
- Schema-template coverage for the page type.
- Search Console setup or performance signals.
- AI content brief action when AI is enabled.

The panel is advisory. Saving and publishing remain owned by the normal Capell page workflow.

## Audit table

`BuildSEOAuditQueryAction` and `SEOAuditTable` provide an admin-wide view of page SEO health. Use it for content QA, launch reviews, and recurring editorial cleanup. The table can be scoped by the current admin site and language context.

## Internal links

`SuggestInternalLinksAction` compares source-page tokens with candidate page titles and meta descriptions. Suggestions are intentionally simple and deterministic so editors can trust the reason shown beside each link. The action returns at most five suggestions.

## Redirect opportunities

`BuildRedirectOpportunityReportAction` groups recorded broken links by target URL and suggests a direct live target when it can resolve one in the same site and language. The Redirects package remains the source of truth for persisted redirects.

## Publish checks

The Workspaces package can consume `SeoPublishReportProvider` through `SeoPublishReportProviderAdapter`. The adapter exposes SEO score and issue counts to publish validation without making SEO Tools depend on Workspaces internals outside the explicit bridge.

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

The brief is a planning aid. It records generation history, but it does not automatically rewrite page content or metadata.
