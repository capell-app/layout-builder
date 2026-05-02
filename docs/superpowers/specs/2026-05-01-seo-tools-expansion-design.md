# SEO Tools Expansion Design

## Goal

Build one complete SEO Tools expansion that brings Capell closer to the best practical parts of Yoast SEO and Rank Math SEO while staying Capell-native: editor guidance, technical SEO automation, redirect visibility, internal linking, schema templates, Search Console insights, publishing gates, and AI content briefs.

The release should ship as one coherent upgrade, not as a hidden foundation-only change. Editors should immediately see better SEO feedback while developers retain Actions, Data objects, extension points, and package boundaries.

## Current Context

`capell-app/seo-tools` already provides page SEO fields, social metadata, robots/canonical handling, XML sitemaps, JSON-LD `@graph`, `/llms.txt`, broken-link records, translation coverage reports, AI-assisted title/meta generation, sitemap admin tools, and render hooks.

`capell-app/redirects` already provides the redirect manager. It stores redirects in Core's `page_urls` table, supports manual 301/302 redirects, automatic redirects when page URLs change, CSV import/export, duplicate/self/loop/chain validation, frontend resolution, hit counts, and admin CRUD. SEO Tools must reuse and enhance this package instead of creating a second redirect system.

`capell-app/site-search` provides search logging and drivers. SEO Tools can use the same content/indexing vocabulary for internal-link suggestions, but SEO Tools must not reach into Site Search internals unless the dependency is explicit and justified.

## Product Shape

This release is an editor-first SEO workflow with technical automation behind it.

The editor experience should answer:

- How healthy is this page?
- What exactly needs fixing before publish?
- How will this page look in search and social cards?
- Which internal links should I add?
- Is schema present and valid for this page type?
- Are there redirect or broken-link actions related to this page?
- Are there Search Console issues or opportunities?
- What AI brief would improve this content?

The site/admin experience should answer:

- Which pages most urgently need SEO work?
- Which broken links or 404s should become redirects?
- Which pages are losing search visibility?
- Which page types lack schema templates?
- Which publish checks are warnings and which are blockers?

## Feature Scope

### 1. Editor SEO Panel

Add an editor-facing SEO panel to the page editing workflow. It should sit near the existing SEO metadata fields and show:

- SEO score.
- Critical issues.
- Warnings.
- Passed checks.
- SERP preview.
- Open Graph/social preview.
- Canonical URL and robots status.
- Schema status.
- Internal-link suggestions.
- Redirect/broken-link related actions when available.
- AI brief action.

This panel should be powered by Actions and Data classes, not Filament-only logic.

Recommended files:

- `packages/seo-tools/src/Actions/BuildPageSeoReportAction.php`
- `packages/seo-tools/src/Data/PageSeoReportData.php`
- `packages/seo-tools/src/Data/SeoIssueData.php`
- `packages/seo-tools/src/Enums/SeoIssueSeverityEnum.php`
- `packages/seo-tools/src/Filament/Components/Forms/Page/PageSeoPanel.php`
- `packages/seo-tools/resources/views/filament/components/page-seo-panel.blade.php`

### 2. Expanded SEO Audit

Replace the current narrow audit query with a richer report that can score pages and expose issue categories. The existing `SEOAuditPage` and `SEOAuditTable` should remain the admin surface, but its data should come from reusable SEO report Actions.

Checks:

- Missing, too-short, or too-long meta title.
- Missing, too-short, or too-long meta description.
- Duplicate meta title within the same site and language.
- Missing social image.
- Missing canonical URL when an override is expected.
- Robots directives that exclude indexable public pages.
- Missing image alt text where media can be inspected safely.
- Weak or missing internal links.
- Missing or incomplete schema.
- Broken links related to the page.
- Translation gaps for multilingual sites.
- Sitemap inclusion status.
- `/llms.txt` inclusion status.

The score should be simple and explainable:

- Critical issue: -25.
- Warning: -10.
- Notice: -3.
- Minimum score: 0.
- Maximum score: 100.

The score is not a promise of ranking. It is an editorial readiness score.

### 3. Redirect Manager Improvements

Do not rebuild redirects inside SEO Tools. Improve `capell-app/redirects` and connect SEO Tools to it.

Improvements:

