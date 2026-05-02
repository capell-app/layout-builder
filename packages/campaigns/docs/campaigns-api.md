# Campaigns API

## Actions

- `BuildCampaignUrlAction::run($url, $utm)` appends missing UTM parameters while preserving existing query values.
- `ResolveCampaignFromUrlAction::run($url)` resolves an active campaign from `utm_campaign` or a registered landing page URL.
- `RecordCampaignConversionAction::run($goal, $visit, $event, $landingPage)` records an idempotent conversion.
- `RecordCtaClickConversionAction::run($goalKey, $visit, $event)` records CTA click conversions.
- `RecordFormSubmissionConversionAction::run($formTarget, $visit, $event)` records form submission conversions.
- `RecordPageViewConversionAction::run($landingPage, $visit, $event)` records page-view conversions for landing pages.
- `InstallCampaignLayoutsAction::run($force)` installs campaign-focused Mosaic layout presets.

## Widgets

Campaigns registers three Mosaic widget components:

- `CampaignHero`
- `CampaignCtaBlock`
- `CampaignLeadForm`

These are intentionally campaign-aware wrappers around existing page, CTA, and form responsibilities.
