# Capell Mosaic - Architectural Precision Widgets

Premium layout widgets implementing the Architectural Precision design system with blueprint-style technical aesthetics.

## Design System Overview

**Colors:**

- **Primary:** `#F2CA50` - Gold for CTAs and highlights
- **Secondary:** `#C6C6CF` - Zinc/silver for structural elements
- **Background:** `#131313` - Obsidian dark foundation
- **Surfaces:** Tonal layering with no drop shadows

**Key Principles:**

- **Zero-Radius Mandate:** All corners are sharp (0px border-radius)
- **Ghost Borders:** 1px borders at 20% opacity using `--mosaic-outline-variant`
- **Coordinate Markers:** Technical labels like `[REF: 001-A]` in all sections
- **Grid Backgrounds:** 5-10% opacity grid pattern as visual paper
- **No Dividers:** Use tonal layering instead of lines

---

## Available Widgets

### 1. Hero Banner

Premium headline section with CTA and full-width grid background.

**Livewire Component:** `Capell\Mosaic\Livewire\Widget\HeroBanner`

**Configuration:**

```php
[
    'title' => 'Your Headline Here',
    'subtitle' => 'Supporting text explaining the value proposition',
    'cta_text' => 'Get Started',
    'cta_url' => '/signup',
    'background_image' => 'url/to/image.jpg', // optional
]
```

**Features:**

- Tight tracking headline (-4% letter-spacing)
- Coordinate marker `[REF: 001-A]`
- Gold gradient CTA button (sharp edges)
- Grid background pattern at 5% opacity
- Bottom accent line with gold gradient

**Tailwind Classes:** `font-mosaic-headline`, `text-mosaic-display-lg`, `mosaic-btn-primary`

---

### 2. Card Grid

Responsive grid of cards with titles, descriptions, and optional images.

**Livewire Component:** `Capell\Mosaic\Livewire\Widget\CardGrid`

**Configuration:**

```php
[
    'columns' => 3, // 1, 2, 3, or 4
    'cards' => [
        [
            'title' => 'Card Title',
            'description' => 'Card description text',
            'image' => 'url/to/image.jpg', // optional
            'link_text' => 'Learn More',
            'link_url' => '/details',
        ],
        // ... more cards
    ]
]
```

**Features:**

- Responsive grid (1 col mobile, N cols desktop)
- 1px ghost borders on top
- Coordinate numbering `[01]`, `[02]`, etc.
- Tonal layer on hover (surface-container-high)
- Gold text for card numbers

**Tailwind Classes:** `mosaic-card`, `grid gap-mosaic-lg`, `border-t border-mosaic-outline-variant`

---

### 3. Feature List

Vertical list of features with icons and descriptions.

**Livewire Component:** `Capell\Mosaic\Livewire\Widget\FeatureList`

**Configuration:**

```php
[
    'layout' => 'vertical', // or 'horizontal'
    'features' => [
        [
            'icon' => '✓', // emoji or icon symbol
            'title' => 'Feature Name',
            'description' => 'Feature explanation text',
        ],
        // ... more features
    ]
]
```

**Features:**

- No dividers between items (tonal layering)
- Gold square icon container (zero-radius)
- Feature numbering `[01]`, `[02]`, etc.
- Ghost top borders
- Hover state with surface layer shift

**Tailwind Classes:** `flex gap-mosaic-lg`, `bg-mosaic-surface-container`, `border-t border-mosaic-outline-variant`

---

### 4. CTA Section

Centered call-to-action with headline, description, and dual buttons.

**Livewire Component:** `Capell\Mosaic\Livewire\Widget\CTASection`

**Configuration:**

```php
[
    'headline' => 'Ready to get started?',
    'description' => 'Supporting description text',
    'primary_button_text' => 'Get Started',
    'primary_button_url' => '/signup',
    'secondary_button_text' => 'Learn More', // optional
    'secondary_button_url' => '/docs',
]
```

**Features:**

- Centered layout with max-width container
- Grid background pattern
- Top and bottom accent lines (gold gradient)
- Coordinate marker `[CTA: 004-A]`
- Primary button (gold gradient), Secondary button (ghost style)

**Tailwind Classes:** `text-center`, `mosaic-btn-primary`, `mosaic-btn-secondary`

---

### 5. Image Gallery

Grid or carousel image gallery with blueprint-style annotations.

**Livewire Component:** `Capell\Mosaic\Livewire\Widget\ImageGallery`

**Configuration:**

```php
[
    'layout' => 'grid', // or 'carousel'
    'columns' => 3, // for grid layout
    'lightbox' => true,
    'images' => [
        [
            'url' => 'image-url.jpg',
            'alt' => 'Alt text',
        ],
        // ... more images
    ]
]
```

**Features:**

- **Grid:** Responsive columns with hover scale effect
- **Carousel:** Swiper.js powered carousel with pagination
- 1px ghost borders (zero-radius)
- Coordinate markers appear on hover
- Gold gradient text on hover overlay

**Tailwind Classes:** `border border-mosaic-outline-variant`, `group-hover:scale-105`, `transition-transform duration-mosaic-base`

---

