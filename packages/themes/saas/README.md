# Capell SaaS Theme

Modern, conversion-optimized SaaS theme for Capell CMS.

## Design

- Electric indigo primary (`#6366f1`) + vibrant emerald accent (`#10b981`)
- Inter for every text element — tight tracking, modern geometric feel
- Gradient hero with product-screenshot mockup, dual CTAs, trust badges
- Pricing table with CSS-only monthly/annual toggle (no Alpine needed)
- Native `<details>/<summary>` FAQ accordion for accessibility
- Full dark mode + `prefers-reduced-motion` support

## Features

- 9 Mosaic widgets: Hero (with screenshot), Feature Matrix, Pricing Table,
  Integrations Grid, Use Cases Tabs, Testimonials Wall, FAQ Accordion,
  CTA Banner, SaaS Footer
- SEO: JSON-LD for Organization, SoftwareApplication, Product + Offers
  (per pricing tier), FAQPage, BreadcrumbList
- Accessibility: skip-to-content, landmark roles, ARIA labels, WCAG 2.1 AA
- Fully responsive: 375 / 768 / 1024 / 1440 breakpoints
- Idempotent install migration + seeder

## Installation

```bash
composer require capell-app/capell-theme-saas
php artisan migrate
php artisan saas:install --seed-layouts
```

## Documentation

- [Installation](docs/INSTALLATION.md)
- [Customization](docs/CUSTOMIZATION.md)
- [Components](docs/COMPONENTS.md)

## License

MIT License
