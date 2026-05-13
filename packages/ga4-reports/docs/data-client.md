# Data Client

GA4 Reports reads Google Analytics data through `GA4ReportsDataClientInterface`, persists daily and page metrics, then renders admin widgets from stored rows. The package binds a real client when config is complete and a null client when it is not.

## Config Keys

| Key                                   | Use                                                    |
| ------------------------------------- | ------------------------------------------------------ |
| `capell-ga4-reports.enabled`          | Enables sync and admin reporting.                      |
| `capell-ga4-reports.property_id`      | GA4 property ID.                                       |
| `capell-ga4-reports.credentials_path` | Service account credentials path.                      |
| `capell-ga4-reports.sync_days`        | Default lookback window for sync actions.              |
| `capell-ga4-reports.route_slug`       | Admin route slug.                                      |
| `capell-ga4-reports.tables.*`         | Table-name overrides and protected table registration. |

Prefer settings UI values when a host app exposes them; config is still the package fallback.

## Swap the Data Client

Use the contract for tests, local demos, or a different analytics backend.

```php
use Capell\GA4Reports\Contracts\GA4ReportsDataClientInterface;
use Capell\GA4Reports\Data\GA4ReportsDailyMetricData;
use Capell\GA4Reports\Data\GA4ReportsPageMetricData;
use Capell\GA4Reports\Data\GA4ReportsWindowData;

final class DemoGA4ReportsDataClient implements GA4ReportsDataClientInterface
{
    public function isConfigured(): bool
    {
        return true;
    }

    public function dailyMetrics(GA4ReportsWindowData $window): array
    {
        return [
            new GA4ReportsDailyMetricData(
                propertyId: $window->propertyId,
                metricDate: $window->startsAt,
                totalUsers: 12,
                sessions: 18,
                screenPageViews: 42,
                engagedSessions: 10,
                engagementRate: 0.56,
                averageSessionDuration: 38.5,
                eventCount: 64,
                conversions: 1,
            ),
        ];
    }

    public function pageMetrics(GA4ReportsWindowData $window): array
    {
        return [
            new GA4ReportsPageMetricData(
                propertyId: $window->propertyId,
                metricDate: $window->startsAt,
                pagePath: '/about',
                pageTitle: 'About',
                totalUsers: 5,
                sessions: 7,
                screenPageViews: 11,
                eventCount: 14,
                conversions: 0,
            ),
        ];
    }
}

$this->app->singleton(GA4ReportsDataClientInterface::class, DemoGA4ReportsDataClient::class);
```

Return empty arrays when the backend has no data. Throw `GA4ReportsApiException` only for failures operators need to see.

## Verification

```bash
vendor/bin/pest packages/ga4-reports/tests --configuration=phpunit.xml
```
