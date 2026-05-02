# Capell Analytics

**Product group:** Capell Growth
**Tier:** Premium

First-party analytics, visitor journeys, click tracking, and consent management for Capell CMS.

## Features

- Page view, click, journey, popular page, and trending page tracking.
- Consent-aware frontend tracker injected through Capell render hooks.
- UK and Europe visitors are asked for cookie category approval before analytics events are recorded.
- Unknown visitor regions are treated as requiring explicit consent.
- Beacon ingestion uses `navigator.sendBeacon` with a `fetch` fallback.
- Visitor identifiers are stored as hashes by default.
- Filament dashboard widgets provide popular pages, trending pages, top actions, journeys, and overview stats.
- Retention cleanup is available through the analytics purge action and command.

## Consent

Essential cookies are always enabled. Analytics and marketing cookies require explicit approval in consent-gated regions, and visitors must accept the policy terms before optional categories are stored.

The region resolver uses request context from trusted CDN headers where available. If a location cannot be determined, the visitor is placed in the unknown region and the consent prompt is shown.

## Configuration

Configuration lives in `config/capell-analytics.php`. The main settings are:

- `enabled` toggles package tracking.
- `require_consent_for_all_regions` forces consent before recording any region.
- `ignored_paths` excludes internal or sensitive paths from beacon storage.
- `retention_days` controls purge defaults.
- `hash_visitor_data` and `hash_salt` control IP and user agent hashing.

## Testing

Run:

```bash
vendor/bin/pest packages/analytics/tests
```
