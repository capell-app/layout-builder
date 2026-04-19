# SaaS theme components

Each component lives in `resources/views/components/` and is invoked via
the `saas::` Blade namespace: `<x-saas::{name} />`.

## Layout

- `<x-saas::header />` — sticky translucent nav with primary CTA + dark toggle
- `<x-saas::saas-footer />` — 4-column footer (product / company / resources / legal) + newsletter + socials

## Utility

- `<x-saas::dark-mode-toggle />` — toggles `data-theme` on `<html>`, persisted to `localStorage`
- `<x-saas::language-switcher />` — `<select>` with `aria-label="Change language"`
- `<x-saas::breadcrumbs :items="[…]" />` — semantic `<nav><ol>` with `aria-current="page"` on the last item
- `<x-saas::search-form action="/search" />` — accessible search with `<label>` for screen readers

## Widgets (also usable as standalone components)

| Component                          | Purpose                                                        |
| ---------------------------------- | -------------------------------------------------------------- |
| `<x-saas::hero-with-screenshot />` | Gradient hero, dual CTAs, trust badges, screenshot mockup      |
| `<x-saas::feature-matrix />`       | Feature comparison table with checkmarks across tiers          |
| `<x-saas::pricing-table />`        | 3 tiers with monthly/annual CSS-only toggle                    |
| `<x-saas::integrations-grid />`    | Logo grid of third-party integrations                          |
| `<x-saas::use-cases-tabs />`       | Radio-based tabs, no JS framework required                     |
| `<x-saas::testimonials-wall />`    | Masonry wall (CSS columns), avatars, star ratings              |
| `<x-saas::faq-accordion />`        | Native `<details>` / `<summary>` for a11y                      |
| `<x-saas::cta-banner />`           | Conversion-focused banner, gradient / solid / inverse variants |

## Example: overriding the hero

```blade
<x-saas::hero-with-screenshot
    eyebrow="Now with AI"
    title="Ship 2x faster with Capell Copilot"
    subtitle="An AI pair-programmer for your whole team."
    primary-cta-label="Try it free"
    primary-cta-url="/signup"
    secondary-cta-label="See pricing"
    secondary-cta-url="#pricing"
    :trust-badges="['SOC 2', 'HIPAA', 'SSO']"
    screenshot-url="/img/dashboard.png"
/>
```

## Accessibility

All components pass WCAG 2.1 AA color contrast in light and dark mode, set
explicit landmark roles (`banner`, `main`, `contentinfo`), render focus-visible
outlines via `--focus-ring`, and respect `prefers-reduced-motion`.
