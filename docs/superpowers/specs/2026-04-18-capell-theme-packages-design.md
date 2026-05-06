---
title: Capell Modern Theme Packages Design
date: 2026-04-18
status: proposed
version: 1.0
---

# Capell Modern Theme Packages Design Specification

## Executive Summary

Create three modern, full-featured theme packages for Capell CMS that serve as foundational starter templates for developers building client websites. Each theme demonstrates a distinct design philosophy (minimalist corporate, creative agency, SaaS) while sharing a unified admin settings interface. Themes work as standalone Blade-based templates and optionally integrate with LayoutBuilder for visual layout editing.

**Scope:** Three independently installable Composer packages in the monorepo (`capell-app/capell-theme-corporate`, `capell-app/capell-theme-agency`, `capell-app/capell-theme-saas`), each fully self-contained with no shared component duplication.

---

## 1. Goals & Success Criteria

### Goals

- Provide developers with production-ready starter themes that reduce initial setup time
- Showcase Capell's capabilities (multi-language, publishing-studio, widgets, caching) through well-designed examples
- Demonstrate best practices in Blade structure, Tailwind organization, and component design
- Enable seamless integration with LayoutBuilder when installed, without requiring it
- Establish a pattern for future theme contributions

### Success Criteria

✅ All three themes installable independently via Composer
✅ Each theme includes 8 core components (hero, features, pricing, testimonials, blog, FAQ, contact, footer)
✅ All themes respond to unified admin settings (no per-theme schema duplication)
✅ Components work as Blade templates AND LayoutBuilder widgets (when layout-builder installed)
✅ 80%+ test coverage (Pest tests for Actions, Blade rendering, widget registration)
✅ Documentation clear enough for developers to customize without needing to understand entire theme structure

---

## 2. Architecture

### 2.1 Monorepo Structure

```
packages/themes/
├── corporate/
│   ├── src/
│   │   ├── CorporateThemeServiceProvider.php
│   │   ├── Widgets/
│   │   │   ├── HeroSectionWidget.php
│   │   │   ├── FeaturesGridWidget.php
│   │   │   ├── PricingTableWidget.php
│   │   │   ├── TestimonialsCarouselWidget.php
│   │   │   ├── BlogListingWidget.php
│   │   │   ├── FaqAccordionWidget.php
│   │   │   ├── ContactFormWidget.php
│   │   │   └── FooterWidget.php
│   │   ├── Actions/
│   │   │   └── InstallCorporateThemeAction.php
│   │   └── Data/
│   │       └── CorporateThemeSettings.php (extends shared ThemeSettings)
│   ├── resources/
│   │   ├── views/
│   │   │   ├── layouts/app.blade.php
│   │   │   ├── pages/home.blade.php
│   │   │   └── components/
│   │   │       ├── hero-section.blade.php
│   │   │       ├── features-grid.blade.php
│   │   │       ├── pricing-table.blade.php
│   │   │       ├── testimonials-carousel.blade.php
│   │   │       ├── blog-listing.blade.php
│   │   │       ├── faq-accordion.blade.php
│   │   │       ├── contact-form.blade.php
│   │   │       ├── footer.blade.php
│   │   │       ├── header.blade.php
│   │   │       └── navigation.blade.php
│   │   ├── css/
│   │   │   ├── theme.css (color vars, custom utilities)
│   │   │   └── components/ (component-level styles)
│   │   └── tailwind/
│   │       └── config.js (corporate palette, typography)
│   ├── database/
│   │   ├── migrations/
│   │   │   └── seed_corporate_theme.php
│   │   └── seeders/
│   │       └── CorporateThemeSeeder.php
│   ├── docs/
│   │   ├── INSTALLATION.md
│   │   ├── CUSTOMIZATION.md
│   │   ├── COMPONENTS.md
│   │   └── ARCHITECTURE.md
│   ├── tests/
│   │   ├── Feature/
│   │   │   ├── InstallThemeActionTest.php
│   │   │   └── WidgetRegistrationTest.php
│   │   └── Unit/
│   │       └── ComponentRenderingTest.php
│   ├── composer.json
│   └── README.md
│
├── agency/
│   ├── src/Widgets/            (Agency-specific widget rendering)
│   ├── resources/views/        (Agency-specific layouts & components)
│   ├── resources/css/
│   ├── resources/tailwind/
│   ├── database/
│   ├── docs/
│   ├── tests/
│   ├── composer.json
│   └── README.md
│
└── saas/
    └── (same structure as corporate & agency)
```

### 2.2 Theme Packages Namespace & Naming

- Namespace: `Capell\Themes\Corporate`, `Capell\Themes\Agency`, `Capell\Themes\Saas`
- Package names: `capell-app/capell-theme-corporate`, `capell-app/capell-theme-agency`, `capell-app/capell-theme-saas`
- Service provider: `CorporateThemeServiceProvider`, `AgencyThemeServiceProvider`, `SaasThemeServiceProvider`
- Actions: `InstallCorporateThemeAction`, `InstallAgencyThemeAction`, `InstallSaasThemeAction`

---

