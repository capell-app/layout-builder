# Agency theme components

The Agency theme ships nine content widgets plus a set of supporting
Blade components.

## Content widgets

| Widget | Blade component | Purpose |
|---|---|---|
| `HeroStatementWidget` | `<x-agency::hero-statement />` | Oversized statement hero with gradient background |
| `PortfolioGridWidget` | `<x-agency::portfolio-grid />` | Filterable magazine-style case-study grid |
| `ProcessFlowWidget` | `<x-agency::process-flow />` | Numbered step-by-step process |
| `ServicesShowcaseWidget` | `<x-agency::services-showcase />` | Icon-driven services with expandable details |
| `ClientsMarqueeWidget` | `<x-agency::clients-marquee />` | Animated horizontal logo marquee |
| `TestimonialsQuoteWidget` | `<x-agency::testimonials-quote />` | Large pull-quote testimonials, one at a time |
| `AwardsBadgesWidget` | `<x-agency::awards-badges />` | Grid of recognition badges |
| `ContactInquiryWidget` | `<x-agency::contact-inquiry />` | Inquiry form with budget/timeline fields |
| `AgencyFooterWidget` | `<x-agency::agency-footer />` | Expressive social-first footer |

## Support components

- `<x-agency::header />` — site header with navigation, CTA, dark-mode toggle
- `<x-agency::dark-mode-toggle />` — theme toggle button with local-storage persistence
- `<x-agency::language-switcher />` — accessible locale select
- `<x-agency::breadcrumbs :items="[...]" />` — navigational breadcrumbs with `aria-current`
- `<x-agency::search-form />` — search input + submit button

## Composability

All components support a `$slot` so you can inject custom markup:

```blade
<x-agency::hero-statement statement="Work worth a second look.">
    <span class="text-[var(--color-primary)]">Made</span> to be remembered.
</x-agency::hero-statement>
```

## Accessibility

Every component includes the relevant landmarks, ARIA labels, and
keyboard-navigable controls. The Feature/AccessibilityTest suite asserts
these markers on the Blade source.
