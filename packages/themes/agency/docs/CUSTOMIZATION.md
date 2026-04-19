# Customizing the Agency theme

The Agency theme is driven entirely by CSS custom properties and Blade
component props. You can re-skin it without touching PHP.

## Design tokens

The theme ships with these CSS variables (defined in
`resources/css/theme.css`):

| Token                  | Default                   | Purpose                                  |
| ---------------------- | ------------------------- | ---------------------------------------- |
| `--color-primary`      | `#ff5a7e`                 | Coral/rose — CTAs, hover accents         |
| `--color-accent`       | `#3b82f6`                 | Electric blue — filter pills, highlights |
| `--color-bg`           | `#ffffff`                 | Main background (light mode)             |
| `--color-fg`           | `#111014`                 | Primary text color                       |
| `--color-surface-dark` | `#0b0a0f`                 | Hero / footer dark surfaces              |
| `--font-headline`      | `Sora, Inter, system-ui…` | Display type                             |
| `--font-body`          | `Inter, system-ui…`       | Body copy                                |
| `--section-y-spacious` | `8rem`                    | Vertical spacing for "spacious" preset   |

Override any of these inline on `<html>` or in your own stylesheet:

```css
:root {
    --color-primary: #ff3b6e;
    --font-headline: 'Satoshi', system-ui, sans-serif;
}
```

## Dark mode

Dark mode is toggled by the `data-theme="dark"` attribute on `<html>`
(handled by `<x-agency::dark-mode-toggle />`). The theme also respects
`prefers-color-scheme: dark` when no attribute is set.

## Spacing presets

Set `data-spacing="compact|balanced|spacious"` on `<body>` to change
global section padding. Agency defaults to `spacious`.

## Swapping components

Every component accepts a `$slot` so you can override markup while keeping
the container:

```blade
<x-agency::hero-statement statement="Make them look.">
    <span class="text-[var(--color-primary)]">Custom</span>
    heading.
</x-agency::hero-statement>
```

## Widget field overrides

Each widget exposes a `fields` array (see
`src/Widgets/*.php`) used by the Mosaic admin UI and by `defaults()` in
tests. Extend a widget and re-declare `$fields` to customize.
