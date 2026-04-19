# Customizing the Corporate theme

The theme is driven by CSS custom properties and a few settings values —
no rebuild required for most changes.

## Colors

Override the palette by setting CSS variables on `<html>`:

```html
<html style="--color-primary:#0e1b4c; --color-accent:#ffd166;"></html>
```

Or edit `resources/vendor/capell-themes/corporate/css/theme.css` after
publishing.

## Typography

Two variables — `--font-headline` and `--font-body`. Defaults are
Playfair Display and Inter. Drop any web font and update the variable.

## Spacing presets

Set `data-spacing` on `<body>`:

- `compact`
- `balanced` (default)
- `spacious`

Each preset changes the `--section-y` rhythm between sections.

## Dark mode

`<html data-theme="dark">` forces dark. Leave it off and the theme follows
`prefers-color-scheme`. The bundled `<x-corporate::dark-mode-toggle />`
persists a choice in `localStorage`.

## Swapping components

Publish views with `php artisan vendor:publish --tag=capell-corporate-views`
and edit the Blade files. The layout exposes slots for `$header`, `$footer`,
`$head`, so you can replace any landmark without forking the whole layout.

## Widgets

Widget classes live in `src/Widgets`. Each declares a `$fields` array that
Mosaic / the admin can render. Override a widget by subclassing and
registering your subclass in your own service provider.