## 3. Component Specifications

### 3.1 Corporate Theme Components

Focused on professionalism, trust, and business credibility.

**1. Hero Section** (required)

- Large headline, subheading, CTA button
- Background: professional photography or subtle gradient
- Optional: secondary CTA
- Blade file: `components/hero-section.blade.php`
- LayoutBuilder Widget: `HeroSectionWidget.php`

**2. Features Grid** (required)

- 3–4 feature cards with icon, title, description
- Layout: 2–3 columns (responsive)
- Icon from Heroicons library
- Blade file: `components/features-grid.blade.php`
- LayoutBuilder Widget: `FeaturesGridWidget.php`

**3. Team Grid** (optional - unique to corporate)

- Team member cards: photo, name, title, social links
- 3–4 columns, filterable by department
- Blade file: `components/team-grid.blade.php`
- LayoutBuilder Widget: `TeamGridWidget.php`

**4. Case Studies/Testimonials** (optional - unique to corporate)

- Case study card: logo, challenge, result, quote
- Rotates through 3–4 case studies
- Blade file: `components/case-studies-carousel.blade.php`
- LayoutBuilder Widget: `CaseStudiesCarouselWidget.php`

**5. Blog Listing** (optional)

- Article cards: featured image, title, excerpt, author, date
- Pagination, category filter
- Blade file: `components/blog-listing.blade.php`
- LayoutBuilder Widget: `BlogListingWidget.php`

**6. Contact Form** (optional)

- Fields: name, email, phone, subject, message
- Server validation, CSRF protection
- Blade file: `components/contact-form.blade.php`
- LayoutBuilder Widget: `ContactFormWidget.php`

**7. Footer** (required)

- Multi-column links (About, Services, Resources, Legal)
- Logo, copyright, contact info
- Social links
- Blade file: `components/footer.blade.php`
- LayoutBuilder Widget: `FooterWidget.php`

**Total: 7 components (3 required + 4 optional)**

---

### 3.2 Agency Theme Components

Focused on creativity, visual storytelling, and distinctive design.

**1. Hero Section** (required)

- Headline, subheading, CTA
- Background: asymmetric gradient, animated shapes, or full-bleed image
- Tilted/skewed layout elements
- Blade file: `components/hero-section.blade.php`
- LayoutBuilder Widget: `HeroSectionWidget.php`

**2. Portfolio Gallery** (unique to agency)

- Grid or masonry layout showcasing project thumbnails
- Filter by category (web design, branding, etc.)
- Click opens modal or detail page
- Blade file: `components/portfolio-gallery.blade.php`
- LayoutBuilder Widget: `PortfolioGalleryWidget.php`

**3. Process Flow** (unique to agency)

- Visual step-by-step process (4–5 steps)
- Icons, descriptions, optional connectors/arrows
- Blade file: `components/process-flow.blade.php`
- LayoutBuilder Widget: `ProcessFlowWidget.php`

**4. Team Grid** (optional - agency twist)

- Team member cards with rich photos, titles, bios, social links
- Hover effects, possibly overlaid text
- Blade file: `components/team-grid.blade.php`
- LayoutBuilder Widget: `TeamGridWidget.php`

**5. Client Testimonials/Logos** (optional)

- Rotating client logos OR testimonial cards
- Autoplay carousel
- Blade file: `components/client-testimonials.blade.php`
- LayoutBuilder Widget: `ClientTestimonialsWidget.php`

**6. Services/Offerings** (optional - unique to agency)

- Card grid showing service categories
- Icon, title, description per service
- Blade file: `components/services-grid.blade.php`
- LayoutBuilder Widget: `ServicesGridWidget.php`

**7. Blog/News Listing** (optional)

- Article cards with featured image, title, excerpt
- Category tags, author, date
- Blade file: `components/blog-listing.blade.php`
- LayoutBuilder Widget: `BlogListingWidget.php`

**8. Contact/CTA** (required)

- Bold CTA section or contact form
- Heading + description + CTA button
- Blade file: `components/contact-cta.blade.php`
- LayoutBuilder Widget: `ContactCtaWidget.php`

**9. Footer** (required)

- Creative footer design: logo, social, newsletter signup, contact
- Optional: background pattern/gradient
- Blade file: `components/footer.blade.php`
- LayoutBuilder Widget: `FooterWidget.php`

**Total: 9 components (3 required + 6 optional)**

---

### 3.3 SaaS Theme Components

Focused on features, pricing, integrations, and conversion.

**1. Hero Section** (required)

- Headline, subheading, dual CTA (Sign Up / Learn More)
- Animated gradient background OR product screenshot
- Optional: trust badges (customers, users, etc.)
- Blade file: `components/hero-section.blade.php`
- LayoutBuilder Widget: `HeroSectionWidget.php`

**2. Features Showcase** (required)

- 6–8 feature cards in 2–4 column grid
- Icon, title, description
- Possible: rotating detailed feature display (title + screenshot)
- Blade file: `components/features-showcase.blade.php`
- LayoutBuilder Widget: `FeaturesShowcaseWidget.php`