- Add a "Create redirect" action from SEO Tools broken-link and 404 tables when Redirects is installed.
- Add a "redirect opportunity" report that groups broken links and 404s by source URL, hit count, site, language, and suggested target.
- Add table columns/filters to the Redirects manager for SEO usefulness: last hit, hit count bucket, automatic/manual, chain warning, loop risk, and target status from already-recorded broken-link or redirect-check data. Do not perform live HTTP checks during table rendering.
- Surface redirect health in SEO audit: redirect chains and heavily-hit 404s are warnings; redirect loops are critical.
- Keep redirect storage in `page_urls`.
- Keep validation in `Capell\Redirects\Actions\ValidateRedirectAction`.
- Cross-package coupling should use contracts, optional class checks, events, or admin action registration. SEO Tools must not duplicate redirect validation rules.

If Redirects is not installed, SEO Tools should show broken-link/404 information without redirect actions.

### 4. Internal Linking Suggestions

Add internal-link suggestions for pages and articles.

Suggestions should use available Capell content signals:

- Page title.
- Meta title.
- Meta description.
- Page content text where extractable.
- Article title/summary if Blog is installed.
- Tags when available.
- Existing internal links to avoid duplicate suggestions.

The first version should be deterministic and local. No external SEO API is required. AI can explain or rank suggestions in the AI brief, but the base suggestions should work without AI.

Recommended files:

- `packages/seo-tools/src/Actions/SuggestInternalLinksAction.php`
- `packages/seo-tools/src/Data/InternalLinkSuggestionData.php`
- `packages/seo-tools/src/Support/InternalLinks/InternalLinkCandidateRepository.php`

### 5. Schema Templates

Keep the existing JSON-LD `@graph` pipeline, but add template registration so packages can declare page-type schema templates.

Requirements:

- SEO Tools owns the schema template registry.
- Packages register templates through a service provider extension point.
- Templates return structured arrays/Data objects used by `SchemaGraphAction`.
- Page type packages can provide defaults without SEO Tools importing their internals.
- Editors can see schema status and missing required fields.
- The implementation should support Article, WebPage, FAQ, HowTo, Event, LocalBusiness, Product, Video, and Organization templates as template types, but only implement default builders where Capell has enough data.

This should be closer to Rank Math's schema power, but safer for Capell: package-owned builders, typed inputs, and no giant untyped schema blob crossing layers.

### 6. Search Console Insights

Add optional Search Console integration.

First release scope:

- Store connection/settings shape.
- Show disabled setup state when credentials are missing.
- Sync or fetch indexing issues by URL.
- Show query impressions/clicks/CTR/position for a page after Search Console data has been synced for that URL.
- Show top declining pages in the SEO Tools admin area.
- Keep analytics summary intentionally small and action-oriented.

No ranking guarantees, no broad analytics dashboard, and no replacement for a future analytics package.

Recommended boundaries:

- `SearchConsoleClientInterface`
- `NullSearchConsoleClient`
- `GoogleSearchConsoleClient`
- `SyncSearchConsoleInsightsAction`
- `BuildPageSearchConsoleInsightsAction`

### 7. Publishing Gates

Expand the existing Workspaces `SeoMetaCheck` concept into configurable SEO publish gates.

Requirements:

- Checks can be configured as blocker, warning, or ignored.
- Publish gates reuse SEO report Actions.
- Critical technical failures, such as noindex on a public page or invalid redirect loop, can block publish when configured.
- Missing meta/social/internal links can warn by default.
- The Workspaces package should integrate through contracts/events or a small adapter, without moving SEO logic into Workspaces.

### 8. AI Content Briefs

Add an AI brief action that helps editors improve content without autopublishing.

The brief should include:

- Suggested content angle.
- Missing topics.
- Suggested headings.
- FAQ ideas.
- Schema opportunities.
- Internal links to add.
- Meta title/description alternatives.
- Search Console opportunity context when available.

The brief must be human-reviewed. It should create draft suggestions only; it must not silently modify content.

Reuse existing SEO Tools AI infrastructure:

- `AiFeatureRegistry`
- `AiRateLimiter`
- `PromptRepository`
- generation history/events
- existing provider configuration

## Architecture

### Actions

All domain logic belongs in Actions. Filament pages and components call Actions and render Data objects.

Core Actions:

- `BuildPageSeoReportAction`
- `BuildSiteSeoOverviewAction`
- `CalculateSeoScoreAction`
- `SuggestInternalLinksAction`
- `BuildSchemaTemplateReportAction`
- `BuildRedirectOpportunityReportAction`
- `BuildPageSearchConsoleInsightsAction`
- `GenerateAiContentBriefAction`

### Data

Use Data classes across boundaries:

- `PageSeoReportData`
- `SeoIssueData`
- `SeoPreviewData`
- `InternalLinkSuggestionData`
- `SchemaTemplateReportData`
- `RedirectOpportunityData`
- `SearchConsoleInsightData`
- `AiContentBriefData`

No bare arrays should cross from Actions into Filament except where existing package APIs require arrays for schema rendering.

### Enums

Use enums for persisted/configured values and UI options:

- `SeoIssueSeverityEnum`
- `SeoCheckKeyEnum`
- `SeoCheckModeEnum`
- `SchemaTemplateTypeEnum`
- `SearchConsoleMetricEnum`

Enums used in Filament controls should implement `HasLabel`.

### Package Boundaries

SEO Tools may depend on Core, Admin, Frontend, and optionally declared companion packages. It must not import package internals without a declared dependency and a clear integration boundary.

Redirect integration should prefer:

- Redirects contracts/actions where already public.
- Optional admin actions only when the Redirects package is installed.
- No duplicate redirect storage.
- No duplicate redirect validation.

Blog, Tags, Site Search, Workspaces, and future Events integrations should be optional extension points.

## UI Design

The UI should be quiet and operational, not gamified. Avoid Yoast-style traffic lights as the main metaphor. Use:

- A numeric score.
- Issue sections grouped by severity.
- Preview tabs for Search and Social.
- Inline action buttons for "Edit meta", "Create redirect", "Generate brief", and "View suggestions".
- Badges for schema, indexability, sitemap, and translation status.

The SEO audit admin table should be dense and filterable:

- Score.
- Critical count.
- Warning count.
- Site.
- Language.
- Page type.
- Indexability.
- Schema status.
- Broken links.
- Translation coverage.
- Last updated.

## Error Handling

- Missing optional packages should degrade gracefully.
- Missing Search Console credentials should show setup state, not errors.
- AI provider failure should record generation failure and show a user-facing error.
- Redirect validation failures should display Redirects package messages.
- Schema builders should fail closed: missing optional fields become warnings, invalid required fields become critical issues.

## Testing

Test Actions directly with Pest.

Required test groups:

- Unit tests for score calculation.
- Unit tests for issue severity and check mode enums.
- Integration tests for page SEO report generation.
- Integration tests for SEO audit table query/report shape.
- Integration tests for redirect opportunity reporting using the existing Redirects package.
- Integration tests for internal-link suggestions.
- Integration tests for schema template registry and report output.
- Unit tests for `NullSearchConsoleClient`.
- Feature/integration tests for publish gate severity mapping.
- Unit tests for AI content brief prompt/data parsing with fake AI responses.
- Arch tests confirming SEO Tools does not import companion package internals outside approved integration namespaces.

Commands:

- `vendor/bin/pest packages/seo-tools/tests`
- `vendor/bin/pest packages/redirects/tests`
- `vendor/bin/pest tests/Packages`

Run `composer preflight` before merge if the release branch is otherwise clean.

## Documentation

Update:

- `packages/seo-tools/README.md`
- `packages/seo-tools/docs/seo-meta-and-discoverability.md`
- `packages/redirects/docs/redirects.md`
- `docs/openai-integration.md` for AI briefs

Add new docs:

- `packages/seo-tools/docs/seo-intelligence.md`
- `packages/seo-tools/docs/search-console.md`
- `packages/seo-tools/docs/schema-templates.md`

## Release Acceptance Criteria

- Editors can see a page SEO score, issue list, SERP preview, social preview, schema status, internal-link suggestions, redirect opportunities, Search Console setup/insights, and AI brief action from the SEO editing flow.
- Admins can use an expanded SEO audit view to prioritize site-wide work.
- Existing Redirects manager remains the canonical redirect system and gains SEO-oriented actions/filters instead of being duplicated.
- Broken links and 404-style records can be turned into redirects when Redirects is installed.
- Publish gates can block, warn, or ignore SEO checks based on configuration.
- AI briefs create reviewable suggestions and never autopublish.
- Tests cover the major Actions and cross-package boundaries.
- The package remains PHP 8.2 compatible and follows Capell Actions + Data standards.
