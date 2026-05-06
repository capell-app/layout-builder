# Insights

Status: **Available, schema-owning** · Kind: **package** · Tier: **premium** · Bundle: **growth** · Contexts: **admin, frontend** · Product group: **Capell Growth**

## What This Plugin Adds

Insights records first-party visits, events, consent decisions, page views, clicks, and journey data for Capell sites.

- Frontend beacon endpoints for events and consent.
- Render hook that can register the tracker.
- Dashboard widgets for overview stats, popular pages, top actions, journeys, and trending pages.
- Settings schema for insights retention and behaviour.

## Why It Matters

**For developers:** Keeps insights in Laravel actions and data objects, with explicit consent enums and configurable routes.

**For teams:** Gives site operators practical traffic and journey insight without sending the workflow through an external dashboard first.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Admin](https://github.com/capell-app/admin)
- [Capell Core](https://github.com/capell-app/core)
- [Capell Frontend](https://github.com/capell-app/frontend)

**Open-source packages used here**

- [Laravel Actions](https://github.com/lorisleiva/laravel-actions) - single-purpose action classes that keep package workflows out of controllers and Filament resources.
- [Spatie Laravel Data](https://github.com/spatie/laravel-data) - typed data objects for package boundaries, form state, settings, and structured results.
- [Spatie Laravel Package Tools](https://github.com/spatie/laravel-package-tools) - Laravel package bootstrapping for config, migrations, commands, translations, and service provider setup.

**Linked package previews**

[![Laravel Actions GitHub preview](https://opengraph.githubassets.com/capell-readme/lorisleiva/laravel-actions)](https://github.com/lorisleiva/laravel-actions)

[![Spatie Laravel Data GitHub preview](https://opengraph.githubassets.com/capell-readme/spatie/laravel-data)](https://github.com/spatie/laravel-data)

[![Spatie Laravel Package Tools GitHub preview](https://opengraph.githubassets.com/capell-readme/spatie/laravel-package-tools)](https://github.com/spatie/laravel-package-tools)

## Screens And Workflow

Screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment.

- Insights overview dashboard widgets.
- Popular pages widget.
- Recent journeys widget.
- Insights settings screen.
- Frontend page with tracker active.

## Technical Shape

- InsightsServiceProvider and AdminServiceProvider register routes, settings, and widgets.
- Config file: capell-insights.php.
- Routes: POST capell/insights/events and POST capell/insights/consent by default.
- Models: InsightsVisit, InsightsConsent, InsightsEvent.
- Actions record page views, clicks, custom events, and consent updates.
- PurgeInsightsDataCommand supports retention cleanup.

## Data Model

- insights_visits stores site, language, consent, landing URL, hashed visitor data, and start time.
- insights_consents stores consent decisions for a visit.
- insights_events stores event type, URL, path, metadata, and occurrence time.
- Visits relate to events and consents.
- Retention is governed by retention_days and purge actions.

## Install Impact

- Adds insights tables and settings migration.
- Adds beacon and consent public POST routes.
- Adds dashboard widgets and insights settings.
- Uses capell-insights config keys for route prefix, consent, hashing, retention, and ignored paths.
- May need scheduled cleanup if retention should be enforced automatically.

## Commands

- `insights:purge {--days= : Override insights retention days}` (packages/insights/src/Console/Commands/PurgeInsightsDataCommand.php)

## Admin And Access

- None proven in this package directory.

- Gate: InsightsOverviewStatsWidget: `admin`, `super_admin`
- Gate: PopularPagesWidget: `admin`, `super_admin`
- Gate: RecentJourneysWidget: `admin`, `super_admin`
- Gate: TopActionsWidget: `admin`, `super_admin`
- Gate: TrendingPagesWidget: `admin`, `super_admin`

## Common Pitfalls

- Exclude admin, Livewire, and insights routes from tracking.
- Set hash_salt deliberately before production data is recorded.
- Consent settings must match the site privacy policy.

## Quick Start

1. Install the package with `composer require capell-app/insights`.
2. Run the package migrations or the Capell package installer required by the host app.
3. Open the new admin or frontend surface and verify the result.

## Next Steps

- [docs/overview.md](docs/overview.md)
- [../search/README.md](../search/README.md)
- [../campaign-studio/README.md](../campaign-studio/README.md)
- [docs/credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
