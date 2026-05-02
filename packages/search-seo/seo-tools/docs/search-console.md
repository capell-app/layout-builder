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

`GoogleSearchConsoleClient` reads the service-account JSON file, creates a JWT assertion, and calls the Search Analytics endpoint for the page URL. Page reports currently surface clicks, impressions, CTR, and average position for the last 30 days.

`NullSearchConsoleClient` is bound when the integration is disabled or unavailable. Page reports then show a setup-required notice instead of failing.

## Report usage

`BuildPageSearchConsoleInsightsAction` is called by `BuildPageSeoReportAction`. It normalizes client responses into `SearchConsoleInsightData`, so UI components never need to know which client is active.

`SyncSearchConsoleInsightsAction` is intentionally a boundary action. It can be scheduled by host applications when persistent historical storage is added, but the current package does not write Search Console metrics to the database.
