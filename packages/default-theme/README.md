# Capell Default Theme

**Product group:** Capell Foundation
**Tier:** Free

Default Theme ships the baseline frontend theme infrastructure for Capell: Tailwind asset generation, Blade helpers, URL generation, default settings, and media rendering support.

## When to install it

Install Default Theme when you want Capell's standard frontend pipeline or when you are building a custom theme that should start from the default conventions.

## Quick install

```bash
composer require capell-app/default-theme
php artisan capell:frontend-tailwind-assets
php artisan capell:static-site
```

## What appears in the admin

| Area            | What administrators can do                                         |
| --------------- | ------------------------------------------------------------------ |
| Theme settings  | Configure default theme values when the admin provider is enabled  |
| Frontend assets | Regenerate Tailwind directive files after package or color changes |

## What developers get

- `capell:frontend-tailwind-assets` command.
- Tailwind asset registry and generator.
- Blade directives and frontend media helpers.
- Default theme settings and settings migration provider.
