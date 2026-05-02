# Search Console

SEO Tools exposes a small Search Console boundary so page reports can show real performance signals without coupling the rest of the package to Google APIs.

## Configuration

Set these environment variables when the integration is enabled:

```dotenv
CAPELL_SEO_TOOLS_SEARCH_CONSOLE_ENABLED=true
CAPELL_SEO_TOOLS_SEARCH_CONSOLE_CREDENTIALS=/absolute/path/to/service-account.json
CAPELL_SEO_TOOLS_SEARCH_CONSOLE_PROPERTY_URL=https://example.com/
```

`CAPELL_SEO_TOOLS_SEARCH_CONSOLE_PROPERTY_URL` is optional. When omitted, the Google client derives the property from the page URL.

## Client boundary

`SearchConsoleClientInterface` defines:

- `isConfigured()`.
- `pageInsights(string $url)`.
- `decliningPages(int $siteId, int $limit = 10)`.

`GoogleSearchConsoleClient` reads the service-account JSON file, creates a JWT assertion, and calls the Search Analytics endpoint for the page URL. Page reports surface clicks, impressions, CTR, and average position for the last 30 days.

`decliningPages(int $siteId, int $limit = 10)` compares the current 30-day Search Analytics page data with the previous 30-day window for the configured property URL. It returns pages with negative click deltas first, including current clicks, previous clicks, delta, impressions, CTR, and position. The `siteId` keeps the contract aligned with multi-site callers even though the Google request is scoped by the configured property URL.

`NullSearchConsoleClient` is bound when the integration is disabled or unavailable. Page reports then show a setup-required notice instead of failing.

## Report usage

`BuildPageSearchConsoleInsightsAction` is called by `BuildPageSeoReportAction`. It normalizes client responses into `SearchConsoleInsightData`, so UI components never need to know which client is active.

`SyncSearchConsoleInsightsAction` reports from stored URL metric windows. When credentials are missing it returns `configured` false; when metrics exist it can report declining pages from `search_console_url_metrics`.