**3. Pricing Table** (unique to SaaS - required)

- 2–3 pricing tiers
- Feature comparison matrix
- CTA button per tier (highlight popular)
- Optional: billing toggle (monthly/annual)
- Blade file: `components/pricing-table.blade.php`
- LayoutBuilder Widget: `PricingTableWidget.php`

**4. Integrations Showcase** (unique to SaaS)

- Grid of integration logos (Slack, Zapier, etc.)
- Optional: "1000+ integrations" badge
- Blade file: `components/integrations-grid.blade.php`
- LayoutBuilder Widget: `IntegrationsGridWidget.php`

**5. Use Cases/Solutions** (unique to SaaS - optional)

- Card grid: use case + description + link
- Icons representing different industries/use cases
- Blade file: `components/use-cases-grid.blade.php`
- LayoutBuilder Widget: `UseCasesGridWidget.php`

**6. Social Proof/Testimonials** (optional)

- Customer testimonials in carousel or grid
- Avatar, quote, customer name + title + company
- Optional: star ratings
- Blade file: `components/social-proof.blade.php`
- LayoutBuilder Widget: `SocialProofWidget.php`

**7. Blog/Resources** (optional)

- Blog article cards OR resource download cards
- Title, excerpt, category, date
- Blade file: `components/blog-listing.blade.php`
- LayoutBuilder Widget: `BlogListingWidget.php`

**8. FAQ Accordion** (optional)

- Collapsible Q&A
- Search functionality
- Schema markup
- Blade file: `components/faq-accordion.blade.php`
- LayoutBuilder Widget: `FaqAccordionWidget.php`

**9. Footer** (required)

- Standard footer with links, logo, copyright
- Optional: newsletter signup
- Blade file: `components/footer.blade.php`
- LayoutBuilder Widget: `FooterWidget.php`

**Total: 9 components (4 required + 5 optional)**

### 3.2 Layout Components (All Themes)

**Header/Navigation**

- Logo area, responsive menu (mobile hamburger), language selector, theme-aware styling
- Blade file: `components/header.blade.php`
- No LayoutBuilder widget (not a content section, part of layout)

**App Layout**

- Wraps page content, includes header + footer
- Manages meta tags, og: tags, CSS/JS loading
- Blade file: `layouts/app.blade.php`

---

## 4. Unified Admin Settings

### 4.1 Shared Settings Data & Schema

**Location:** `packages/core/src/Data/ThemeSettings.php` (or `packages/admin/`)

**ThemeSettings Data object:**

```php
class ThemeSettings extends Data
{
    public function __construct(
        public string $active_theme,           // 'corporate' | 'agency' | 'saas'
        public string $primary_color,          // hex: '#1a2d6d'
        public string $accent_color,           // hex: '#f59e0b'
        public string $headline_font,          // 'playfair' | 'sora' | 'inter'
        public string $body_font,              // 'inter' | 'manrope'
        public string $hero_style,             // 'image' | 'gradient' | 'video'
        public string $footer_layout,          // 'minimal' | 'expanded' | 'newsletter'
        public string $spacing_preset,         // 'compact' | 'balanced' | 'spacious'
        public bool $show_testimonials,
        public bool $show_pricing,
        public bool $show_blog,
        public bool $show_contact,
    ) {}
}
```

**ThemeSettingsSchema (Filament form):**

- Theme selector dropdown (corporate, agency, saas)
- Color pickers (primary, accent) — shared across all themes
- Font selectors (headline/body) — shared across all themes
- Radio buttons/toggles for hero style, footer layout, spacing preset
- Checkboxes to show/hide optional sections

**Registration (in AdminServiceProvider or similar):**

```php
$registry->registerSettingsClass('themes', ThemeSettings::class);
$registry->register('themes', ThemeSettingsSchema::class);
```

Each theme reads settings via:

```php
$settings = Settings::get('themes'); // Returns ThemeSettings instance
```

---

## 5. LayoutBuilder Widget Integration

### 5.1 Widget Registration

When LayoutBuilder is installed, each theme's ServiceProvider registers **only its unique widgets** (not shared across themes):

```php
// In CorporateThemeServiceProvider::boot()
if (class_exists('Capell\LayoutBuilder\Models\Widget')) {
    CapellCore::registerWidget(HeroSectionWidget::class);
    CapellCore::registerWidget(FeaturesGridWidget::class);
    CapellCore::registerWidget(TeamGridWidget::class);
    CapellCore::registerWidget(CaseStudiesCarouselWidget::class);
    CapellCore::registerWidget(BlogListingWidget::class);
    CapellCore::registerWidget(ContactFormWidget::class);
    CapellCore::registerWidget(FooterWidget::class);
}
```

Each theme registers **only its own components as widgets** — no cross-theme widget sharing.

### 5.2 Widget Class Structure

Each widget extends `Capell\LayoutBuilder\Models\Widget` and implements editable properties:

```php
namespace Capell\Themes\Corporate\Widgets;

use Capell\LayoutBuilder\Models\Widget;
use Filament\FormBuilder\Components\TextInput;
use Filament\FormBuilder\Components\Textarea;
use Filament\FormBuilder\Components\Select;
use Illuminate\View\View;

class HeroSectionWidget extends Widget
{
    protected static string $view = 'corporate::components.hero-section';

    public string $headline = 'Welcome to Our Company';
    public string $subheading = 'Building trust and delivering results';
    public string $cta_text = 'Get Started';
    public string $cta_url = '/contact';
    public ?string $image_url = null;
    public string $background_style = 'image'; // 'image' | 'gradient'

    public static function getLabel(): string
    {
        return 'Hero Section';
    }

    public function getSchema(): array
    {
        return [
            TextInput::make('headline')
                ->label('Headline')
                ->required()
                ->columnSpan('full'),
            Textarea::make('subheading')
                ->label('Subheading')
                ->rows(2)
                ->columnSpan('full'),
            TextInput::make('cta_text')
                ->label('CTA Button Text')
                ->required(),
            TextInput::make('cta_url')
                ->label('CTA URL')
                ->url()
                ->required(),
            Select::make('background_style')
                ->label('Background Style')
                ->options([
                    'image' => 'Image',
                    'gradient' => 'Gradient',
                ])
                ->default('image'),
            TextInput::make('image_url')
                ->label('Image URL')
                ->url()
                ->visible(fn ($get) => $get('background_style') === 'image'),
        ];
    }

    public function render(): View
    {
        return view($this->view, [
            'headline' => $this->headline,
            'subheading' => $this->subheading,
            'cta_text' => $this->cta_text,
            'cta_url' => $this->cta_url,
            'image_url' => $this->image_url,
            'background_style' => $this->background_style,
        ]);
    }
}
```

### 5.3 Widget Data Serialization