### 6. Form Section

Multi-field form with technical styling and various input types.

**Livewire Component:** `Capell\Mosaic\Livewire\Widget\FormSection`

**Configuration:**

```php
[
    'title' => 'Contact Us',
    'description' => 'Get in touch with our team',
    'submit_text' => 'Send Message',
    'submit_action' => '/contact',
    'fields' => [
        [
            'name' => 'email',
            'label' => 'Email Address',
            'type' => 'email',
            'placeholder' => 'you@example.com',
            'required' => true,
            'help_text' => 'We\'ll never share your email',
        ],
        [
            'name' => 'message',
            'label' => 'Message',
            'type' => 'textarea',
            'rows' => 4,
            'required' => true,
        ],
        [
            'name' => 'agree',
            'label' => 'I agree to the terms',
            'type' => 'checkbox',
            'checkbox_label' => 'Yes, I agree',
            'required' => true,
        ],
        // ... more fields
    ]
]
```

**Field Types:** `text`, `email`, `number`, `textarea`, `select`, `checkbox`

**Features:**

- Technical field IDs as monospace labels `[field-name]`
- Ghost borders with zero-radius
- Focus state with gold glow
- Full-width layout
- Submit button with gold gradient
- Bottom divider before button

**Tailwind Classes:** `mosaic-input`, `w-full`, `border border-mosaic-outline-variant`

---

## CSS Classes Reference

### Color Classes

```
bg-mosaic-background
bg-mosaic-surface
bg-mosaic-surface-container
bg-mosaic-surface-container-high
bg-mosaic-surface-container-highest

text-mosaic-on-surface
text-mosaic-on-surface-variant
text-mosaic-primary
text-mosaic-secondary

border-mosaic-outline-variant
```

### Typography Classes

```
font-mosaic-headline
font-mosaic-body
font-mosaic-mono

text-mosaic-display-lg/md
text-mosaic-headline-lg/md/sm
text-mosaic-title-lg/md/sm
text-mosaic-body-lg/md/sm
text-mosaic-label-lg/md/sm
```

### Spacing Classes

```
gap-mosaic-xs/sm/md/lg/xl/2xl/3xl
p-mosaic-lg, pt-mosaic-lg, px-mosaic-lg, etc.
mt-mosaic-lg, mb-mosaic-lg, etc.
```

### Button Classes

```
mosaic-btn
mosaic-btn-primary
mosaic-btn-secondary
mosaic-btn-ghost
```

### Card Classes

```
mosaic-card          # Standard card styling
.mosaic-card:hover   # Tonal layer shift
.mosaic-card.active  # Gold border variant
```

---

## Design Tokens

All color and sizing tokens are defined in CSS custom properties:

```css
/* Colors */
--mosaic-primary:
    #f2ca50 --mosaic-primary-container: #d4af37 --mosaic-secondary: #c6c6cf
        --mosaic-background: #131313 --mosaic-outline-variant: #4d4635
        /* Spacing */ --mosaic-spacing-md: 1rem --mosaic-spacing-lg: 1.5rem
        --mosaic-spacing-xl: 2rem /* Typography */
        --mosaic-font-headline: 'Space Grotesk',
    sans-serif --mosaic-font-body: 'Inter',
    sans-serif --mosaic-text-display-lg: 3.5rem
        --mosaic-text-headline-sm: 1.5rem /* Transitions */
        --mosaic-transition-base: 200ms ease-in-out /* Glows & Shadows */
        --mosaic-glow-primary: rgba(242, 202, 80, 0.08)
        --mosaic-shadow-ambient: rgba(0, 0, 0, 0.5);
```

---

## Implementation Guide

### 1. Register Widget in Blade

```blade
<livewire:hero-banner
    :containerKey="'hero_1'"
    :widgetData="[
    'title' => 'Welcome',
    'subtitle' => 'Your subtitle here',
    'cta_text' => 'Get Started',
    'cta_url' => '/signup'
  ]"
/>
```

### 2. Use Tailwind Classes

All widgets use the `mosaic-` prefixed Tailwind classes defined in `tailwind-config.js`:

```html
<div class="bg-mosaic-surface text-mosaic-on-surface p-mosaic-lg">
    <h2 class="font-mosaic-headline text-mosaic-headline-md">Title</h2>
</div>
```

### 3. Customize Colors

Override CSS custom properties in your stylesheet:

```css
:root {
    --mosaic-primary: #ffd700; /* change primary color */
    --mosaic-secondary: #c0c0c0; /* change secondary */
}
```

---

## Browser Support

- Chrome/Edge: Full support
- Firefox: Full support
- Safari: Full support (iOS 13+)
- CSS Custom Properties: IE11 not supported

---

## Notes

- All widgets use **zero-radius borders** (sharp edges) per the Architectural Precision mandate
- **No drop shadows** - use tonal layering for depth
- **Ghost borders** are `1px solid var(--mosaic-outline-variant)` at 20% opacity
- Coordinate markers are technical labels for a premium, blueprint-like feel
- Grid backgrounds are optional but recommended for visual authenticity
- All fonts use Space Grotesk for headlines and Inter for body text
