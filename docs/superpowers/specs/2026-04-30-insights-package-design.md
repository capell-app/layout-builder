# Insights Package Design

## Goal

Build `capell-app/insights` as the canonical Capell package for first-party visitor insights, popular page reporting, journey tracking, click/action tracking, and cookie consent management.

The package should let Capell sites understand what visitors do without requiring GA4 Reports or another third-party tracker. Third-party forwarding can be added later, but version 1 stores and dashboard-dashboard_reports first-party data inside Capell.

## Name

Use **Insights**:

- Composer package: `capell-app/insights`
- Namespace: `Capell\Insights`
- Package directory: `packages/insights`
- Translation namespace: `capell-insights`
- Config key: `capell-insights`
- Admin settings group: `insights`

## Package Responsibilities

`insights` owns:

- anonymous visit/session tracking
- page view tracking
- visitor journey reconstruction
- button, link, form, and custom action event tracking
- click location capture through `navigator.sendBeacon()`
- consent category storage and consent audit records
- UK and European consent prompts
- unknown-location visitor self-declaration
- popular and trending page summaries
- insights retention and purge behavior
- admin dashboard widgets for insights summaries

`insights` does not own:

- GA4 Reports, Meta Pixel, or other third-party provider setup in version 1
- heatmaps or replay recording
- A/B testing
- personal user profiling
- raw IP address storage
- raw user agent storage
- cross-site tracking

## Architecture

Domain logic belongs in actions:

- `ResolveConsentRegionAction`
- `CreateInsightsVisitAction`
- `UpdateInsightsConsentAction`
- `RecordInsightsEventAction`
- `RecordPageViewAction`
- `RecordClickAction`
- `RecordCustomActionAction`
- `BuildPopularPagesQueryAction`
- `BuildTrendingPagesQueryAction`
- `BuildJourneyTimelineAction`
- `PurgeInsightsDataAction`

Structured data belongs in data objects:

- `InsightsBeaconData`
- `InsightsConsentData`
- `InsightsEventData`
- `InsightsVisitData`
- `InsightsPageSummaryData`
- `InsightsJourneyStepData`
- `InsightsWindowData`

Enums should model persisted values:

- `InsightsEventType`
- `InsightsConsentCategory`
- `InsightsConsentRegion`
- `InsightsConsentStatus`

Thin controllers should accept data objects and call actions:

- `InsightsBeaconController`
- `InsightsConsentController`

Admin widgets and resources should call query actions. They should not build reporting queries inline.

## Consent Model

Use four consent categories:

- `essential`: required for the consent state and basic security. Always enabled.
- `insights`: first-party page view, journey, search, and click tracking.
- `marketing`: third-party or advertising integrations. Stored now, used by future integrations.
- `preferences`: optional UI or personalization preferences.

The consent UI must include:

- accept all
- reject non-essential
- granular category toggles
- a required cookie policy and terms popup acknowledgement before saving granular choices
- a manage preferences entry point after the first decision

For UK and European visitors, no non-essential insights or marketing event should be recorded until the visitor explicitly opts in. Essential consent records can be stored so the package remembers the visitor's decision.

Current compliance assumption: strictly necessary cookies can be used without consent, but insights cookies generally require consent in the UK and Europe. This follows the GOV.UK Design System cookie guidance and ICO cookie guidance.

## Region Resolution

`ResolveConsentRegionAction` should classify visitors as:

- `uk_or_europe`
- `outside_uk_or_europe`
- `unknown`

The default resolver should use configured server-side location data when available. Because the root project already depends on `torann/geoip`, version 1 can support that package without adding another location dependency.

If the location cannot be determined, the frontend consent modal must ask the visitor to choose whether they are visiting from the UK or Europe. Until that choice is made, treat the visitor as `unknown` and do not run non-essential tracking.

The package should allow a site owner to force a default consent region from config for static or privacy-first deployments.

## Frontend Tracking

Register a frontend render hook that injects a small JavaScript tracker near the end of the document body.

The script should:

- create or reuse an anonymous visit identifier only after consent rules allow it
- send page view events when insights consent exists or the visitor is outside the consent region
- use `navigator.sendBeacon()` for page views, clicks, consent updates, and custom actions
- fall back to `fetch(..., { keepalive: true })` when `sendBeacon` is unavailable
- capture click target metadata from elements with `data-capell-insights`
- capture automatic button and link clicks when enabled by settings
- include click location fields such as viewport coordinates, document coordinates, selector, visible label, nearest landmark, and page URL
- avoid sending form field values, query secrets, passwords, or full DOM snapshots

Recommended markup API:

```html
<button
    data-capell-insights="cta_click"
    data-capell-insights-label="Book a demo"
    data-capell-insights-location="home.hero"
>
    Book a demo
</button>
```

Automatic click tracking should be conservative. It may record buttons, anchors, and submit controls, but it should avoid elements inside admin panels and elements marked with `data-capell-insights-ignore`.

## Beacon Endpoint

Register routes under a configurable prefix, defaulting to `/capell/insights`:

```php
Route::post('events', InsightsBeaconController::class)
    ->middleware(['web'])
    ->name('capell-insights.events');

Route::post('consent', InsightsConsentController::class)
    ->middleware(['web'])
    ->name('capell-insights.consent');
```

The endpoint should accept batches so the browser can send several events in one beacon.

Validation should reject:

- unsupported event types
- oversized payloads
- events without a URL
- click events without a useful target or location
- non-essential events without valid consent when the region requires consent

