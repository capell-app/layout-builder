# Tracking And Consent

Insights records first-party activity through two public endpoints and a frontend render hook. The package should stay invisible to admin pages, Livewire traffic, debug tooling, and any path listed in `capell-insights.ignored_paths`.

## Runtime Flow

1. `RegisterInsightsTrackerHook` injects the tracker when `capell-insights.enabled` is true and the frontend render hook registry is bound.
2. The browser posts event batches to `POST /capell/insights/events` by default.
3. Consent changes post to `POST /capell/insights/consent`.
4. Controllers turn request payloads into `InsightsBeaconData`, `InsightsConsentData`, and `InsightsEventData`.
5. Actions write `InsightsVisit`, `InsightsConsent`, and `InsightsEvent` rows.

The route prefix comes from `capell-insights.route_prefix`. Both endpoints use the `web` middleware group, skip CSRF, and apply `throttle:60,1`.

## Config Keys

| Key                                               | Use                                                                  |
| ------------------------------------------------- | -------------------------------------------------------------------- |
| `capell-insights.enabled`                         | Turns tracker registration on or off.                                |
| `capell-insights.route_prefix`                    | Prefix for beacon and consent routes.                                |
| `capell-insights.track_page_views`                | Records page-view events when enabled.                               |
| `capell-insights.track_clicks`                    | Records click events when enabled.                                   |
| `capell-insights.automatic_click_tracking`        | Lets the frontend tracker capture clicks automatically.              |
| `capell-insights.require_consent_for_all_regions` | Blocks tracking until consent exists, regardless of detected region. |
| `capell-insights.default_consent_region`          | Fallback consent region when the request cannot resolve one.         |
| `capell-insights.policy_version`                  | Stored with consent records so policy updates can be audited.        |
| `capell-insights.retention_days`                  | Default cleanup window for `insights:purge`.                         |
| `capell-insights.hash_visitor_data`               | Hashes visitor identifiers before storage.                           |
| `capell-insights.hash_salt`                       | Salt used for hashing. Set this before production traffic.           |
| `capell-insights.ignored_paths`                   | Paths that should never be tracked.                                  |
| `capell-insights.ignored_selectors`               | Click targets the frontend tracker should skip.                      |
| `capell-insights.tables.*`                        | Table-name overrides, also used when registering protected tables.   |

## Record a Custom Action

Use `RecordCustomActionAction` for server-side events that are not browser clicks or page views.

```php
use Capell\Insights\Actions\RecordCustomActionAction;
use Capell\Insights\Data\InsightsEventData;
use Capell\Insights\Enums\InsightsEventType;

RecordCustomActionAction::run(
    visitUuid: 'visit_01HXZ8QY9J2N3R4S5T6V7W8X9Y',
    data: new InsightsEventData(
        type: InsightsEventType::Custom,
        url: 'https://example.test/newsletter',
        eventName: 'newsletter_signup',
        label: 'Footer signup',
    ),
);
```

Keep custom event names stable. Store identifiers and dimensions, not full request bodies.

## Update Consent

Consent writes should go through `UpdateInsightsConsentAction` so the stored region, category, status, and policy version stay consistent.

```php
use Capell\Insights\Actions\UpdateInsightsConsentAction;
use Capell\Insights\Data\InsightsConsentData;
use Capell\Insights\Enums\InsightsConsentRegion;
use Capell\Insights\Enums\InsightsConsentStatus;
use Illuminate\Http\Request;

final class ConsentController
{
    public function __invoke(Request $request)
    {
        return UpdateInsightsConsentAction::run(
            request: $request,
            data: new InsightsConsentData(
                insights: true,
                marketing: false,
                preferences: true,
            ),
            status: InsightsConsentStatus::Granular,
            region: InsightsConsentRegion::UkOrEurope,
        );
    }
}
```

If the constructor shape changes, update this doc with the action in the same change.

## Retention

Use the package command for cleanup in the host app. In this repository, test the behavior directly:

```bash
vendor/bin/pest packages/insights/tests --configuration=phpunit.xml
```

The runtime command is `insights:purge {--days=}` in the host application. This repository does not run `php artisan`.

## Safety Notes

- Keep admin, Livewire, debug, storage, and beacon paths in `ignored_paths`.
- Set `hash_salt` before recording production data. Changing it later breaks visitor continuity.
- Treat raw IP addresses and user agents as sensitive. Prefer hashed fields unless a product requirement says otherwise.
