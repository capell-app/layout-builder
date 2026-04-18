# Modern Widget Customization Guide

> **The Sovereign Architect Design System**
>
> Modern, admin-friendly, customizable widgets for Capell Mosaic layout builder.
> Built with tonal depth, glassmorphism, and zero-code flexibility.

---

## Quick Start

### 1. Include Design Tokens

In your Blade layout or main CSS file:

```blade
@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/mosaic/css/design-tokens.css') }}">
@endpush
```

Or import in your Tailwind/Vite CSS:

```css
@import 'vendor/mosaic/css/design-tokens.css';
```

### 2. Use a Widget Component

```blade
<x-mosaic::modern.hero-banner
    title="Welcome to Capell"
    subtitle="Create beautiful layouts without code"
    :primaryCta="['label' => 'Get Started', 'url' => route('pages.create')]"
    :customizable="true"
/>
```

---

## Available Widgets

### 1. **Hero Banner** (`modern.hero-banner`)

A full-width hero section with customizable gradient, text, and CTA buttons.

**Props:**

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `title` | string | "Welcome to Capell" | Hero heading |
| `subtitle` | string | "Create beautiful layouts without code" | Subheading |
| `primaryCta` | array | `['label' => 'Get Started', 'url' => '#']` | Primary button |
| `secondaryCta` | array | null | Secondary button (optional) |
| `backgroundImage` | string | null | Background image URL |
| `backgroundGradient` | string | Linear gradient | CSS gradient override |
| `height` | string | 'lg' | 'sm', 'md', 'lg', 'xl' |
| `textAlign` | string | 'center' | 'left', 'center', 'right' |
| `accentColor` | string | 'tertiary' | 'primary', 'secondary', 'tertiary' |
| `customizable` | bool | true | Show admin hints |

**Example:**

```blade
<x-mosaic::modern.hero-banner
    title="Launch Your Project"
    subtitle="Professional layouts, zero complexity"
    :primaryCta="[
        'label' => 'Start Free',
        'url' => '#pricing',
        'icon' => '🚀'
    ]"
    :secondaryCta="[
        'label' => 'View Demo',
        'url' => route('demo')
    ]"
    height="xl"
    textAlign="center"
    accentColor="primary"
/>
```

---

### 2. **Card Grid** (`modern.card-grid`)

Responsive grid of customizable cards. Perfect for feature showcases, portfolios, or product displays.

**Props:**

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `title` | string | "Featured Widgets" | Section heading |
| `description` | string | "Choose from our collection..." | Section description |
| `cards` | array | Sample cards | Array of card objects |
| `columns` | int | 3 | 2, 3, or 4 columns |
| `variant` | string | 'default' | 'default', 'elevated', 'glass' |
| `accentColor` | string | 'primary' | 'primary', 'secondary', 'tertiary' |
| `customizable` | bool | true | Show admin hints |

**Card Object Structure:**

```php
[
    'icon' => '🎨',                           // Optional emoji or icon
    'title' => 'Design System',
    'description' => 'Modern tokens and components',
    'image' => 'https://example.com/image.jpg',  // Optional
    'link' => [
        'label' => 'Learn More',
        'url' => route('docs')
    ]
]
```

**Example:**

```blade
<x-mosaic::modern.card-grid
    title="Why Choose Capell?"
    description="Powerful features designed for content editors"
    :cards="[
        [
            'icon' => '⚡',
            'title' => 'Lightning Fast',
            'description' => 'Optimized for speed and performance',
            'link' => ['label' => 'Read More', 'url' => '#']
        ],
        [
            'icon' => '🎨',
            'title' => 'Fully Customizable',
            'description' => 'Modern design system with unlimited possibilities',
            'link' => ['label' => 'Explore', 'url' => '#']
        ],
        [
            'icon' => '🔧',
            'title' => 'No Coding',
            'description' => 'Drag, drop, and publish. That\'s it.',
            'link' => ['label' => 'Try Now', 'url' => '#']
        ],
    ]"
    columns="3"
    variant="default"
/>
```

---

### 3. **CTA Section** (`modern.cta-section`)

Eye-catching call-to-action section with optional split layout.

**Props:**

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `heading` | string | "Ready to Create..." | Main heading |
| `subheading` | string | "No coding required..." | Secondary text |
| `primaryButton` | array | Sample button | Primary CTA |
| `secondaryButton` | array | null | Secondary button |
| `layout` | string | 'centered' | 'centered' or 'split' |
| `accentColor` | string | 'tertiary' | Color theme |
| `backgroundGradient` | string | Purple gradient | Custom gradient CSS |
| `customizable` | bool | true | Show admin hints |

**Example:**

```blade
<x-mosaic::modern.cta-section
    heading="Transform Your Content Management"
    subheading="Join hundreds of teams using Capell to build amazing websites"
    :primaryButton="[
        'label' => 'Start Free Trial',
        'url' => route('register'),
        'icon' => '🎯'
    ]"
    :secondaryButton="[
        'label' => 'Schedule Demo',
        'url' => route('demo')
    ]"
    layout="split"
    backgroundGradient="linear-gradient(135deg, #7c3aed 0%, #3131c0 100%)"
/>
```

---

## Design Tokens Reference

All design tokens are CSS variables accessible as `var(--mosaic-*)`:

### Colors

```css
--mosaic-primary: #d2bbff;
--mosaic-primary-container: #7c3aed;
--mosaic-secondary: #c0c1ff;
--mosaic-tertiary: #ffb784;           /* Gold accent */
--mosaic-surface: #1b1b20;
--mosaic-on-surface: #e4e1e9;
--mosaic-on-surface-variant: #ccc3d8;
--mosaic-outline-variant: #4a4455;
```

### Spacing