The endpoint should respond with `204 No Content` for successful beacon writes.

## Database

Create `insights_visits` with:

- `id`
- `uuid` unique
- `site_id` nullable index
- `language_id` nullable index
- `consent_region`
- `consent_status`
- `landing_url`
- `referrer_url` nullable
- `utm_source` nullable index
- `utm_medium` nullable index
- `utm_campaign` nullable index
- `ip_hash` nullable
- `user_agent_hash` nullable
- `started_at` index
- `last_seen_at` nullable index
- timestamps

Create `insights_consents` with:

- `id`
- `visit_id` nullable foreign key
- `consent_region`
- `status`
- `categories` JSON cast to data
- `policy_version`
- `terms_accepted_at` nullable
- `decided_at` index
- `ip_hash` nullable
- `user_agent_hash` nullable
- timestamps

Create `insights_events` with:

- `id`
- `visit_id` nullable foreign key
- `site_id` nullable index
- `language_id` nullable index
- `type` index
- `url` index
- `path` index
- `title` nullable
- `occurred_at` index
- `sequence` unsigned integer
- `event_name` nullable index
- `label` nullable
- `location` nullable index
- `target_selector` nullable
- `viewport_x` nullable
- `viewport_y` nullable
- `document_x` nullable
- `document_y` nullable
- `metadata` JSON cast to data
- timestamps

Do not store raw IP addresses or raw user agents by default. Store salted hashes only when visitor hashing is enabled.

Register all insights tables as protected tables through Capell core.

## Popular And Trending Pages

Popular pages are ranked by page views, unique visits, and click-through counts for a selected date window.

Trending pages compare the current window with the previous equivalent window and rank pages by growth. The action should return enough data for admin widgets to show current count, previous count, absolute change, and percentage change.

Default reporting windows:

- today
- last 7 days
- last 30 days
- custom date range

Version 1 should calculate dashboard-dashboard_reports directly from `insights_events`. A summary table can be added later if the events table becomes too large.

## Journey Tracking

Journey tracking should reconstruct the ordered event trail for a visit using `visit_id`, `occurred_at`, and `sequence`.

A journey step should include:

- event type
- URL and path
- title
- event label
- location
- occurred time
- time since previous step

The admin UI should show journeys as anonymized timelines. It must not expose IP addresses, raw user agents, or form field values.

## Settings

`InsightsSettings` should include:

- `enabled`
- `track_page_views`
- `track_clicks`
- `track_form-builder`
- `automatic_click_tracking`
- `require_consent_for_all_regions`
- `default_consent_region`
- `policy_version`
- `retention_days`
- `hash_visitor_data`
- `hash_salt`
- `ignored_paths`
- `ignored_selectors`
- `route_prefix`

All labels, helper text, modal copy, buttons, widget headings, and validation messages should use `__('capell-insights::...')`.

## Admin

Register dashboard widgets through `CapellAdmin::registerDashboardWidget(...)`, matching the existing package pattern.

Version 1 widgets:

- `PopularPagesWidget`
- `TrendingPagesWidget`
- `InsightsOverviewStatsWidget`
- `RecentJourneysWidget`
- `TopActionsWidget`

If a full Filament resource is added, keep it read-only in version 1. The main operational controls should live in settings.

## Retention

Add:

- `PurgeInsightsDataAction`
- `insights:purge`

Schedule purging monthly from the admin provider. Retention should delete old events, visits, and consent records according to settings while preserving referential integrity.

## Package Dependencies

`capell-app/insights` should require:

- `capell-app/admin`
- `capell-app/core`
- `capell-app/frontend`
- `lorisleiva/laravel-actions`
- `spatie/laravel-data`
- `spatie/laravel-package-tools`

Do not add a new Composer dependency for geolocation in version 1. Use existing project support when available and provide a configurable fallback.

## Relationship To Existing Insights Code

`packages/theme-studio/themes-core` already contains a small GA4 wrapper. Do not delete it during version 1 unless the implementation plan explicitly includes a migration path.

The new insights package should coexist with that wrapper. Future work can add an adapter that forwards selected events to GA4 only when marketing consent is granted.

## Testing

Tests should cover:

- consent region resolution for UK/Europe, outside region, forced config, and unknown
- unknown-location visitor choice requirement
- consent category serialization
- terms acknowledgement requirement for granular consent
- no non-essential event storage before consent in UK/Europe
- page view storage after insights consent
- outside-region page view storage with default settings
- `sendBeacon` payload validation through the HTTP endpoint
- click event storage with location metadata
- ignored paths and selectors
- popular page query
- trending page query
- journey timeline ordering
- purge action retention behavior
- service provider package registration
- render hook registration
- protected table registration

Run the package tests with:

```bash
vendor/bin/pest packages/insights/tests
```

Run affected frontend/theme tests with:

```bash
vendor/bin/pest packages/insights/tests packages/theme-studio/themes-core/tests
```

## Implementation Sequence

1. Create the package skeleton, composer metadata, translations, config, and Capell package manifest.
2. Add settings, enums, data objects, and package service providers.
3. Add migrations, models, casts, factories, and protected table registration.
4. Implement consent region resolution and consent recording.
5. Implement event recording actions and beacon routes.
6. Add frontend script and render hook injection.
7. Add popular pages, trending pages, and journey query actions.
8. Add admin widgets and settings schema.
9. Add purge command, schedule registration, and retention tests.
10. Run focused package tests and affected frontend/theme tests.

This sequence keeps compliance behavior testable before storing insights events, then layers reporting on top of the event model.
