# Installing the Capell Agency theme

## Requirements

- PHP 8.2+
- Laravel 10+
- Capell CMS (`capell-app/capell` ^4.0)
- Filament 4 (optional — needed only for the admin schema UI)
- Mosaic (optional — unlocks the visual layout editor)

## Install

```bash
composer require capell-app/capell-theme-agency
```

The Laravel package discovery mechanism registers
`Capell\Themes\Agency\AgencyThemeServiceProvider` automatically.

## Register the theme row

```bash
php artisan migrate            # runs the bundled seed migration
php artisan agency:install     # idempotent — safe to re-run
```

Add `--seed-layouts` to create home / work / contact Mosaic layouts when
Mosaic is installed.

## Publish resources (optional)

```bash
php artisan vendor:publish --tag=capell-agency-views
php artisan vendor:publish --tag=capell-agency-css
# or both at once
php artisan vendor:publish --tag=capell-agency
```

Published files live in `resources/vendor/capell-themes/agency/`.

## Enable in Capell admin

1. Open **Settings → Theme**
2. Choose **Agency** from the active-theme dropdown
3. Save — the theme applies immediately

## Uninstall

```bash
php artisan migrate:rollback --path=vendor/capell-app/capell-theme-agency/database/migrations
composer remove capell-app/capell-theme-agency
```
