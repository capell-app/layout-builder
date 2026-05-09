---
title: Capell Inertia Runtime Design
date: 2026-05-09
status: draft
---

# Capell Inertia Runtime Design

## Purpose

Build first-class Inertia support for Capell public websites without treating
Inertia as a one-off theme trick. Capell should own CMS resolution, page and
widget meaning, publishing state, preview context, SEO, package extension
points, and public-output safety. Inertia should own the public frontend
experience: persistent layouts, navigation, transitions, forms, shared props,
deferred props, partial reloads, SSR, asset versioning, and client-side
component composition.

The target is not "make a Capell page render through Inertia". The target is a
proper Capell frontend runtime system where Blade, Livewire, and Inertia are
controlled rendering strategies behind the same CMS contract.

## Product Position

Capell should provide one shared Inertia runtime that multiple Inertia themes
inherit. Individual Inertia themes should be thin visual packages. They should
not duplicate app bootstrapping, SSR wiring, shared props, page resolution,
navigation loading, media normalization, SEO mapping, preview payloads, package
extension hooks, or public-output safety checks.

The shared runtime gives Capell a better product surface:

- Editors continue using Filament, pages, layouts, widgets, custom page types,
  publishing, redirects, domains, and preview.
- Developers get a real Inertia application architecture rather than
  server-rendered HTML fragments passed into Vue or React.
- Package authors get one public data/presentation contract for Blade,
  Livewire, and Inertia instead of separate package-specific integrations.
- Multiple Inertia themes can share page and widget data builders while swapping
  only layout, page, and section components.

## Current Context

The current theme documentation describes Foundation Theme as the shared Blade
runtime. It registers theme definitions, renderer contracts, preview context,
token CSS, Tailwind asset generation, media URL handling, Blade directives, and
a post-load beacon client.

The current frontend package resolves public requests through a shared pipeline:
site, language, page, layout, theme, context, view registration, and subscriber
notifications. Page rendering then flows mainly through Livewire page components
and Blade views. Theme rendering adapts Capell page/layout/widget data into
Blade section HTML through `ThemePageAdapter`, `ThemeRegistry`, and
`BladeThemeRenderer`.

This is a good base, but the runtime boundary is implicit. Page types and
widgets currently lean on Blade/Livewire implementation fields. Inertia needs
those concerns normalized into explicit runtime presentations.

## Core Design

### Frontend Runtime

Introduce a first-class frontend runtime concept:

```php
enum FrontendRuntime: string
{
    case Blade = 'blade';
    case Livewire = 'livewire';
    case Inertia = 'inertia';
}
```

Capell should resolve one primary runtime per public response. The active theme
selects the runtime. Page types and widgets may register presentations for each
runtime, but they should not switch a request from one runtime to another.

Core owns the vocabulary. Frontend owns response selection. Runtime packages own
implementation details.

### Theme Runtime Metadata

Theme manifests and theme definitions should declare runtime type explicitly:

```json
{
    "kind": "theme",
    "themeKey": "inertia-corporate",
    "runtime": "inertia",
    "extends": "capell-app/inertia-runtime",
    "frontend": {
        "entry": "resources/js/app.ts",
        "ssr": true,
        "pages": "resources/js/Pages",
        "layouts": "resources/js/Layouts",
        "components": "resources/js/Components"
    }
}
```

Blade themes continue to declare or default to `runtime: "blade"`. Livewire can
be treated as a runtime for page/component response purposes even when it still
uses Blade views underneath.

This lets Capell validate manifests, scaffold correctly, show meaningful theme
metadata in admin/marketplace surfaces, route preview consistently, and choose
the correct frontend responder without package-specific detection.

### Shared CMS Data Contract

Capell should define canonical public data objects for page rendering. Inertia
themes should consume these DTOs rather than each theme inventing prop shapes.

Representative shape:

```php
final class FrontendPageViewData extends Data
{
    public function __construct(
        public PageViewData $page,
        public SiteViewData $site,
        public ThemeViewData $theme,
        public NavigationViewData $navigation,
        public LayoutViewData $layout,
        public SeoViewData $seo,
        public PreviewViewData $preview,
        public array $shared = [],
    ) {}
}
```

These objects are not Inertia-specific. Blade, Livewire, and Inertia should be
able to consume the same public CMS data with runtime-specific responders.

### Page Type Contract

Page type should express content meaning, not only render implementation.

Target model:

```php
PageType::make('blog-index')
    ->data(BlogIndexPageDataBuilder::class)
    ->presentations([
        FrontendRuntime::Blade->value => BlogIndexBladePresentation::class,
        FrontendRuntime::Livewire->value => BlogIndexLivewirePresentation::class,
        FrontendRuntime::Inertia->value => BlogIndexInertiaPresentation::class,
    ]);
```

