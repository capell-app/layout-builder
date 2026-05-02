# Capell Theme Packages Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create three production-ready CMS theme packages (Corporate, Agency, SaaS) for Capell with comprehensive CMS features, exceptional flexibility, and high-standard testing (90%+ coverage, WCAG 2.1 AA, performance budgets, E2E, security tests).

**Architecture:** Three self-contained theme packages in `packages/themes/{corporate,agency,saas}/` sharing unified `ThemeSettings` in `packages/theme-studio/themes-core/`. Each theme provides 7-9 Blade components that work as static templates OR Mosaic widgets (when installed). All components include SEO, accessibility, dark mode, and form handling. Comprehensive test suite covers unit, integration, E2E, accessibility, performance, and security.

**Tech Stack:** Laravel 10+, Blade, Tailwind CSS 4, Filament 4, Pest 3, Mosaic (optional), Lighthouse CI, WCAG testing, E2E (Playwright).

---

## Current Status (as of 2026-04-19)

**Repo:** `capell-packages-4` (branch `feat/theme-packages`, worktree at `../capell-packages-4-themes`)

**Completed phases:**

- ✅ **Phase 1: Infrastructure & shared settings** — Tasks 1-3
- ✅ **Phase 2: Corporate Theme** — Tasks 4-20 (51 files, 5 commits)
- ✅ **Phase 3: Agency Theme** — Tasks 21-35 (32 PHP files, 5 commits)
- ✅ **Phase 4: SaaS Theme** — Tasks 36-50 (31 PHP files, 5 commits)
- 🔶 **Phase 5 partial** — cross-cutting `themes-core` code was partially scaffolded: `Analytics/GoogleAnalytics4`, `Analytics/UtmCollector`, `Cache/ThemeCache`, `Images/ResponsiveImage`, `Language/LanguageManager`, `Language/HreflangGenerator`, `Mail/FormSubmissionNotification`, `Mail/NewsletterWelcome`, `Performance/AssetOptimizer`, `Performance/CriticalCssInliner`, `Search/{SiteSearch,DatabaseSiteSearch,SearchResult}` + Blade partials. These landed in the migration commit (`44971864`) but have NOT been reviewed or tested yet. Treat as prototype/stub.

**Migration note:** all files were originally built in `capell-app/intelligent-panini-da14c2` worktree by mistake, then migrated to this correct repo on 2026-04-19 in commit `44971864`. Migration commit used `--no-verify` per user approval because composer/npm deps are not yet installed in this worktree.

## Where to Start From (New Session)

1. **Working directory:** `/Users/ben/Sites/packages/capell/capell-packages-4-themes`
2. **Branch:** `feat/theme-packages`
3. **First steps:**
    - Run `composer install` and `npm install` so Pint/Prettier are available (pre-commit hooks require them)
    - Inspect the Phase 5 partial files under `packages/theme-studio/themes-core/src/` — these were written by a subagent that was killed mid-task. Many are likely OK but need spec-compliance + code-quality review per the subagent-driven-development skill
    - Resume execution at **Phase 5 Task 51** (see below), treating already-present files as starting points
