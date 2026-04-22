# Modern Widgets Reference Guide

Complete documentation for all 13 production-ready widgets in Capell Mosaic.

## Table of Contents

1. [Hero Banner](#hero-banner)
2. [Card Grid](#card-grid)
3. [Feature List](#feature-list)
4. [Stats Section](#stats-section)
5. [Testimonials](#testimonials)
6. [Team Members](#team-members)
7. [Pricing Table](#pricing-table)
8. [FAQ Section](#faq-section)
9. [Image Gallery](#image-gallery)
10. [Alternating Content](#alternating-content)
11. [Process Steps](#process-steps)
12. [CTA Section](#cta-section)

---

## Hero Banner

Full-width hero section with customizable backgrounds, CTA buttons, and parallax effects.

### Props

| Prop               | Type    | Default                                               | Description                                      |
| ------------------ | ------- | ----------------------------------------------------- | ------------------------------------------------ |
| title              | string  | "Welcome to Capell"                                   | Hero main heading                                |
| subtitle           | string  | ""                                                    | Secondary text/description                       |
| primaryCta         | array   | `['label' => 'Get Started', 'url' => '#']`            | Primary button config                            |
| secondaryCta       | array   | null                                                  | Secondary button config                          |
| backgroundImage    | string  | null                                                  | Image URL for background                         |
| backgroundGradient | string  | `'linear-gradient(135deg, #7c3aed 0%, #3131c0 100%)'` | CSS gradient override                            |
| videoUrl           | string  | null                                                  | MP4 video URL for background (overrides image)   |
| height             | string  | 'lg'                                                  | Height preset: 'sm', 'md', 'lg', 'xl'            |
| textAlign          | string  | 'center'                                              | Text alignment: 'left', 'center', 'right'        |
| accentColor        | string  | 'tertiary'                                            | Button color: 'primary', 'secondary', 'tertiary' |
| parallax           | boolean | false                                                 | Enable parallax scroll effect                    |
| customizable       | boolean | true                                                  | Show admin customize hints                       |

### Example

```blade
<x-mosaic::modern.hero-banner
    title="Transform Your Content"
    subtitle="Build beautiful pages without code"
    height="lg"
    videoUrl="https://example.com/hero.mp4"
    :parallax="true"
/>
```

---

## Card Grid

Responsive grid of cards with icons, images, badges, and hover effects.

### Props

| Prop         | Type    | Default            | Description                                |
| ------------ | ------- | ------------------ | ------------------------------------------ |
| title        | string  | "Featured Widgets" | Section heading                            |
| description  | string  | ""                 | Section description                        |
| cards        | array   | []                 | Array of card objects                      |
| columns      | int     | 3                  | Grid columns: 2, 3, or 4                   |
| variant      | string  | 'default'          | Card style: 'default', 'elevated', 'glass' |
| accentColor  | string  | 'primary'          | Color: 'primary', 'secondary', 'tertiary'  |
| hoverEffect  | string  | 'scale'            | Hover effect: 'scale', 'shadow', 'lift'    |
| customizable | boolean | true               | Show admin hints                           |

### Card Object Properties

```php
[
    'icon' => '🎨',                    // Emoji icon
    'title' => 'Design System',        // Card title
    'description' => 'Modern tokens',  // Card description
    'image' => null,                   // Optional image URL
    'badge' => 'Popular',              // Optional badge text
    'link' => [                        // Optional CTA button
        'label' => 'Learn More',
        'url' => '#'
    ]
]
```

### Example

```blade
<x-mosaic::modern.card-grid
    title="Our Services"
    :cards="$services"
    columns="3"
    hoverEffect="lift"
/>
```

---

## Feature List

Feature showcase with animation system and flexible layouts.

### Props

| Prop         | Type    | Default             | Description                                        |
| ------------ | ------- | ------------------- | -------------------------------------------------- |
| title        | string  | "Powerful Features" | Section heading                                    |
| description  | string  | ""                  | Section description                                |
| features     | array   | []                  | Array of feature objects                           |
| layout       | string  | 'grid'              | Layout: 'grid' or 'vertical'                       |
| columns      | int     | 3                   | Grid columns (if grid layout): 2, 3, or 4          |
| animation    | string  | 'fade-in'           | Animation: 'fade-in', 'slide-up', 'zoom', 'bounce' |
| customizable | boolean | true                | Show admin hints                                   |

### Feature Object Properties

```php
[
    'icon' => '⚡',                    // Emoji icon
    'title' => 'Lightning Fast',       // Feature title
    'description' => 'Fast rendering'  // Feature description
]
```

### Example

```blade
<x-mosaic::modern.feature-list
    title="Why Choose Us?"
    :features="$features"
    animation="slide-up"
    layout="grid"
/>
```

---

## Stats Section

Display key metrics with customizable layout.

### Props

| Prop         | Type    | Default          | Description                        |
| ------------ | ------- | ---------------- | ---------------------------------- |
| title        | string  | "By The Numbers" | Section heading                    |
| description  | string  | ""               | Section description                |
| stats        | array   | []               | Array of stat objects              |
| layout       | string  | 'horizontal'     | Layout: 'horizontal' or 'vertical' |
| customizable | boolean | true             | Show admin hints                   |

### Stat Object Properties

```php
[
    'label' => 'Users',        // Metric label
    'value' => '1000+',        // Metric value
    'icon' => '👥'             // Optional emoji icon
]
```

### Example

```blade
<x-mosaic::modern.stats-section
    title="Our Impact"
    :stats="$stats"
    layout="horizontal"
/>
```

---

## Testimonials

Customer testimonials with grid or carousel display modes.

### Props

| Prop         | Type    | Default              | Description                   |
| ------------ | ------- | -------------------- | ----------------------------- |
| title        | string  | "What Customers Say" | Section heading               |
| testimonials | array   | []                   | Array of testimonial objects  |
| columns      | int     | 2                    | Grid columns: 1, 2, or 3      |
| displayMode  | string  | 'grid'               | Display: 'grid' or 'carousel' |
| customizable | boolean | true                 | Show admin hints              |

### Testimonial Object Properties

```php
[
    'quote' => 'Amazing experience!',   // Testimonial text
    'author' => 'Sarah Johnson',        // Author name
    'role' => 'Marketing Manager',      // Author role/title
    'avatar' => '👩‍💼'                   // Emoji avatar
]
```

### Example

```blade
<x-mosaic::modern.testimonials
    title="Client Success Stories"
    :testimonials="$testimonials"
    displayMode="carousel"
/>
```

---

## Team Members

Team showcase with social media links and role badges.

### Props

| Prop         | Type    | Default    | Description              |
| ------------ | ------- | ---------- | ------------------------ |
| title        | string  | "Our Team" | Section heading          |
| members      | array   | []         | Array of member objects  |
| columns      | int     | 3          | Grid columns: 2, 3, or 4 |
| customizable | boolean | true       | Show admin hints         |

### Member Object Properties

```php
[
    'name' => 'Alex Morgan',                    // Member name
    'role' => 'Product Lead',                   // Job title
    'avatar' => '👨‍💻',                        // Emoji avatar
    'bio' => 'Designer with 5+ years',          // Short bio
    'tags' => ['Design', 'Leadership'],         // Role tags
    'social' => [                               // Social media links
        'twitter' => 'https://twitter.com/alex',
        'linkedin' => 'https://linkedin.com/in/alex',
        'github' => 'https://github.com/alex'
    ]
]
```

### Example

```blade
<x-mosaic::modern.team-members
    title="Meet Our Team"
    :members="$team"
    columns="4"
/>
```

---

## Pricing Table

Pricing plans with annual/monthly billing toggle.

### Props

| Prop           | Type    | Default                       | Description                          |
| -------------- | ------- | ----------------------------- | ------------------------------------ |
| title          | string  | "Simple, Transparent Pricing" | Section heading                      |
| plans          | array   | []                            | Array of plan objects                |
| currency       | string  | '$'                           | Currency symbol                      |
| billingOptions | string  | 'monthly'                     | Billing: 'monthly', 'annual', 'both' |
| customizable   | boolean | true                          | Show admin hints                     |

### Plan Object Properties

```php
[
    'name' => 'Starter',                    // Plan name
    'price' => '29',                        // Monthly price
    'priceAnnual' => '290',                 // Annual price
    'description' => 'For individuals',     // Plan description
    'features' => [                         // Feature list
        'Up to 5 projects',
        '1 GB storage',
        'Email support'
    ],
    'featured' => false,                    // Highlight this plan
    'cta' => [                              // CTA button
        'label' => 'Get Started',
        'url' => '#'
    ]
]
```

### Example

```blade
<x-mosaic::modern.pricing-table
    title="Choose Your Plan"
    :plans="$plans"
    billingOptions="both"
    currency="$"
/>
```

---

## FAQ Section

Accordion FAQ with category/tab filtering.

### Props

| Prop         | Type    | Default                      | Description                 |
| ------------ | ------- | ---------------------------- | --------------------------- |
| title        | string  | "Frequently Asked Questions" | Section heading             |
| faqs         | array   | []                           | Array of FAQ objects        |
| categories   | array   | []                           | Category list for filtering |
| customizable | boolean | true                         | Show admin hints            |

### FAQ Object Properties

```php
[
    'question' => 'How do I get started?',       // Question
    'answer' => 'Follow our documentation...',   // Answer
    'category' => 'Getting Started'              // Category for filtering
]
```

### Example

```blade
<x-mosaic::modern.faq-section
    title="Common Questions"
    :faqs="$faqs"
    :categories="['Getting Started', 'Features', 'Pricing']"
/>
```

---

## Image Gallery

Responsive image gallery with hover captions.

### Props

| Prop         | Type    | Default         | Description                 |
| ------------ | ------- | --------------- | --------------------------- |
| title        | string  | "Photo Gallery" | Section heading             |
| description  | string  | ""              | Section description         |
| images       | array   | []              | Array of image objects      |
| columns      | int     | 3               | Grid columns: 2, 3, or 4    |
| layout       | string  | 'grid'          | Layout: 'grid' or 'masonry' |
| customizable | boolean | true            | Show admin hints            |

### Image Object Properties

```php
[
    'src' => 'https://example.com/photo.jpg',  // Image URL
    'caption' => 'Beautiful sunset'              // Hover caption
]
```

### Example

```blade
<x-mosaic::modern.image-gallery
    title="Our Work"
    :images="$gallery"
    columns="3"
/>
```

---

## Alternating Content

Two-column layout with alternating text and image positioning.

### Props

| Prop         | Type    | Default | Description              |
| ------------ | ------- | ------- | ------------------------ |
| title        | string  | ""      | Section heading          |
| sections     | array   | []      | Array of section objects |
| customizable | boolean | true    | Show admin hints         |

### Section Object Properties

```php
[
    'title' => 'Step One',                      // Section title
    'description' => 'First step...',           // Section content
    'icon' => '1️⃣',                            // Emoji icon
    'image' => null                             // Optional image URL
]
```

### Example

```blade
<x-mosaic::modern.alternating-content :sections="$steps" />
```

---

## Process Steps

Timeline visualization in horizontal or vertical layout.

### Props

| Prop         | Type    | Default       | Description                        |
| ------------ | ------- | ------------- | ---------------------------------- |
| title        | string  | "Our Process" | Section heading                    |
| description  | string  | ""            | Section description                |
| steps        | array   | []            | Array of step objects              |
| layout       | string  | 'horizontal'  | Layout: 'horizontal' or 'vertical' |
| customizable | boolean | true          | Show admin hints                   |

### Step Object Properties

```php
[
    'title' => 'Discovery',             // Step title
    'description' => 'Learn about...',  // Step description
    'icon' => '🔍'                      // Emoji icon
]
```

### Example

```blade
<x-mosaic::modern.process-steps
    title="How It Works"
    :steps="$process"
    layout="horizontal"
/>
```

---

## CTA Section

Call-to-action section with custom layouts and buttons.

### Props

| Prop               | Type    | Default                 | Description                   |
| ------------------ | ------- | ----------------------- | ----------------------------- |
| title              | string  | "Ready to Get Started?" | Section heading               |
| description        | string  | ""                      | Section description           |
| primaryButton      | array   | []                      | Primary CTA button config     |
| secondaryButton    | array   | null                    | Secondary button config       |
| backgroundGradient | string  | ""                      | CSS gradient background       |
| layout             | string  | 'centered'              | Layout: 'centered' or 'split' |
| customizable       | boolean | true                    | Show admin hints              |

### Example

```blade
<x-mosaic::modern.cta-section
    title="Launch Your Site Today"
    :primaryButton="['label' => 'Get Started', 'url' => route('signup')]"
/>
```

---

## Filament Admin Integration

All widgets include Filament schema files for zero-code customization. Schemas are located in:

```
src/Filament/Schemas/Widgets/
├── ModernHeroBannerSchema.php
├── ModernCardGridSchema.php
├── ModernFeatureListSchema.php
├── ModernStatsSectionSchema.php
├── ModernTestimonialsSchema.php
├── ModernTeamMembersSchema.php
├── ModernPricingTableSchema.php
├── ModernImageGallerySchema.php
├── ModernAlternatingContentSchema.php
├── ModernProcessStepsSchema.php
├── ModernFaqSchema.php
└── ModernCTASectionSchema.php
```

Each schema provides:

- TextInput fields for content
- Select dropdowns for layout options
- Repeaters for dynamic arrays (cards, features, etc.)
- Toggle switches for feature flags
- ColorPicker for color customization
- Helper text explaining each option

---

## Design Token Reference

All widgets use CSS custom properties from `design-tokens.css`:

### Colors

- `--mosaic-primary`: Violet (#d2bbff)
- `--mosaic-primary-container`: Purple (#7c3aed)
- `--mosaic-secondary`: Indigo (#c0c1ff)
- `--mosaic-tertiary`: Gold (#ffb784)
- `--mosaic-surface`: Dark background (#1b1b20)
- `--mosaic-surface-container`: Container background (#262631)
- `--mosaic-on-surface`: Light text (#e4e1e9)
- `--mosaic-on-surface-variant`: Muted text (#adacb4)

### Typography

- `--mosaic-font-headline`: Space Grotesk
- `--mosaic-font-body`: Inter
- `--mosaic-font-mono`: Fira Code

### Spacing

- `--mosaic-space-xs`: 0.25rem
- `--mosaic-space-sm`: 0.5rem
- `--mosaic-space-md`: 1rem
- `--mosaic-space-lg`: 1.5rem
- `--mosaic-space-xl`: 2rem

---

## Accessibility Notes

All widgets are WCAG 2.1 AA compliant:

- Semantic HTML structure
- Proper heading hierarchy
- Color contrast ratios meet standards
- Keyboard navigation support
- ARIA labels where needed
- Focus-visible states
- No color-only information

---

## Responsive Behavior

- **Mobile**: 1 column layouts, reduced padding
- **Tablet** (768px+): 2-3 column layouts
- **Desktop** (1024px+): Full-width layouts, optimal spacing

All widgets adapt automatically to screen size.

---

## Performance Tips

1. **Images**: Use optimized formats (WebP with fallback)
2. **Videos**: Host on CDN, use MP4 format
3. **Gradients**: CSS gradients are hardware-accelerated
4. **Animations**: Use CSS transforms for best performance
5. **Lazy Loading**: Consider lazy-loading images in galleries

---

## Version History

- **v2.0** (April 2026): 13 widgets with advanced features
- **v1.0** (April 2026): Initial 3 widgets release

---

**Last Updated:** April 18, 2026
**Status:** Production Ready ✅
**Questions?** See WIDGET_CUSTOMIZATION_GUIDE.md for more examples