The exact API can follow existing Capell registries rather than this fluent
syntax, but the boundary matters:

- Page type owns meaning.
- Data builder owns public CMS data.
- Presentation owns runtime-specific component/view mapping.
- Theme owns visual treatment.

### Widget Contract

Widgets should also separate content/data from presentation. A widget type
should define schema and public data once, then register runtime presentations:

```php
[
    'key' => 'hero',
    'data' => HeroWidgetDataBuilder::class,
    'presentations' => [
        'blade' => 'capell-theme::sections.hero',
        'livewire' => HeroSection::class,
        'inertia' => 'Sections/Hero',
    ],
]
```

For an Inertia page, the layout should become a structured component graph:

```php
[
    'layout' => [
        'containers' => [
            [
                'key' => 'main',
                'widgets' => [
                    [
                        'key' => 'hero',
                        'component' => 'Sections/Hero',
                        'props' => [...],
                    ],
                ],
            ],
        ],
    ],
]
```

It should not default to Blade-rendered HTML fragments in Inertia props.

## Inertia Runtime Package

Create a shared `capell-app/inertia-runtime` package. It should own:

- Laravel Inertia integration and middleware registration.
- Inertia root view.
- `HandleInertiaRequests` shared props.
- Vite asset versioning.
- SSR configuration and install guidance.
- Inertia page response renderer.
- Page component resolver conventions.
- Layout and section component conventions.
- Deferred prop registration.
- Partial reload boundaries.
- Form error and flash message conventions.
- Preview/workspace/shared state props.
- Public-output safety checks for serialized props.
- Starter Vue 3 and TypeScript application shell.

The runtime package should not own theme-specific visual identity.

## Inertia Theme Packages

Individual Inertia themes should extend the shared runtime and provide:

- Theme manifest with `runtime: "inertia"`.
- Theme definition, presets, preview image, tokens, and supported sections.
- Frontend components under a consistent namespace.
- Page components only where the visual structure differs.
- Layout components and section components.
- Theme-specific CSS/Tailwind sources.
- Optional presentation overrides for specific widgets or page types.
- Tests proving manifest correctness, component mapping, runtime registration,
  and safe anonymous/non-admin output.

An Inertia theme should not duplicate runtime boot files unless it is replacing
the application stack intentionally. The normal extension model is "runtime app
plus theme module", not "every theme is a separate Inertia app".

## Capell Frontend Changes

Capell Frontend should gain a response renderer layer:

```php
interface FrontendResponseRenderer
{
    public function runtime(): FrontendRuntime;

    public function render(FrontendPageViewData $page): Response;
}
```

The existing page controller should stop hard-coding Livewire as the only page
component path. After the frontend kernel resolves context, it should resolve
the active runtime and delegate to the matching renderer.

Expected renderers:

- `BladeFrontendResponseRenderer`
- `LivewireFrontendResponseRenderer`
- `InertiaFrontendResponseRenderer`

This improves Blade and Livewire as well as enabling Inertia. Their current
implementation paths become registered presentations rather than implicit
defaults.

## Capell Core Changes

Core should provide the shared metadata and registry primitives:

- `FrontendRuntime` enum.
- Manifest validation for `runtime` and `frontend` keys.
- Theme definition runtime metadata.
- Page presentation registry.
- Widget presentation registry.
- Public page/widget data builder contracts.
- Runtime support checks with clear exceptions.
- Backwards-compatible defaults for existing Blade/Livewire metadata.

Core should not import Inertia classes. Core should know that a theme is
`inertia`; the runtime package should know how Inertia works.

## Package Extension Points

Package authors should be able to contribute Inertia support without reaching
into a theme package:

- Register page-type data builders.
- Register widget public data builders.
- Register runtime presentations for Blade, Livewire, and Inertia.
- Contribute shared props through a controlled frontend prop hook.
- Mark expensive props as deferred.
- Declare partial reload groups.
- Add SEO/head data through structured SEO DTOs.

This lets Blog, Search, Form Builder, Content Sections, and future packages work
with multiple Inertia themes through the same public contract.

## Migration Policy

Existing Blade/Livewire behaviour should keep working.

Migration rules:

- Existing Blade views register as Blade presentations.
- Existing Livewire classes register as Livewire presentations.
- New Inertia components register as Inertia presentations.
- Existing page type metadata is adapted into the new presentation registry.
- Unsupported Inertia widgets fail loudly in development and tests.
- Production may optionally allow a legacy HTML fragment fallback, disabled by
  default.

Fallback shape:

```php
[
    'component' => 'Legacy/HtmlFragment',
    'props' => [
        'html' => $safeHtml,
    ],
]
```

This is a migration escape hatch, not the target architecture.

## Performance Model

For Inertia themes, HTML caching becomes secondary. Capell should optimize
content resolution and prop building while Inertia handles delivery.

The preferred model:

1. Capell resolves site, page, layout, theme, publishing, redirects, domains,
   and preview context.
2. Capell builds typed public data objects with cacheable data builders.
3. Inertia runtime exposes stable shared props.
4. Heavy content uses deferred props.
5. Index/listing pages use partial reload groups.
6. Vite handles asset versioning.
7. SSR is available for first paint and SEO-sensitive pages.
8. Capell cache invalidation clears data/prop caches by content graph impact.

Static HTML caching can remain for Blade/Livewire runtimes and possibly for
specific SSR responses later, but it should not drive the Inertia architecture.

## Public Output Safety

Inertia responses must follow the same safety rule as Blade output: anonymous
and non-admin users must never receive authoring metadata, selectors, model IDs,
field paths, package internals, permissions, or signed editor URLs.

The safety check must inspect serialized Inertia props as well as rendered HTML.
Preview and authoring state should be exposed only through authenticated preview
or beacon flows.

## Non-Goals

- Replacing Filament admin with Inertia.
- Sharing Filament layouts with public Inertia themes.
- Rendering all existing Blade widgets as HTML fragments inside Inertia.
- Making every theme a separate full Inertia application.
- Moving package domain logic into frontend components.
- Removing Blade or Livewire support.

## Risks And Mitigations

| Risk | Mitigation |
| --- | --- |
| Inertia becomes a special-case package hack | Put runtime metadata in Core and responder selection in Frontend. |
| Multiple Inertia themes duplicate runtime code | Build one shared runtime package and keep themes as visual/component modules. |
| Existing widgets lack Inertia support | Add presentation registry, fail loudly in dev/test, and provide explicit legacy fallback only for migration. |
| Page and widget prop shapes drift per theme | Define canonical public DTOs and extension hooks. |
| Core becomes coupled to Inertia | Core owns `runtime: inertia` metadata only; Inertia classes live in the runtime package. |
| SSR increases install complexity | Make SSR supported and documented from day one, with clear local/prod defaults. |
| Public props leak authoring data | Add serialized prop safety inspection and tests for anonymous/non-admin responses. |

## Implementation Phases

### Phase 1: Runtime Contract

- Add `FrontendRuntime`.
- Add theme manifest/runtime metadata.
- Add page and widget presentation registry contracts.
- Adapt existing Blade/Livewire metadata into registry entries.
- Add tests around runtime resolution and backwards compatibility.

### Phase 2: Shared Public Data

- Introduce canonical page, site, theme, navigation, layout, section, widget,
  SEO, and preview DTOs.
- Move current theme adapter behaviour toward data builders.
- Add extension hooks for package prop/data contributions.
- Add serialized public-output safety inspection.

### Phase 3: Response Renderers

- Add `FrontendResponseRenderer` contract.
- Move existing Livewire page response path into a renderer.
- Add Blade renderer where direct Blade responses are appropriate.
- Add Inertia renderer in the new runtime package.

### Phase 4: Inertia Runtime Package

- Add shared Inertia app shell.
- Configure Vite, SSR, shared props, asset versioning, error handling, flash,
  forms, deferred props, and partial reload conventions.
- Add root view and frontend middleware.
- Add package tests proving Inertia response payloads and safety.

### Phase 5: First Inertia Theme

- Build a first-party Inertia theme using the shared runtime.
- Implement standard Capell page, layout, navigation, widget, and section
  components.
- Add theme presets and Tailwind sources.
- Verify it uses Capell layouts/widgets/pages/custom types without duplicating
  CMS resolution logic.

### Phase 6: Package Coverage

- Add Inertia presentations for first-party public widgets and page types in
  Blog, Content Sections, Search, Form Builder, SEO Suite, and other frontend
  packages as needed.
- Add package-level tests ensuring supported widgets provide Inertia
  presentations.

## Success Criteria

- A Capell install can select a Blade, Livewire, or Inertia theme through the
  same theme system.
- The active runtime is explicit, validated, and inspectable.
- The Inertia runtime uses idiomatic Inertia features instead of HTML fragments.
- Multiple Inertia themes share runtime code and public data builders.
- First-party widgets/page types can declare Inertia presentations without
  depending on a specific theme.
- Anonymous and non-admin Inertia responses expose no authoring surface.
- Existing Blade/Livewire themes continue to work through compatibility
  adapters.