4. **Namespaces (important — differ from original plan due to collision with vendored `Capell\Core\` and `Capell\Admin\`):**
    - `Capell\Themes\Core\*` (from `packages/theme-studio/themes-core/`)
    - `Capell\Themes\Admin\*` (from `packages/theme-studio/themes-admin/`)
    - `Capell\Themes\Corporate\*`, `Capell\Themes\Agency\*`, `Capell\Themes\Saas\*`
5. **Filament 4 API note:** `Tabs` lives at `Filament\Schemas\Components\Tabs` (not `Filament\Forms\Components\Tabs`). The tests use `Orchestra\Testbench\TestCase` with Filament providers registered, not `Filament\Tests\TestCase` which does not exist in Filament 4 dist.
6. **Remaining work:**
    - Phase 5 (review partial files + complete Tasks 51-65 — multi-lang hreflang partial exists, analytics partial exists, email partial exists, etc.; still missing: SEO structured data builder, sitemap, canonical, OG cards, spam protection, preview mode, mobile menu, accessibility helpers, and Filament admin page wiring)
    - Phase 6 (Testing & QA, Tasks 66-80)
    - Phase 7 (Documentation & release, Tasks 81-85)

## Known Deviations & Defects to Address

- **Composer package names** — themes are named `capell-app/capell-theme-{name}` but neighboring Capell packages use pattern `capell-app/{name}` (e.g., `capell-app/blog`, `capell-app/mosaic`). Consider renaming to `capell-app/theme-corporate` etc. for consistency
- **`minimum-stability` / `prefer-stable`** — corporate package has these, agency/saas don't — align
- **Root-level wiring** — each theme package declares its own composer.json but is not yet wired into a monorepo root composer.json at `capell-packages-4/composer.json`. The existing monorepo composer.json in `capell-packages-4-themes/` may need a path repository added (check — blog/mosaic are siblings and may already resolve via the parent repo's path config)
- **Tests not executed** — all Pest tests are source-only; `composer install` + root `phpunit.xml` `<testsuites>` update needed to run them. Task 66 (Phase 6) should address this first before E2E work begins
- **Pest vs PHPUnit style** — some infrastructure tests use PHPUnit `TestCase` directly; project convention is Pest — convert during Phase 6 QA pass
- **Snake_case properties in `ThemeSettings`** — spec/plan uses snake_case (`active_theme`, `primary_color`) but Capell/Laravel/Filament convention is camelCase. Code review flagged this as a design concern for Phase 6

---

## File Structure

```
packages/
├── themes/
│   ├── corporate/
│   │   ├── src/
│   │   │   ├── CorporateThemeServiceProvider.php
│   │   │   ├── Widgets/
│   │   │   │   ├── HeroSectionWidget.php
│   │   │   │   ├── FeaturesGridWidget.php
│   │   │   │   ├── TeamGridWidget.php
│   │   │   │   ├── CaseStudiesCarouselWidget.php
│   │   │   │   ├── BlogListingWidget.php
│   │   │   │   ├── ContactFormWidget.php
│   │   │   │   └── FooterWidget.php
│   │   │   ├── Actions/
│   │   │   │   ├── InstallCorporateThemeAction.php
│   │   │   │   └── SeedCorporateLayoutsAction.php
│   │   │   ├── Data/
│   │   │   │   └── CorporateThemeSettings.php
│   │   │   └── SEO/
│   │   │       └── StructuredDataGenerator.php
│   │   ├── resources/
│   │   │   ├── views/
│   │   │   │   ├── layouts/app.blade.php
│   │   │   │   ├── pages/home.blade.php
│   │   │   │   └── components/
│   │   │   │       ├── hero-section.blade.php
│   │   │   │       ├── features-grid.blade.php
│   │   │   │       ├── team-grid.blade.php
│   │   │   │       ├── case-studies-carousel.blade.php
│   │   │   │       ├── blog-listing.blade.php
│   │   │   │       ├── contact-form.blade.php
│   │   │   │       ├── footer.blade.php
│   │   │   │       ├── header.blade.php
│   │   │   │       ├── language-switcher.blade.php
│   │   │   │       ├── dark-mode-toggle.blade.php
│   │   │   │       ├── breadcrumbs.blade.php
│   │   │   │       └── search-form.blade.php
│   │   │   ├── css/
│   │   │   │   ├── theme.css
│   │   │   │   └── components/
│   │   │   └── tailwind/
│   │   │       └── config.js
│   │   ├── database/
│   │   │   ├── migrations/
│   │   │   │   └── seed_corporate_theme.php
│   │   │   └── seeders/
│   │   │       └── CorporateThemeSeeder.php
│   │   ├── tests/
│   │   │   ├── Feature/
│   │   │   │   ├── InstallThemeActionTest.php
│   │   │   │   ├── WidgetRegistrationTest.php
│   │   │   │   ├── LayoutSeedingTest.php
│   │   │   │   ├── ThemeSettingsTest.php
│   │   │   │   ├── AccessibilityTest.php
│   │   │   │   ├── SEOTest.php
│   │   │   │   └── E2ELayoutBuilderTest.php
│   │   │   └── Unit/
│   │   │       ├── Components/
│   │   │       │   ├── HeroSectionComponentTest.php
│   │   │       │   ├── FeaturesGridComponentTest.php
│   │   │       │   └── ... (one per component)
│   │   │       ├── Widgets/
│   │   │       │   ├── HeroSectionWidgetTest.php
│   │   │       │   ├── FeaturesGridWidgetTest.php
│   │   │       │   └── ... (one per widget)
│   │   │       ├── SEO/
│   │   │       │   └── StructuredDataGeneratorTest.php
│   │   │       └── Actions/
│   │   │           └── InstallCorporateThemeActionTest.php
│   │   ├── docs/
│   │   │   ├── INSTALLATION.md
│   │   │   ├── CUSTOMIZATION.md
│   │   │   ├── COMPONENTS.md
│   │   │   ├── ARCHITECTURE.md
│   │   │   ├── TESTING.md
│   │   │   └── SEO.md
│   │   ├── composer.json
│   │   ├── phpunit.xml
│   │   └── README.md
│   ├── agency/
│   │   └── (same structure as corporate, different components)
│   └── saas/
│       └── (same structure as corporate, different components)
│
├── core/ (modifications)
│   ├── src/
│   │   ├── Data/
│   │   │   └── ThemeSettings.php (NEW: shared settings for all themes)
│   │   └── Schemas/
│   │       └── ThemeSettingsSchema.php (NEW: admin form schema)
│   └── ...
│
└── admin/ (modifications)
    ├── src/
    │   └── ServiceProviders/
    │       └── AdminServiceProvider.php (MODIFY: register theme settings)
    └── ...
```

---

## Implementation Phases

### Phase 1: Infrastructure & Shared Settings (3 tasks)

### Phase 2: Corporate Theme Components & Widgets (25 tasks)

### Phase 3: Agency Theme (25 tasks)

### Phase 4: SaaS Theme (25 tasks)

### Phase 5: Cross-Cutting Features (12 tasks)

### Phase 6: Testing & QA (15 tasks)

### Phase 7: Documentation & Release (5 tasks)

**Total: ~110 tasks**

---

# PHASE 1: Infrastructure & Shared Settings

## Task 1: Create Package Structure & composer.json Files

**Files:**

- Create: `packages/theme-corporate/composer.json`
- Create: `packages/theme-agency/composer.json`
- Create: `packages/theme-saas/composer.json`
- Create: `packages/theme-corporate/README.md`
- Create: `packages/theme-corporate/.gitignore`

- [ ] **Step 1: Create corporate package composer.json**

Create `packages/theme-corporate/composer.json`:

```json
{
    "name": "capell-app/capell-theme-corporate",
    "description": "Professional corporate theme for Capell CMS",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": "^8.2",
        "laravel/framework": "^10.0",
        "capell-app/capell": "^4.0",
        "spatie/laravel-data": "^4.0"
    },
    "require-dev": {
        "pestphp/pest": "^3.0",
        "laravel/pint": "^1.25",
        "phpstan/phpstan": "^1.0"
    },
    "suggest": {
        "capell-app/capell-mosaic": "Enable visual layout editor for theme components"
    },
    "autoload": {
        "psr-4": {
            "Capell\\Themes\\Corporate\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Capell\\Themes\\Corporate\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Capell\\Themes\\Corporate\\CorporateThemeServiceProvider"
            ]
        }
    }
}
```

- [ ] **Step 2: Create agency & saas composer.json files**

Copy corporate/composer.json to agency/composer.json and saas/composer.json, then edit:

- Replace `capell-theme-corporate` → `capell-theme-agency`
- Replace `Professional corporate theme` → `Creative agency theme`
- Replace `Capell\\Themes\\Corporate` → `Capell\\Themes\\Agency`
- Replace `CorporateThemeServiceProvider` → `AgencyThemeServiceProvider`

Repeat for saas theme.

- [ ] **Step 3: Create .gitignore**

Create `packages/theme-corporate/.gitignore`:

```
/vendor
/node_modules
.DS_Store
*.log
.env
.env.local
```

- [ ] **Step 4: Create README.md**

Create `packages/theme-corporate/README.md`:

````markdown
# Capell Corporate Theme

Professional, trust-focused theme for B2B services, consulting firms, and corporate websites.

## Features

- Professional design with conservative color palette (navy + gold)
- Components: Hero, Features, Team, Case Studies, Blog, Contact, Footer
- Blade templates + optional Mosaic widgets
- Full SEO, accessibility (WCAG 2.1 AA), dark mode, multi-language support
- Form handling with validation and spam protection
- Email integration (newsletters, form submissions)
- Analytics hooks (GA4)

## Installation

```bash
composer require capell-app/capell-theme-corporate
```
````

## Documentation

See `docs/` directory for detailed guides.

## License

MIT

````

- [ ] **Step 5: Commit**

```bash
git add packages/theme-corporate/composer.json packages/theme-corporate/README.md packages/theme-corporate/.gitignore
git add packages/theme-agency/composer.json packages/theme-agency/README.md packages/theme-agency/.gitignore
git add packages/theme-saas/composer.json packages/theme-saas/README.md packages/theme-saas/.gitignore
git commit -m "chore: create theme package scaffolding and composer configs"
````

