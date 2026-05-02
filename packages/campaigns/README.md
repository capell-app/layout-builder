# Capell Campaigns

**Product group:** Capell Growth
**Tier:** Premium

Campaigns adds marketing-site tooling on top of Capell pages, Mosaic layouts, Forms, and Analytics.

It provides campaign groups, landing-page records, reusable CTA blocks, conversion goals, conversion attribution, campaign Mosaic widgets, installable campaign layouts, and dashboard reporting.

## When to install it

Install Campaigns when a Capell site needs editor-managed marketing campaigns, campaign-specific landing pages, reusable calls to action, conversion goals, and UTM attribution without moving those concerns into Core.

## Quick install

```bash
composer require capell-app/campaigns
php artisan migrate
php artisan optimize:clear
```

Campaigns depends on Core, Admin, Frontend, Mosaic, Forms, and Analytics.

## Install layout presets

```bash
vendor/bin/testbench capell:campaigns-install-layouts
```

Use `--force` to update existing preset layouts.

## What appears in the admin

| Area              | What editors can do                                                      |
| ----------------- | ------------------------------------------------------------------------ |
| Campaign groups   | Manage campaign identity, status, dates, budgets, and default UTM values |
| Landing pages     | Link campaigns to Capell pages and choose primary conversion goals       |
| CTA blocks        | Maintain reusable campaign calls to action                               |
| Conversion goals  | Define page-view, CTA-click, form-submission, and custom goals           |
| Dashboard widgets | Review campaign totals, top campaigns, and top landing pages             |

## What developers get

- Actions for campaign URL building, URL resolution, conversion attribution, conversion recording, and layout installation.
- Data objects for UTM values, CTA actions, attribution snapshots, and dashboard summaries.
- Mosaic widget components for campaign heroes, CTA blocks, and lead forms.
- A page schema extender that applies campaign defaults to Capell pages.
- Listeners for page synchronisation and form-submission conversions.

## Package boundaries

- Mosaic owns layout rendering and widget placement.
- Forms owns form definitions and submissions.
- Analytics owns visits and events when the Analytics package is installed.
- Campaigns owns campaign metadata, conversion goals, conversion records, and campaign reports.

## Reference

- [Campaigns API](docs/campaigns-api.md)
- [Campaigns database](docs/campaigns-database.md)
