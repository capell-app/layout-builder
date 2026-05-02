# Modern Widgets — Developer Guide

**Last updated:** April 2026 | **Status:** Current (post-Tailwind rewrite)

This document explains how the modern widget system actually works, how to build new widgets correctly, and what to build next. Read this before touching any widget file.

---

## How the system works

### The rendering pipeline

```
Filament admin schema
        ↓
  Widget props (PHP array)
        ↓
  Blade @props declaration
        ↓
  HTML output with Tailwind classes
        ↓
  Browser (no CSS variables required)
```

Each widget is a **self-contained Blade component** at:

```
packages/foundation/mosaic/resources/views/components/modern/
```

Registered under the `mosaic` namespace, used like:

```blade
<x-mosaic::modern.faq-section
    :faqs="$faqData"
    title="FAQ"
/>
```

### Styling approach (current)

All widgets use **direct Tailwind utility classes only**. There are no CSS custom properties (`--mosaic-*` variables) in use — they were removed because the variables were never defined by the host site, making backgrounds transparent and text invisible.

**The rule:** every visual property must be a Tailwind class hardcoded in the template. Never use `var(--anything)` inline styles.

Color palette in use across all widgets:

| Purpose                   | Class                                                   |
| ------------------------- | ------------------------------------------------------- |
| Section background        | `bg-white` (inherited from page)                        |
| Card background           | `bg-gray-50`                                            |
| Card border (subtle)      | `border border-gray-100`                                |
| Primary accent            | `indigo-600`                                            |
| Primary accent (light bg) | `indigo-50` / `indigo-100`                              |
| Primary accent (text)     | `indigo-600` / `indigo-700`                             |
| Featured/gradient         | `bg-gradient-to-br from-indigo-600 to-purple-800`       |
| Highlight/badge           | `amber-400` / `amber-900`                               |
| Body text                 | `text-gray-900` (headings), `text-gray-500` (secondary) |
| Dividers                  | `border-gray-100` / `border-gray-200`                   |

---

## Anatomy of a widget

Every widget file follows this structure:

```blade
{{--
    DocBlock: name, props description
--}}

@props([
    'title' => 'Default Value',
    'items' => [...],          ← default demo data
    'layout' => 'horizontal',
    'customizable' => true,
])

@php
    // Computed values only — no business logic
    $gridClass = match ($columns) {
        2 => 'grid-cols-1 md:grid-cols-2',
        4 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4',
        default => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
    };
@endphp

<section class="px-6 py-12 md:px-12 md:py-16">
    {{-- Section heading --}}
    @if ($title)
        <div class="mx-auto mb-12 max-w-2xl text-center">
            <h2 class="text-3xl font-bold text-gray-900 md:text-4xl">
                {{ $title }}
            </h2>
        </div>
    @endif

    {{-- Main content --}}
    <div class="{{ $gridClass }} grid gap-6">
        @forelse ($items as $item)
            {{-- Item card --}}
        @empty
            <div class="col-span-full py-12 text-center">
                <p class="text-gray-500">No items configured</p>
            </div>
        @endforelse
    </div>

    {{-- Admin hint (shown only to logged-in users) --}}
    @if ($customizable && auth()->check())
        <div class="mt-12 border-t border-gray-100 pt-8 text-center opacity-60">
            <span class="text-xs text-gray-500">✨ Customize: ...</span>
        </div>
    @endif
</section>

{{-- Inline JS (only if widget needs interactivity) --}}
<script>
    function widgetSpecificFunction() { ... }
</script>
```

### Key patterns

**Default demo data in `@props`** — every prop has sensible defaults so the widget renders usefully with zero configuration. This is what editors see before customizing.

**`@forelse` not `@foreach`** — always provide an `@empty` fallback. Widgets should never show a broken blank section.

**`@if ($title)` guards** — every optional section is wrapped so it collapses cleanly when the prop is empty/null.

**`auth()->check()` on admin hints** — the `$customizable` hint is always gated so it never shows to site visitors.

**Animations via `<style>` not `<script>`** — if you need CSS keyframes, put them in a `<style>` tag in the component. Never write CSS inside a `<script>` tag.

**Responsive column ordering** — use Tailwind's `md:order-last` to reorder columns on larger screens. Never use CSS `direction: rtl` hacks.

**Absolute-positioned badges need a relative wrapper** — if you're placing a badge absolutely on top of a circle/icon, wrap both in a `relative` container sized to match the inner element, not the whole column.

---

## Current widget inventory

| Widget              | File                            | Interactive?             | Layout options                 |
| ------------------- | ------------------------------- | ------------------------ | ------------------------------ |
| Hero Banner         | `hero-banner.blade.php`         | No                       | height (sm/lg/xl)              |
| Card Grid           | `card-grid.blade.php`           | No                       | columns (2/3/4)                |
| CTA Section         | `cta-section.blade.php`         | No                       | single layout                  |
| Feature List        | `feature-list.blade.php`        | No                       | columns (2/3)                  |
| Stats Section       | `stats-section.blade.php`       | No                       | horizontal/vertical            |
| Testimonials        | `testimonials.blade.php`        | Yes (carousel)           | grid/carousel, columns (1/2/3) |
| Team Members        | `team-members.blade.php`        | No                       | columns (2/3/4)                |
| Pricing Table       | `pricing-table.blade.php`       | Yes (billing toggle)     | single layout                  |
| Image Gallery       | `image-gallery.blade.php`       | No                       | columns (2/3/4)                |
| Alternating Content | `alternating-content.blade.php` | No                       | left/right per item            |
| Process Steps       | `process-steps.blade.php`       | No                       | horizontal/vertical            |
| FAQ Section         | `faq-section.blade.php`         | Yes (filter + accordion) | single layout                  |

---