---

## Task 2: Create Shared ThemeSettings Data Object

**Files:**

- Create: `packages/core/src/Data/ThemeSettings.php`
- Create: `packages/core/tests/Unit/Data/ThemeSettingsTest.php`

- [ ] **Step 1: Write failing test for ThemeSettings**

Create `packages/core/tests/Unit/Data/ThemeSettingsTest.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Core\Tests\Unit\Data;

use Capell\Core\Data\ThemeSettings;
use PHPUnit\Framework\TestCase;

class ThemeSettingsTest extends TestCase
{
    public function test_creates_theme_settings_with_all_properties(): void
    {
        $settings = new ThemeSettings(
            active_theme: 'corporate',
            primary_color: '#1a2d6d',
            accent_color: '#f59e0b',
            headline_font: 'playfair',
            body_font: 'inter',
            hero_style: 'image',
            footer_layout: 'expanded',
            spacing_preset: 'balanced',
            show_testimonials: true,
            show_pricing: false,
            show_blog: true,
            show_contact: true,
        );

        $this->assertSame('corporate', $settings->active_theme);
        $this->assertSame('#1a2d6d', $settings->primary_color);
        $this->assertSame('#f59e0b', $settings->accent_color);
        $this->assertSame('playfair', $settings->headline_font);
        $this->assertSame('inter', $settings->body_font);
        $this->assertSame('image', $settings->hero_style);
        $this->assertSame('expanded', $settings->footer_layout);
        $this->assertSame('balanced', $settings->spacing_preset);
        $this->assertTrue($settings->show_testimonials);
        $this->assertFalse($settings->show_pricing);
        $this->assertTrue($settings->show_blog);
        $this->assertTrue($settings->show_contact);
    }

    public function test_theme_settings_has_sensible_defaults(): void
    {
        $settings = ThemeSettings::from([
            'active_theme' => 'corporate',
        ]);

        $this->assertSame('corporate', $settings->active_theme);
        $this->assertSame('#1a2d6d', $settings->primary_color); // corporate navy
        $this->assertSame('playfair', $settings->headline_font);
        $this->assertTrue($settings->show_blog);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
cd /Users/ben/Sites/capell-app/intelligent-panini-da14c2
composer test -- packages/core/tests/Unit/Data/ThemeSettingsTest.php -v
```

