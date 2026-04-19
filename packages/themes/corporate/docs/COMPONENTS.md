# Components

All components are registered under the `corporate` namespace:

```blade
<x-corporate::hero-section title="Welcome" />
```

## Widgets

| Widget                      | Blade view                                       | Purpose                                          |
|-----------------------------|--------------------------------------------------|--------------------------------------------------|
| `HeroSectionWidget`         | `corporate::components.hero-section`             | Primary hero with CTA                            |
| `FeaturesGridWidget`        | `corporate::components.features-grid`            | Grid of product/service features                 |
| `TeamGridWidget`            | `corporate::components.team-grid`                | Photos and bios of team members                  |
| `CaseStudiesCarouselWidget` | `corporate::components.case-studies-carousel`    | Horizontal carousel of case studies              |
| `BlogListingWidget`         | `corporate::components.blog-listing`             | Responsive grid of blog posts                    |
| `ContactFormWidget`         | `corporate::components.contact-form`             | Accessible contact form with honeypot            |
| `FooterWidget`              | `corporate::components.footer`                   | Expanded / minimal / newsletter footer layouts   |

## Utility components

- `<x-corporate::header />` — sticky header with primary nav
- `<x-corporate::breadcrumbs :items="$items" />` — breadcrumb nav
- `<x-corporate::language-switcher />` — locale select
- `<x-corporate::dark-mode-toggle />` — icon button, persists to localStorage
- `<x-corporate::search-form />` — site search form with GET `q=`

## Slots

Every component accepts the default `$slot` so you can override the
rendered content. The hero additionally accepts an explicit title prop —
if the slot is non-empty, it wins.

## Accessibility

- Landmarks: `<header role="banner">`, `<main id="main" role="main">`,
  `<nav aria-label="Primary">`, `<footer role="contentinfo">`.
- Every form control has an associated `<label>`.
- Breadcrumbs set `aria-current="page"` on the last item.
- A `.skip-to-content` link is included in the base layout.
- All interactive elements honor `:focus-visible` outlines driven by
  `--focus-ring`.
