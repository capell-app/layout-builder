# Campaigns Database

## Tables

- `campaign_groups`: campaign identity, status, date window, budget notes, and default UTM values.
- `campaign_landing_pages`: links a campaign group to a Core page and optional primary conversion goal.
- `campaign_cta_blocks`: reusable CTA content and button definitions.
- `campaign_conversion_goals`: page view, CTA click, form submission, and custom action goals.
- `campaign_conversions`: immutable conversion records with attribution snapshots.

## Attribution

Campaigns reads UTM fields from Analytics visits when Analytics is installed. Conversion records store their own attribution snapshot so historical reports remain stable when a campaign group or landing page is edited later.

## Layout Presets

The layout installer creates three Mosaic layout presets:

- Lead Generation
- Product Launch
- Webinar