## Building a new widget

### Step 1 — Define the props

Start by listing everything an editor would want to change. Think in terms of: content (text, images), structure (how many columns, which layout), and visibility (show/hide sections).

```blade
@props([
    'title' => 'Section Title',
    'subtitle' => '',
    'items' => [
        ['label' => 'Item One', 'value' => '...', 'icon' => '🎯'],
    ],
    'columns' => 3,
    'customizable' => true,
])
```

### Step 2 — Compute derived values in `@php`

Only derive classes and flags here — never fetch data or run queries.

```blade
@php
    $gridClass = match ((int) $columns) {
        2 => 'grid-cols-1 md:grid-cols-2',
        4 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4',
        default => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
    };
@endphp
```

### Step 3 — Build the HTML with Tailwind only

Use the color palette table above. When in doubt, reach for `bg-gray-50` cards with `text-gray-900` headings and `text-indigo-600` accents.

Featured/highlighted items get: `bg-gradient-to-br from-indigo-600 to-purple-800 text-white`

### Step 4 — Add interactivity with vanilla JS (if needed)

Keep JS minimal and scoped. Use data attributes for state, not global variables. Namespace your functions to avoid conflicts between multiple widgets on the same page:

```js
function myWidgetName_toggle(element) {
    // ...
}
```

Or better — scope to a container:

```js
function initMyWidget(container) {
    container.querySelector('.toggle-btn').addEventListener('click', () => { ... })
}
document.querySelectorAll('.my-widget').forEach(initMyWidget)
```

### Step 5 — Add the Filament schema

Create `src/Filament/Schemas/Widgets/ModernMyWidgetSchema.php`. Map every `@prop` to a form field:

| Prop type           | Filament component               |
| ------------------- | -------------------------------- |
| String (short)      | `TextInput`                      |
| String (long)       | `Textarea`                       |
| Boolean             | `Toggle`                         |
| Select from options | `Select::make()->options([...])` |
| Array of objects    | `Repeater`                       |
| Number              | `TextInput::make()->numeric()`   |

### Step 6 — Add demo data

Update `demo/` pages to include the new widget so it can be visually tested. Run:

```bash
cd ~/Sites/capell-ruby/ && npm run build && php artisan optimize:clear
```

---

## Common mistakes to avoid

**Invisible content** — always verify your card/section has a visible background. `bg-gray-50` is the standard. Never leave backgrounds relying on CSS variables.

**White text on white background** — if an item uses `text-white`, verify its container has a dark background (`bg-indigo-600`, gradient, etc).

**Badge positioning** — `absolute` positions an element relative to the nearest `relative` ancestor. If the badge appears in the wrong place, add `relative` to the immediate parent container, not a grandparent.

**CSS in script tags** — `@keyframes` and all CSS must go in `<style>` tags. JavaScript goes in `<script>` tags.

**`@foreach` without empty state** — always use `@forelse` so editors see a useful message when no data is configured.

**Long function names colliding** — two widgets using `function slideCarousel()` on the same page will conflict. Use the widget name as a prefix or scope to a container selector.

---

## Future widget ideas

Ordered by impact and reuse value:

### High priority

**Contact / Lead Form widget**
Props: `fields[]`, `submitLabel`, `successMessage`, `action` (URL or Livewire component). Needs Livewire component for submission. Most marketing pages need a form.

**Navigation / Header widget**
Props: `logo`, `navItems[]`, `ctaButton`, `sticky` (bool). Needs JS for mobile hamburger. Currently every demo page rolls its own nav.

**Footer widget**
Props: `columns[]` (each with `heading`, `links[]`), `copyright`, `socialLinks[]`. Simple but used on every page.

**Video Section widget**
Props: `videoUrl` (YouTube/Vimeo embed or local), `poster`, `title`, `autoplay`. Embed-only (no file upload). Useful for product demos.

### Medium priority

**Comparison Table widget**
Props: `features[]`, `plans[]`, `values` (2D map). Table with sticky column headers. Useful alongside Pricing Table.

**Timeline widget**
A date-based variant of Process Steps. Props: `events[]` with `date`, `title`, `description`, `icon`. Vertical layout only.

**Logo Cloud / Partners widget**
Props: `title`, `logos[]` (each with `name`, `image`, `url`). Simple grid of partner/client logos. Very common on landing pages.

**Countdown Timer widget**
Props: `targetDate`, `title`, `message` (shown after expiry). Needs JS `setInterval`. Useful for launches/sales.

**Map / Location widget**
Props: `embedUrl` (Google Maps iframe src), `address`, `phone`, `hours[]`. Displays an embedded map alongside contact details.

### Lower priority (complex)

**Tabbed Content widget**
Props: `tabs[]` (each with `label`, `content` or `widgetSlot`). Lets editors put multiple content sections behind tabs.

**Before/After Slider widget**
Props: `beforeImage`, `afterImage`, `label`. Drag slider to reveal comparison. Needs JS drag handler.

**Pricing Calculator widget**
Interactive: props drive base prices + multipliers. Outputs a calculated total. Highly custom per project — build as a project-specific widget, not a generic one.

---

## Extending the Filament schema system

Each widget schema lives in `src/Filament/Schemas/Widgets/`. When adding props to an existing widget:

1. Add the prop with a default in `@props` in the Blade file
2. Add the corresponding form field in the schema class
3. Verify the Filament repeater structure matches the array shape expected by `@forelse`

The schema array keys must exactly match the Blade prop names — Filament passes them through as-is.

---

## After any widget change

```bash
cd ~/Sites/capell-ruby/ && npm run build && php artisan optimize:clear
```

Tailwind JIT scans Blade files at build time. Changes to class names in Blade files require a rebuild to appear. Cache clear ensures Laravel's view cache doesn't serve stale output.
