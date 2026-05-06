---
name: capell-campaign-studio-development
description: Use when editing Capell CampaignStudio landing pages, CTAs, goals, or attribution.
---

# Capell CampaignStudio

Campaign groups, landing pages, CTA blocks, conversion goals, UTM attribution, and reporting.

## Look

- `packages/campaign-studio/src`
- `packages/campaign-studio/docs`
- `packages/campaign-studio/README.md`

## Rules

- Keep attribution and conversion writes explicit and testable.
- LayoutBuilder configurators should not own campaign domain logic.
- Preserve page schema extender behaviour for campaign fields.
- Run `vendor/bin/pest packages/campaign-studio/tests`.
