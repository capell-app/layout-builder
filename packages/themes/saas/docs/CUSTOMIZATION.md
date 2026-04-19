# Customizing the SaaS theme

The theme is driven by CSS custom properties, Blade components with `@props`,
and a `SaasThemeSettings` DTO. You can customize at three layers.

## 1. CSS tokens

All colors, spacing, radii and shadows are declared at the top of
`resources/css/theme.css` and published to your app via the
`capell-saas-css` tag. Override any of these at runtime by setting inline
styles on `<html>` (for example from a server-rendered partial):

```html
<html style="--color-primary:#8b5cf6; --color-accent:#f472b6;"></html>
```

### Default palette

| Token              | Value     | Purpose                        |
| ------------------ | --------- | ------------------------------ |
| `--color-primary`  | `#6366f1` | Electric indigo — main brand   |
| `--color-accent`   | `#10b981` | Vibrant emerald — CTAs, checks |
| `--color-bg`       | `#ffffff` | Light surface                  |
| `--color-bg-muted` | `#f9fafb` | Section backgrounds            |
| `--color-fg`       | `#0f172a` | Body text                      |
| `--gradient-hero`  | (linear)  | Hero headline gradient         |

Dark-mode values ship inside `[data-theme='dark']` and `@media (prefers-color-scheme: dark)`.

## 2. Blade components

Override a component by publishing the views and editing in place:

```bash
php artisan vendor:publish --tag=capell-saas-views
```

All components use `@props` so you can pass data straight from your
controller or Mosaic layout. See `docs/COMPONENTS.md` for prop signatures.

## 3. SaasThemeSettings DTO

`Capell\Themes\Saas\Data\SaasThemeSettings` extends the shared
`ThemeSettings` with SaaS-specific toggles:

```php
$settings = new SaasThemeSettings(
    primary_color: '#8b5cf6',
    accent_color: '#f472b6',
    product_name: 'Acme',
    product_description: 'The calmest inbox on the internet.',
    social_twitter: 'https://twitter.com/acme',
    pricing_cycle_default: 'annual',
);
```

Inject the DTO into your layout/composer to drive structured data,
footer tagline and pricing cycle defaults from a single source.