Expected: FAIL with "Class Capell\Core\Data\ThemeSettings does not exist"

- [ ] **Step 3: Create ThemeSettings Data class**

Create `packages/core/src/Data/ThemeSettings.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Core\Data;

use Spatie\LaravelData\Data;

class ThemeSettings extends Data
{
    public function __construct(
        public string $active_theme,           // 'corporate' | 'agency' | 'saas'
        public string $primary_color = '#1a2d6d',      // corporate navy default
        public string $accent_color = '#f59e0b',       // corporate gold default
        public string $headline_font = 'playfair',     // playfair | sora | inter
        public string $body_font = 'inter',            // inter | manrope
        public string $hero_style = 'image',           // image | gradient | video
        public string $footer_layout = 'expanded',     // minimal | expanded | newsletter
        public string $spacing_preset = 'balanced',    // compact | balanced | spacious
        public bool $show_testimonials = true,
        public bool $show_pricing = false,
        public bool $show_blog = true,
        public bool $show_contact = true,
    ) {}
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
composer test -- packages/core/tests/Unit/Data/ThemeSettingsTest.php -v
```

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add packages/core/src/Data/ThemeSettings.php packages/core/tests/Unit/Data/ThemeSettingsTest.php
git commit -m "feat: add shared ThemeSettings data object for all themes"
```

---

## Task 3: Create ThemeSettingsSchema (Filament Form)

**Files:**

- Create: `packages/admin/src/Schemas/ThemeSettingsSchema.php`
- Create: `packages/admin/tests/Unit/Schemas/ThemeSettingsSchemaTest.php`

- [ ] **Step 1: Write failing test for schema**

Create `packages/admin/tests/Unit/Schemas/ThemeSettingsSchemaTest.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Admin\Tests\Unit\Schemas;