Widgets serialize to `layouts.layout_items` table (LayoutBuilder's layout item storage):

```json
{
    "widget_type": "Capell\Themes\Corporate\Widgets\HeroSectionWidget",
    "properties": {
        "headline": "Welcome to Our Company",
        "subheading": "Building trust...",
        "cta_text": "Get Started",
        "cta_url": "/contact",
        "background_style": "image",
        "image_url": "https://..."
    }
}
```

### 5.4 Blade Components vs LayoutBuilder Widgets

**Static Blade usage (works without LayoutBuilder):**

```blade
<x-corporate::hero-section
    headline="Welcome"
    subheading="Our story"
    cta-text="Get Started"
    cta-url="/contact"
/>
```

**LayoutBuilder widget usage (with LayoutBuilder installed):**

- Editor opens layout builder for a page
- Drags "Hero Section" widget from widget palette
- Editor sidebar shows: headline input, subheading textarea, CTA fields, background style dropdown
- Widget renders via `render()` method → calls `corporate::components.hero-section` Blade template
- Saved to database as layout item

**Same Blade template, two interfaces: static import or visual builder.**

### 5.5 Pre-Built Layouts (Seeded on Install)

Each theme seeds **2–3 example layouts** that use its widgets. When editor installs theme, layouts are available in LayoutBuilder:

**Corporate Theme Layouts:**

1. `home-corporate` — Hero + Features + Team + Case Studies + Blog + Contact + Footer
2. `services-corporate` — Hero + Services Grid (as Features with custom styling) + Team + Contact + Footer
3. `about-corporate` — Hero + Team + Case Studies (testimonials) + Contact + Footer

**Agency Theme Layouts:**

1. `portfolio-home-agency` — Hero + Portfolio Gallery + Process Flow + Services + Team + Blog + Contact + Footer
2. `services-agency` — Hero + Services Grid + Process Flow + Contact + Footer
3. `about-agency` — Hero + Team + Client Testimonials + Process Flow + Contact + Footer

**SaaS Theme Layouts:**

1. `home-saas` — Hero + Features + Pricing + Integrations + Use Cases + Social Proof + CTA + Footer
2. `pricing-saas` — Hero + Pricing Table + Use Cases + FAQ + Social Proof + CTA + Footer
3. `features-saas` — Hero + Features Showcase + Use Cases + Integrations + Blog + FAQ + Footer

Each layout is stored as a `Layout` model record with corresponding `LayoutItem` children pointing to widgets.

### 5.6 Layout Seeder Example

```php
// In CorporateThemeSeeder

public function seedLayouts(): void
{
    $type = Type::firstOrCreate(
        ['key' => 'home-corporate', 'type' => 'layout'],
        ['name' => 'Home - Corporate Theme']
    );

    $layout = Layout::create([
        'type_id' => $type->id,
        'name' => 'Home - Corporate Theme',
    ]);

    // Add widgets to layout in order
    $layout->addItem(HeroSectionWidget::class, [
        'headline' => 'Welcome to [Company]',
        'subheading' => 'Delivering excellence since 2020',
        'cta_text' => 'Learn More',
        'cta_url' => '/about',
    ]);

    $layout->addItem(FeaturesGridWidget::class, [
        'features' => [
            ['icon' => 'check', 'title' => 'Feature 1'],
            ['icon' => 'check', 'title' => 'Feature 2'],
        ],
    ]);

    $layout->addItem(TeamGridWidget::class, [
        'title' => 'Our Team',
    ]);

    // ... add more items
}
```

---

## 6. Design Differentiation

### 6.1 Component Variation by Theme

| Component         | Corporate                | Agency                    | SaaS                                |
| ----------------- | ------------------------ | ------------------------- | ----------------------------------- |
| Hero              | ✅ (photo/gradient)      | ✅ (asymmetric, animated) | ✅ (animated gradient + badges)     |
| Features/Showcase | ✅ Features Grid         | ✅ Services Grid          | ✅ Features Showcase (6–8 features) |
| Specialized 1     | ✅ Team Grid             | ✅ Portfolio Gallery      | ✅ Pricing Table                    |
| Specialized 2     | ✅ Case Studies Carousel | ✅ Process Flow           | ✅ Integrations Grid                |
| Specialized 3     | —                        | ✅ Client Testimonials    | ✅ Use Cases Grid                   |
| Blog              | ✅ Blog Listing          | ✅ Blog Listing           | ✅ Blog Listing                     |
| Social Proof      | Case Studies             | Client Testimonials       | Testimonials/Reviews                |
| Contact/CTA       | ✅ Contact Form          | ✅ Contact CTA            | ✅ Dual CTA (Sign Up + Learn More)  |
| Footer            | ✅                       | ✅                        | ✅                                  |
| **Total Widgets** | 7                        | 9                         | 9                                   |

### 6.2 Visual Identity Matrix

| Aspect                | Corporate                           | Agency                              | SaaS                             |
| --------------------- | ----------------------------------- | ----------------------------------- | -------------------------------- |
| **Primary Color**     | Navy (#1a2d6d)                      | Purple (#9333ea)                    | Blue (#3b82f6)                   |
| **Accent Color**      | Gold (#f59e0b)                      | Neon Pink (#ec4899)                 | Cyan (#06b6d4)                   |
| **Headline Font**     | Playfair Display                    | Sora (bold)                         | Inter (semibold)                 |
| **Body Font**         | Inter                               | Manrope                             | Inter                            |
| **Hero Background**   | Professional photography, centered  | Asymmetric gradient + tilted shapes | Animated gradient overlay        |
| **Card Style**        | Subtle shadow, square               | Rounded + gradient border           | Minimal shadow, thin border      |
| **Spacing Preset**    | Generous (32px, lots of whitespace) | Balanced (20px)                     | Compact (16px grid)              |
| **Imagery**           | Muted tones, professional           | High contrast, colorful, abstract   | Icons + minimal photos           |
| **Layout Philosophy** | Centered, symmetric                 | Asymmetric, overlapping             | Grid-based, component-heavy      |
| **Animations**        | Fade-in, subtle                     | Bold transitions, scroll effects    | Micro-interactions, hover states |
| **CTA Button Style**  | Outlined, conservative              | Filled + gradient                   | Filled + subtle glow             |
| **Use Case**          | B2B Services, Consulting            | Creative Agencies, Design           | SaaS Products, Startups          |

### 6.2 Theme-Specific CSS

**Corporate Theme** (`corporate/resources/css/theme.css`):

```css
:root {
    --color-primary: #1a2d6d;
    --color-accent: #f59e0b;
    --font-headline: 'Playfair Display', serif;
    --font-body: 'Inter', sans-serif;
    --spacing-unit: 2rem;
}

/* Conservative button style */
.btn-primary {
    @apply border-primary text-primary border-2 bg-white;
}

/* Subtle animations */
@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}
.animate-in {
    animation: fadeIn 0.6s ease-in-out;
}
```

**Agency Theme** (`agency/resources/css/theme.css`):

```css
:root {
    --color-primary: #9333ea;
    --color-accent: #ec4899;
    --font-headline: 'Sora', sans-serif;
    --font-body: 'Manrope', sans-serif;
    --spacing-unit: 1.25rem;
}

/* Bold button with gradient */
.btn-primary {
    @apply from-primary to-accent bg-gradient-to-r text-white;
}

/* Assertive animations */
@keyframes slideInUp {
    from {
        transform: translateY(20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}
```

**SaaS Theme** (`saas/resources/css/theme.css`):

```css
:root {
    --color-primary: #3b82f6;
    --color-accent: #06b6d4;
    --font-headline: 'Inter', sans-serif;
    --font-body: 'Inter', sans-serif;
    --spacing-unit: 1rem;
}

/* Modern filled button with glow */
.btn-primary {
    @apply bg-primary shadow-primary/20 text-white shadow-lg;
}

/* Interactive micro-interactions */
.hover-lift:hover {
    transform: translateY(-2px);
}
```

---

## 7. Installation & Setup

### 7.1 User Flow

**Step 1: Install**

```bash
# Option A: Install one theme
composer require capell-app/capell-theme-corporate

# Option B: Install all three
composer require capell-app/capell-theme-corporate capell-app/capell-theme-agency capell-app/capell-theme-saas
```

**Step 2: Register**
ServiceProviders auto-registered via auto-discovery.

**Step 3: Seed**

```bash
php artisan db:seed --class=CorporateThemeSeeder
php artisan db:seed --class=AgencyThemeSeeder
php artisan db:seed --class=SaasThemeSeeder
```

Creates `themes` table records:

```
id | name | key | custom_css | meta | admin | default | status
1  | Corporate | corporate | NULL | {...} | {...} | 1 | 1
```

**Step 4: Configure**
Editor opens Settings panel in Filament admin → selects active theme + customizes colors/fonts.

**Step 5: Publish (Optional)**

```bash
php artisan vendor:publish --tag=capell-theme-corporate-views
php artisan vendor:publish --tag=capell-theme-corporate-css
```

Copies views to `resources/views/vendor/capell-themes/corporate/` for customization.

### 7.2 Artisan Commands

Each theme package provides install command:

```bash
php artisan capell:corporate-theme-install
php artisan capell:agency-theme-install
php artisan capell:saas-theme-install
```

These run `InstallCorporateThemeAction` (etc.), which:

- Seeds theme record
- Registers LayoutBuilder widgets (if installed)
- Publishes views/CSS
- Outputs setup instructions

---

## 8. Flexibility & Composability

Themes are **foundational, not prescriptive**. Developers can mix, match, extend, and rebuild using theme components as building blocks.

### 8.1 Layout Composability (LayoutBuilder + Static)

**Same widgets, infinite layouts:**

```blade
<!-- Example: Developer creates custom "Services + Testimonials" layout -->
<x-theme::hero />
<x-theme::features-grid :items="$features" />
<x-theme::services-grid :items="$services" />
<!-- Add agency's services widget -->
<x-theme::social-proof :testimonials="$testimonials" />
<x-theme::contact-cta />
<x-theme::footer />
```

**Or via LayoutBuilder:**

- Editor starts with blank page
- Drags Corporate's Hero widget
- Adds SaaS's Pricing widget (if aesthetically compatible)
- Adds Agency's Portfolio Gallery widget
- Custom mix of widgets from any/all themes on one page

**Layouts are NOT locked to a theme's widgets** — developers can compose freely.

### 8.2 Component Slot Extensibility

Every component uses Blade slots for customization:

```blade
<!-- Hero with custom slot content -->
<x-corporate::hero-section
    headline="My Custom Site"
    subheading="Built with Corporate Theme"
>
    <!-- Inject custom banner or announcement -->
    <x-slot name="before-cta">
        <div class="bg-yellow-100 p-4">
            <p>🎉 Special launch offer - 20% off</p>
        </div>
    </x-slot>

    <!-- Replace CTA button entirely -->
    <x-slot name="cta-button">
        <a
            href="/demo"
            class="btn-custom"
        >
            Book a Demo
        </a>
    </x-slot>
</x-corporate::hero-section>
```

Each component exposes predictable slots:

- `hero-section`: `before-cta`, `after-cta`, `cta-button`
- `features-grid`: `header`, `before-items`, `after-items`
- `footer`: `before-footer`, `footer-columns`, `after-footer`

### 8.3 CSS Variable Theming (No Compile Needed)

Override colors/fonts **without rebuilding Tailwind**:

```css
/* In app.css, after importing theme CSS */
:root {
    --color-primary: #e91e63; /* Override corporate navy */
    --color-accent: #00bcd4; /* Override gold */
    --font-headline: 'Merriweather'; /* Override Playfair */
}
```

All components respect CSS variables:

```css
/* In corporate/resources/css/theme.css */
.btn-primary {
    background-color: var(--color-primary);
    font-family: var(--font-body);
}
```

**Result:** Change theme appearance instantly without touching Tailwind or JavaScript.

### 8.4 Widget Extension (LayoutBuilder)

Create theme-specific widget variants:

```php
// app/Widgets/ExtendedHeroWidget.php
namespace App\Widgets;

use Capell\Themes\Corporate\Widgets\HeroSectionWidget;
use Filament\FormBuilder\Components\FileUpload;

class ExtendedHeroWidget extends HeroSectionWidget
{
    public ?string $video_url = null;

    public function getSchema(): array
    {
        return array_merge(parent::getSchema(), [
            FileUpload::make('video_url')
                ->label('Background Video')
                ->acceptedFileTypes(['video/mp4']),
        ]);
    }
}
```

Register custom widget:

```php
CapellCore::registerWidget(ExtendedHeroWidget::class);
```

Now editors get enhanced hero widget with video support — built on corporate theme's foundation.

### 8.5 Multi-Site Use Case (One Theme, Many Sites)

**Same theme, different configurations:**

Corporate theme used for:

- **Law firm site** → Primary: #003366, accent: #cc0000, footer with office addresses
- **SaaS consultant site** → Primary: #1e40af, accent: #059669, footer with newsletter + pricing links
- **B2B manufacturer** → Primary: #1f2937, accent: #f97316, footer with certifications

All three use Corporate theme's Hero, Features, Team, Footer components, but configured completely differently.

Settings registry per-site:

```php
// Site 1 (law firm)
Settings::for('law-firm-site')->set('themes', [
    'active_theme' => 'corporate',
    'primary_color' => '#003366',
    'accent_color' => '#cc0000',
    'footer_layout' => 'expanded',
]);

// Site 2 (SaaS consultant)
Settings::for('saas-site')->set('themes', [
    'active_theme' => 'corporate',
    'primary_color' => '#1e40af',
    'accent_color' => '#059669',
    'footer_layout' => 'expanded',
]);
```

**Same theme code, completely different visual presentations.**

### 8.6 Publishing Views for Hardcore Customization

For developers who want full control:

```bash
php artisan vendor:publish --tag=capell-theme-corporate-views
```

Copies all Blade files to `resources/views/vendor/capell-themes/corporate/`.

Now developer modifies at will:

```blade
<!-- resources/views/vendor/capell-themes/corporate/components/hero-section.blade.php -->
<!-- Custom implementation, no longer tied to package -->
```

### 8.7 Bridging Themes

Mix corporate + agency + saas components:

```blade
<!-- "Hybrid" page: corporate hero + agency portfolio + saas pricing -->
<x-corporate::hero-section />
<x-agency::portfolio-gallery />
<x-saas::pricing-table />
<x-corporate::footer />
```

Each component **visually respects its parent theme's design**, but developers can create bespoke experiences by combining.

---

## 9. Developer Customization

### 9.1 Blade Template Overrides

After publishing, developer modifies:

```blade
<!-- resources/views/vendor/capell-themes/corporate/components/hero-section.blade.php -->

<section class="hero py-24">
    <!-- Custom modifications here -->
</section>
```

### 9.2 Tailwind/CSS Overrides

In project's `tailwind.config.js`:

```js
export default {
    content: ['resources/views/vendor/capell-themes/corporate/**/*.blade.php'],
    theme: {
        extend: {
            colors: {
                primary: '#custom-color',
            },
        },
    },
}
```

### 9.3 Component Props & Slots

Components accept props for flexibility:

```blade
<x-corporate::hero-section
    :headline="$page->meta['hero_headline']"
    class="bg-gray-900"
>
    <!-- Optional slot for additional content -->
</x-corporate::hero-section>
```

---

## 10. Testing Strategy

### 10.1 Test Coverage (80%+ target)

**Actions:**

- `InstallThemeActionTest` — verifies theme record creation, LayoutBuilder registration, view publishing, layout seeding

**Blade Rendering:**

- `ComponentRenderingTest` — render each Blade component with sample data, assert expected HTML structure

**Widget Integration:**

- `WidgetRegistrationTest` — if LayoutBuilder installed, verify all theme-specific widgets registered
- `WidgetSchemaTest` — verify widget schema fields (Filament form fields) render correctly
- `WidgetRenderingTest` — verify widget render output matches Blade component output

**Layout Seeding:**

- `LayoutSeedingTest` — verify pre-built layouts seeded with correct layout items, widget types, and properties

**Settings:**

- `ThemeSettingsTest` — verify settings persist and are readable by themes
- `ThemeSettingsSchemTest` — verify admin form schema renders correctly with all fields

### 10.2 Test Files (per theme)

```
tests/
├── Feature/
│   ├── InstallThemeActionTest.php
│   ├── WidgetRegistrationTest.php
│   ├── LayoutSeedingTest.php
│   ├── ThemeSettingsTest.php
│   └── ThemeSettingsSchemaTest.php
└── Unit/
    ├── Components/
    │   ├── HeroSectionComponentTest.php
    │   ├── FeaturesGridComponentTest.php
    │   └── ... (one per component)
    ├── Widgets/
    │   ├── HeroSectionWidgetTest.php
    │   ├── FeaturesGridWidgetTest.php
    │   └── ... (one per widget)
    └── Actions/
        └── InstallThemeActionTest.php
```

**Example test:**

```php
// HeroSectionWidgetTest
public function it_renders_widget_with_properties(): void
{
    $widget = HeroSectionWidget::make([
        'headline' => 'Welcome',
        'subheading' => 'Get started',
        'cta_text' => 'Sign Up',
        'cta_url' => '/signup',
    ]);

    $rendered = $widget->render()->render();

    $this->assertStringContainsString('Welcome', $rendered);
    $this->assertStringContainsString('Sign Up', $rendered);
}

// LayoutSeedingTest
public function it_seeds_home_layout_with_widgets(): void
{
    (new CorporateThemeSeeder)->run();

    $layout = Layout::where('name', 'Home - Corporate Theme')->firstOrFail();

    $this->assertCount(7, $layout->items); // 7 layout items
    $this->assertTrue($layout->items->first()->widget_type === HeroSectionWidget::class);
}
```

---

## 11. Documentation

### 11.1 Per-Theme Docs

**INSTALLATION.md**

- Prerequisites (Laravel 10+, PHP 8.2, Capell core)
- Installation steps
- Optional LayoutBuilder setup
- Seeding & configuration

**CUSTOMIZATION.md**

- How to publish views/CSS
- Overriding component templates
- Modifying colors/fonts via settings or CSS
- Adding custom components
- Examples of common modifications

**COMPONENTS.md**

- Reference for each of 8 components
- Prop list + defaults
- Blade slot documentation
- LayoutBuilder widget schema (if applicable)
- Example usage

**ARCHITECTURE.md**

- Directory structure explanation
- Design philosophy (why this layout?)
- CSS variable naming
- How settings flow to components
- Extending the theme

### 11.2 README.md

- Quick start
- Feature overview
- Design philosophy (1–2 paragraphs)
- Links to full docs

---

## 12. Dependencies

### 12.1 Composer

```json
{
    "require": {
        "laravel/framework": "^10.0",
        "capell-app/capell": "^4.0",
        "tailwindlabs/tailwindcss": "^4.0"
    },
    "require-dev": {
        "pestphp/pest": "^3.0",
        "laravel/pint": "^1.25"
    },
    "suggest": {
        "capell-app/capell-layout-builder": "Enable visual layout editor for theme components"
    }
}
```

### 12.2 NPM

```json
{
    "devDependencies": {
        "@tailwindcss/form-builder": "^0.5",
        "@tailwindcss/typography": "^0.5"
    }
}
```

---

## 13. Implementation Order

1. **Phase 1: Core Infrastructure**
    - Create three theme packages skeleton (`packages/themes/{corporate,agency,saas}/`)
    - Implement shared `ThemeSettings` Data + Schema in core/admin
    - Create `InstallThemeAction` base pattern (action for each theme)
    - Setup composer.json for all three packages
    - Publish to local path repositories

2. **Phase 2: Corporate Theme (Complete)**
    - Build all 7 Blade components + layouts (`resources/views/components/`, `resources/views/layouts/`)
    - Create 7 LayoutBuilder widget classes (`src/Widgets/`)
    - Write theme CSS + Tailwind config (`resources/css/`, `resources/tailwind/`)
    - Build seeder: seed theme record + 3 pre-built layouts with layout items (`database/seeders/`)
    - Implement ServiceProvider: register widgets (if LayoutBuilder installed), register with CapellCore
    - Write comprehensive tests (component rendering, widget schema, layout seeding)
    - Write docs (INSTALLATION.md, CUSTOMIZATION.md, COMPONENTS.md, ARCHITECTURE.md)

3. **Phase 3: Agency Theme (Complete)**
    - Build all 9 Blade components (different set: Portfolio Gallery, Process Flow, Services, etc.)
    - Create 9 LayoutBuilder widgets with agency-specific schema
    - Write theme CSS + Tailwind config (bold colors, asymmetric design)
    - Build seeder: seed theme + 3 agency-specific layouts
    - Implement ServiceProvider
    - Write tests + docs

4. **Phase 4: SaaS Theme (Complete)**
    - Build all 9 Blade components (different set: Pricing, Integrations, Use Cases, etc.)
    - Create 9 LayoutBuilder widgets with SaaS-specific schema
    - Write theme CSS + Tailwind config (modern, grid-based)
    - Build seeder: seed theme + 3 SaaS-specific layouts
    - Implement ServiceProvider
    - Write tests + docs

5. **Phase 5: Unified Settings**
    - Verify all three themes respond to shared `ThemeSettings`
    - Test theme switching (change active_theme in settings, render different layouts)
    - Verify color/font settings apply across all themes

6. **Phase 6: LayoutBuilder Integration Testing**
    - Cross-theme testing: install all 3, verify widgets all registered
    - Test layout builder: create layout with corporate widgets, switch to agency theme and verify layout still renders (different visual appearance)
    - Test widget editing: edit widget in layout, verify changes persist and render correctly
    - Test layout preview: verify layouts render identically in frontend and in LayoutBuilder preview

7. **Phase 7: Polish & Ship**
    - Documentation review (clarity, completeness, examples)
    - README.md for each theme
    - CHANGELOG.md
    - Add to Packagist (or docs on how to install from GitHub)
    - Tag v1.0.0 release

---

## 14. Risks & Mitigations

| Risk                                                                            | Mitigation                                                                                               |
| ------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------- |
| Settings schema doesn't accommodate all three themes                            | Unified schema designed to be minimal + extensible; themes interpret settings visually, not functionally |
| Blade duplication increases maintenance burden                                  | Accept duplication for clarity; document common patterns so changes propagate consistently               |
| LayoutBuilder widget registration fails silently if LayoutBuilder not installed | Use `class_exists()` check + test for both scenarios (with/without LayoutBuilder)                        |
| Components too opinionated, hard to customize                                   | Provide extensive documentation + slot-based architecture for flexibility                                |
| Performance issues with 8 components on one page                                | Lazy load via Blade conditional or use LayoutBuilder visibility toggles                                  |

---

## 15. Success Metrics

- ✅ All three themes installable & functional within first week of release
- ✅ 80%+ test coverage across all three themes
- ✅ Zero external bugs (100% pass) in first month
- ✅ Developers report "easy to customize" in feedback
- ✅ LayoutBuilder integration seamless (widget editing matches Blade rendering pixel-for-pixel)