```css
--mosaic-spacing-xs: 0.25rem;
--mosaic-spacing-sm: 0.5rem;
--mosaic-spacing-md: 1rem;
--mosaic-spacing-lg: 1.5rem;
--mosaic-spacing-xl: 2rem;
--mosaic-spacing-2xl: 2.5rem;
```

### Typography

```css
--mosaic-font-headline: 'Space Grotesk', system-ui, sans-serif;
--mosaic-font-body: 'Inter', system-ui, sans-serif;
--mosaic-text-headline-lg: 2rem;
--mosaic-text-body-md: 0.9375rem;
```

### Utilities

```css
--mosaic-radius-md: 0.5rem;
--mosaic-radius-lg: 0.75rem;
--mosaic-transition-base: 200ms ease-in-out;
--mosaic-blur-lg: 16px;
```

---

## CSS Utility Classes

### Backgrounds

```html
<!-- Solid colors -->
<div class="mosaic-bg-base">Background</div>
<div class="mosaic-bg-surface">Surface</div>
<div class="mosaic-bg-container">Container</div>
<div class="mosaic-bg-container-highest">Elevated</div>

<!-- Glassmorphism -->
<div class="mosaic-bg-glass">Frosted glass effect</div>
```

### Text

```html
<p class="mosaic-text-primary">Primary text</p>
<p class="mosaic-text-secondary">Secondary/muted text</p>
<span class="mosaic-text-label">Uppercase label</span>
```

### Buttons

```html
<button class="mosaic-btn mosaic-btn-primary">Primary</button>
<button class="mosaic-btn mosaic-btn-secondary">Secondary</button>
<button class="mosaic-btn mosaic-btn-ghost">Ghost</button>
```

### Cards

```html
<div class="mosaic-card">Elevated card</div>
<div class="mosaic-card active">Selected card with accent</div>
```

### Forms

```html
<input type="text" class="mosaic-input" placeholder="Enter text...">
```

### Badges

```html
<span class="mosaic-badge mosaic-badge-primary">Primary</span>
<span class="mosaic-badge mosaic-badge-success">Success</span>
<span class="mosaic-badge mosaic-badge-error">Error</span>
```

---

## Admin Customization Properties

When rendering widgets with `customizable="true"`, admins see hints about what can be customized:

```blade
✨ Customize: Title, Gradient, CTA buttons in properties panel
```

### Typical Widget Admin Properties:

1. **Content**
   - Heading / Title text
   - Description / Subtitle
   - Button labels and URLs
   - Card content and images

2. **Styling**
   - Accent color (primary/secondary/tertiary)
   - Background gradient or color
   - Layout variant (centered/split/elevated)
   - Column count (for grids)

3. **Advanced**
   - Text alignment
   - Height/size preset
   - Show/hide elements
   - Responsive behavior

---

## Integration with Filament Admin

To make widgets customizable in the Filament admin panel, create a schema:

```php
// In your Widget Schema
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Grid;

Grid::make(2)->schema([
    TextInput::make('data.title')
        ->label('Hero Title')
        ->required(),

    Select::make('data.accentColor')
        ->label('Accent Color')
        ->options([
            'primary' => 'Violet',
            'secondary' => 'Indigo',
            'tertiary' => 'Gold',
        ])
        ->default('tertiary'),

    Select::make('data.height')
        ->label('Height')
        ->options([
            'sm' => 'Small',
            'md' => 'Medium',
            'lg' => 'Large',
            'xl' => 'Extra Large',
        ])
        ->default('lg'),
])
```

---

## Theming & Dark Mode

All widgets automatically support light/dark mode via CSS media queries:

```css
@media (prefers-color-scheme: dark) {
  /* Dark mode (default) */
}

@media (prefers-color-scheme: light) {
  /* Light mode */
}
```

Users with light mode preference see lighter surfaces and adjusted text colors automatically.

---

## Performance Tips

1. **Lazy load images:** Use `loading="lazy"` on card images
2. **Optimize gradients:** Pre-compute gradients in design tokens
3. **Minimize re-renders:** Pass props as readonly when possible
4. **Use CSS variables:** Leverage custom properties for theming
5. **Responsive images:** Serve appropriate image sizes per breakpoint

---

## Accessibility (WCAG 2.1 AA)

All widgets include:

- ✅ Semantic HTML (`<section>`, `<h1>`, `<button>`, `<a>`)
- ✅ Proper color contrast ratios (WCAG AA)
- ✅ Focus-visible states on interactive elements
- ✅ Alt text for images
- ✅ Keyboard navigation support
- ✅ ARIA labels where needed

---

## Browser Support

- Chrome/Edge: Latest 2 versions
- Firefox: Latest 2 versions
- Safari: Latest 2 versions
- Mobile: iOS 14+, Android 12+

---

## Troubleshooting

### Styles Not Applying?

Ensure design tokens CSS is loaded before component CSS:

```blade
@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/mosaic/css/design-tokens.css') }}">
@endpush
```

### Colors Look Wrong?

Check `prefers-color-scheme` media query. Some systems force light/dark mode.

### Components Not Rendering?

Verify component paths: `<x-mosaic::modern.hero-banner />`

Check namespace in `config/view.php`:

```php
'namespaces' => [
    'mosaic' => resource_path('views/vendor/mosaic'),
],
```

---

## Next Steps

1. ✅ **Copy design tokens** to your CSS pipeline
2. ✅ **Test widgets** in the demo workbench
3. ✅ **Create schemas** for Filament admin customization
4. ✅ **Document** widget properties for your team
5. ✅ **Build workflows** using widget components

---

## Questions?

Refer to the main Capell docs: [packages/core/docs/extending-capell.md](../core/docs/extending-capell.md)