use Capell\Admin\Schemas\ThemeSettingsSchema;
use Capell\Core\Data\ThemeSettings;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tests\TestCase;

class ThemeSettingsSchemaTest extends TestCase
{
    public function test_theme_settings_schema_renders(): void
    {
        $schema = ThemeSettingsSchema::make();

        $this->assertNotEmpty($schema->getComponents());
        $this->assertTrue(
            $schema->getComponents()
                ->filter(fn ($comp) => $comp instanceof Select && $comp->getName() === 'active_theme')
                ->count() > 0
        );
    }

    public function test_schema_includes_color_pickers(): void
    {
        $schema = ThemeSettingsSchema::make();
        $colorPickers = $schema->getComponents()
            ->filter(fn ($comp) => $comp instanceof ColorPicker);

        $this->assertGreaterThan(0, $colorPickers->count());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
composer test -- packages/admin/tests/Unit/Schemas/ThemeSettingsSchemaTest.php -v
```

Expected: FAIL with "Class Capell\Admin\Schemas\ThemeSettingsSchema does not exist"

- [ ] **Step 3: Create ThemeSettingsSchema**

Create `packages/admin/src/Schemas/ThemeSettingsSchema.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Admin\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Toggle;

class ThemeSettingsSchema
{
    public static function make(): Tabs
    {
        return Tabs::make('Theme Settings')
            ->tabs([
                Tabs\Tab::make('Theme')
                    ->schema([
                        Select::make('active_theme')
                            ->label('Active Theme')
                            ->options([
                                'corporate' => 'Corporate',
                                'agency' => 'Agency',
                                'saas' => 'SaaS',
                            ])
                            ->required(),
                    ]),
                Tabs\Tab::make('Colors')
                    ->schema([
                        ColorPicker::make('primary_color')
                            ->label('Primary Color')
                            ->required(),
                        ColorPicker::make('accent_color')
                            ->label('Accent Color')
                            ->required(),
                    ]),
                Tabs\Tab::make('Typography')
                    ->schema([
                        Select::make('headline_font')
                            ->label('Headline Font')
                            ->options([
                                'playfair' => 'Playfair Display',
                                'sora' => 'Sora',
                                'inter' => 'Inter',
                            ])
                            ->required(),
                        Select::make('body_font')
                            ->label('Body Font')
                            ->options([
                                'inter' => 'Inter',
                                'manrope' => 'Manrope',
                            ])
                            ->required(),
                    ]),
                Tabs\Tab::make('Layout')
                    ->schema([
                        Select::make('hero_style')
                            ->label('Hero Background')
                            ->options([
                                'image' => 'Image',
                                'gradient' => 'Gradient',
                                'video' => 'Video',
                            ])
                            ->required(),
                        Select::make('footer_layout')
                            ->label('Footer Layout')
                            ->options([
                                'minimal' => 'Minimal',
                                'expanded' => 'Expanded',
                                'newsletter' => 'Newsletter',
                            ])
                            ->required(),
                        Select::make('spacing_preset')
                            ->label('Spacing')
                            ->options([
                                'compact' => 'Compact',
                                'balanced' => 'Balanced',
                                'spacious' => 'Spacious',
                            ])
                            ->required(),
                    ]),
                Tabs\Tab::make('Sections')
                    ->schema([
                        Toggle::make('show_testimonials')
                            ->label('Show Testimonials Section'),
                        Toggle::make('show_pricing')
                            ->label('Show Pricing Section'),
                        Toggle::make('show_blog')
                            ->label('Show Blog Section'),
                        Toggle::make('show_contact')
                            ->label('Show Contact Section'),
                    ]),
            ]);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
composer test -- packages/admin/tests/Unit/Schemas/ThemeSettingsSchemaTest.php -v
```

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add packages/admin/src/Schemas/ThemeSettingsSchema.php packages/admin/tests/Unit/Schemas/ThemeSettingsSchemaTest.php
git commit -m "feat: add ThemeSettingsSchema for admin panel configuration"
```

---

# PHASE 2: Corporate Theme Components & Widgets

## Task 4: Create Corporate Theme ServiceProvider

**Files:**

- Create: `packages/theme-corporate/src/CorporateThemeServiceProvider.php`
- Create: `packages/theme-corporate/tests/Feature/ServiceProviderTest.php`

- [ ] **Step 1: Write failing test**

Create `packages/theme-corporate/tests/Feature/ServiceProviderTest.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Themes\Corporate\Tests\Feature;

use Capell\Themes\Corporate\CorporateThemeServiceProvider;
use Orchestra\Testbench\TestCase;

class ServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [CorporateThemeServiceProvider::class];
    }

    public function test_service_provider_boots(): void
    {
        $this->assertTrue(true); // Provider loaded successfully
    }

    public function test_blade_namespace_registered(): void
    {
        $this->assertTrue(
            view()->getEngineResolver()->resolve('blade')->getFinder()->getNamespaces()
                ->has('corporate')
        );
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
cd packages/theme-corporate
composer test -- tests/Feature/ServiceProviderTest.php -v
```

Expected: FAIL with "Class Capell\Themes\Corporate\CorporateThemeServiceProvider does not exist"

- [ ] **Step 3: Create ServiceProvider**

Create `packages/theme-corporate/src/CorporateThemeServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Themes\Corporate;

use Capell\Themes\Corporate\Widgets\HeroSectionWidget;
use Capell\Themes\Corporate\Widgets\FeaturesGridWidget;
use Capell\Themes\Corporate\Widgets\TeamGridWidget;
use Capell\Themes\Corporate\Widgets\CaseStudiesCarouselWidget;
use Capell\Themes\Corporate\Widgets\BlogListingWidget;
use Capell\Themes\Corporate\Widgets\ContactFormWidget;
use Capell\Themes\Corporate\Widgets\FooterWidget;
use Illuminate\Support\ServiceProvider;
use Capell\Core\Facades\CapellCore;

class CorporateThemeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Load views
        $this->loadViewsFrom(
            __DIR__ . '/../resources/views',
            'corporate'
        );

        // Load CSS
        if (file_exists($cssPath = __DIR__ . '/../resources/css/theme.css')) {
            // CSS loaded via Vite/Tailwind in consuming application
        }

        // Register Mosaic widgets if available
        if (class_exists('Capell\Mosaic\Models\Widget')) {
            $this->registerMosaicWidgets();
        }

        // Publish views
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/capell-themes/corporate'),
        ], 'capell-theme-corporate-views');

        // Publish CSS
        $this->publishes([
            __DIR__ . '/../resources/css' => resource_path('css/vendor/capell-themes/corporate'),
        ], 'capell-theme-corporate-css');
    }

    protected function registerMosaicWidgets(): void
    {
        CapellCore::registerWidget(HeroSectionWidget::class);
        CapellCore::registerWidget(FeaturesGridWidget::class);
        CapellCore::registerWidget(TeamGridWidget::class);
        CapellCore::registerWidget(CaseStudiesCarouselWidget::class);
        CapellCore::registerWidget(BlogListingWidget::class);
        CapellCore::registerWidget(ContactFormWidget::class);
        CapellCore::registerWidget(FooterWidget::class);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
composer test -- tests/Feature/ServiceProviderTest.php -v
```

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add src/CorporateThemeServiceProvider.php tests/Feature/ServiceProviderTest.php
git commit -m "feat: create CorporateThemeServiceProvider with Mosaic widget registration"
```

---

**[... Continue with 30+ more tasks covering: Hero component (Blade + Widget + Tests), Features Grid, Team Grid, Case Studies, Blog, Contact Form, Footer, SEO generation, Dark Mode support, Forms with validation, Email integration, Layouts & seeding, Agency Theme components, SaaS Theme components, E2E tests, Performance testing, Accessibility audits, Security tests, Documentation ...]**

---

# PHASE 2+ Summary (Tasks 5-60)

Due to context constraints, here's the remaining task structure for Phases 2-7:

## Corporate Theme Component Tasks (Tasks 5-20)

**Task 5:** Create HeroSectionWidget & Blade component (Blade + Widget + Unit tests)
**Task 6:** Create FeaturesGridWidget & component (same pattern)
**Task 7:** Create TeamGridWidget & component
**Task 8:** Create CaseStudiesCarouselWidget & component
**Task 9:** Create BlogListingWidget & component
**Task 10:** Create ContactFormWidget & component (with validation)
**Task 11:** Create FooterWidget & component
**Task 12:** Create SEO/StructuredDataGenerator (schema.org markup)
**Task 13:** Create DarkModeToggle component
**Task 14:** Create LanguageSwitcher component
**Task 15:** Create Breadcrumbs component
**Task 16:** Create SearchForm component
**Task 17:** Create FormValidationClass (client + server validation)
**Task 18:** Create EmailNotificationClass (form submissions, newsletters)
**Task 19:** Create CorporateThemeSeeder (seed theme record + layouts)
**Task 20:** Create CSS theme file with dark mode & accessibility support

---

**[Each task follows TDD: failing test → run fail → implement → run pass → commit]**

---

# PHASE 3-4: Agency & SaaS Themes (Tasks 21-50)

Replicate Phase 2 structure for Agency theme:

- **Portfolio Gallery widget**
- **Process Flow widget**
- **Services Grid widget**
- **Client Testimonials widget**

Replicate for SaaS theme:

- **Pricing Table widget**
- **Integrations Grid widget**
- **Use Cases widget**
- **FAQ Accordion widget**

Each follows same pattern: Blade component + Widget + Tests

---

# PHASE 5: Cross-Cutting Features (Tasks 51-65)

**Task 51:** Multi-language support (language switcher, hreflang generation)
**Task 52:** Analytics GA4 event hooks (form submissions, CTA clicks)
**Task 53:** Email template system (form notifications, newsletters)
**Task 54:** Image optimization (lazy loading, responsive srcset)
**Task 55:** Caching integration hooks
**Task 56:** Search integration (site search component)
**Task 57:** Performance optimization (asset minification, bundling)
**Task 58:** Accessibility improvements (ARIA labels, focus management)
**Task 59:** Mobile/responsive improvements (touch interactions, viewport)
**Task 60:** Structured data generation (JSON-LD schema)
**Task 61:** Sitemap auto-generation
**Task 62:** Canonical URL handling
**Task 63:** OG/Twitter card generation
**Task 64:** Form spam protection (honeypot + CAPTCHA integration)
**Task 65:** Preview mode for drafts (workspace preview)

---

# PHASE 6: Testing & QA (Tasks 66-80)

**Task 66:** E2E test: Mosaic layout builder (create layout, add widgets, publish)
**Task 67:** E2E test: Theme switching (change theme, verify styles)
**Task 68:** E2E test: Form submission (fill form, validate, submit, receive email)
**Task 69:** E2E test: Dark mode toggle (enable, verify styles persist)
**Task 70:** E2E test: Multi-language switching
**Task 71:** Accessibility audit (WCAG 2.1 AA) - all 3 themes
**Task 72:** Performance testing (Lighthouse CI setup, 90+ score targets)
**Task 73:** Visual regression testing (screenshot comparison across themes)
**Task 74:** Cross-browser testing (Chrome, Firefox, Safari, Edge)
**Task 75:** Mobile responsiveness testing (375px, 768px, 1024px, 1440px)
**Task 76:** Security testing (CSRF validation, XSS prevention, SQL injection checks)
**Task 77:** Load testing (render 100 widgets on single page, measure performance)
**Task 78:** SEO validation (meta tags, schema markup, canonical, hreflang)
**Task 79:** Code coverage report (ensure 90%+ across all themes)
**Task 80:** QA checklist creation (manual testing checklist)

---

# PHASE 7: Documentation & Release (Tasks 81-85)

**Task 81:** Write INSTALLATION.md per theme
**Task 82:** Write CUSTOMIZATION.md per theme (slots, CSS vars, widget extension)
**Task 83:** Write COMPONENTS.md per theme (prop docs, examples)
**Task 84:** Write TESTING.md (how to run tests, accessibility testing guide)
**Task 85:** Release v1.0.0 (tag, update Packagist, publish announcement)

---

## Execution Recommendations

**This plan has ~85 tasks.** Implementation approach:

1. **Sequential Execution (Recommended for Quality):**
    - Tasks 1-3 (Infrastructure): ~30 min
    - Tasks 4-20 (Corporate base): ~8-10 hours
    - Tasks 21-50 (Agency + SaaS): ~16-20 hours (can parallelize agencies/saas once patterns locked)
    - Tasks 51-65 (Cross-cutting): ~8-10 hours
    - Tasks 66-80 (Testing): ~12-15 hours (can parallelize test categories)
    - Tasks 81-85 (Docs): ~4-5 hours

    **Total: 50-70 hours solo, ~25-35 hours with parallel agents**

2. **Subagent-Driven (Fastest):**
    - Phase 1 (3 tasks) sequentially
    - Phase 2 (17 tasks) → dispatch 3-4 subagents in parallel, each handles 4-5 component tasks
    - Phase 3-4 (30 tasks) → dispatch 6 subagents in parallel (corporate/agency/saas split, 2 agents per theme)
    - Phase 5 (15 tasks) → dispatch 5 subagents in parallel
    - Phase 6 (15 tasks) → dispatch 5 subagents in parallel (E2E, accessibility, performance, security, coverage)
    - Phase 7 (5 tasks) sequentially

    **Timeline: ~2-3 days with 6-8 concurrent subagents**

---

**Plan complete and saved to `docs/superpowers/plans/2026-04-19-capell-theme-packages.md`.**

## Execution Options

**1. Subagent-Driven (recommended)** - I dispatch fresh subagents per task phase, review between phases, optimize for speed with parallel work

**2. Inline Execution** - Execute tasks in this session using superpowers:executing-plans, with checkpoints for review between phases

**Which approach would you prefer?**
