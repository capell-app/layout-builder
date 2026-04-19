# capell-app/themes-admin

Filament 4 admin panel integration for the Capell theme system. Provides a
**Theme Settings** page that lets admins choose the active theme and configure
brand colors without leaving the admin UI.

## What's inside

| Class | Purpose |
|---|---|
| `ThemesAdminServiceProvider` | Registers the package, config, and Filament page |
| `ThemeSettingsPage` | Filament `Page` that renders the theme settings form |
| `ThemeSettingsSchema` | Filament schema factory — `Tabs` with active theme select and color pickers |

## Requirements

- PHP 8.2+
- Laravel 10+
- Filament 4.7+ (panel provider already configured in your app)
- `capell-app/themes-core` (pulled in automatically)

## Installation

```bash
composer require capell-app/themes-admin
```

The service provider is auto-discovered. The **Theme** page appears in your
Filament admin under **Settings → Theme**.

## Using the settings page

Navigate to **Settings → Theme** in the Filament admin panel.

- **Active theme** — dropdown to choose Corporate, Agency, or SaaS
- **Primary color** — brand color used for CTAs and highlights
- **Accent color** — secondary brand color

Changes apply immediately on save; no cache clear or deploy needed.

## Extending the schema

To add theme-specific fields, resolve `ThemeSettingsSchema` and call additional
`schema()` or `schema()->tab()` methods before passing it to the page:

```php
// In your theme's ServiceProvider:
use Capell\Themes\Admin\Schemas\ThemeSettingsSchema;
use Filament\Forms\Components\Toggle;

ThemeSettingsSchema::extend(function (array $components): array {
    return array_merge($components, [
        Toggle::make('show_cookie_banner')->label('Show cookie banner'),
    ]);
});
```

## Tests

```bash
php -d memory_limit=-1 vendor/bin/pest packages/themes-admin/tests
```

See [../../TESTING.md](../../TESTING.md) for full testing instructions.

## License

MIT
