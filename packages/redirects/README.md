# Capell Redirects

**Product group:** Capell Foundation
**Tier:** Free

Redirects manages manual 301/302 redirects, automatic page-slug redirects, and CSV import/export for Capell.

The package keeps canonical storage in Core's `page_urls` table and adds package-owned behaviour around redirect validation, admin management, and frontend resolution.

## When to install it

Install Redirects when editors need a redirect manager or when slug changes should create redirects automatically.

## Quick install

```bash
composer require capell-app/redirects
php artisan migrate
php artisan optimize:clear
```

## What appears in the admin

| Area      | What editors can do                                       |
| --------- | --------------------------------------------------------- |
| Redirects | Create, edit, filter, import, and export manual redirects |

## What developers get

- `RedirectResolver` and `RedirectRecorder` contracts.
- Automatic redirect creation when page URLs change.
- Validation for duplicates, self-redirects, loops, and redirect chains.
- Filament importer/exporter classes for CSV workflows.
- Config toggles for automatic redirects.

## Reference

See [Redirect Manager reference](docs/redirects.md) for storage, validation, admin, frontend, and configuration details.
