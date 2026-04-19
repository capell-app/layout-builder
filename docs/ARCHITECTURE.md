# Capell Themes — Architectural Boundaries

## Package responsibilities

### capell-app/frontend (external)

Owns the **CMS rendering pipeline** — the code that turns a Capell page record into HTML:

- **Per-page SEO**: Open Graph / Twitter Card tags and JSON-LD derived from the CMS page record (`BuildSocialMetaAction`, `PageMetaSchemaAction`, `SiteMetaSchemaAction`, `BreadcrumbsSchemaAction`).
- **Full-page HTML caching**: `PageCache` (Silber-based), `HtmlCacheMiddleware`, `PageCachePolicy`, `DeleteCachedPageController`.
- **Preview UI**: `workspace-preview-pill` Blade component, `ExitWorkspacePreviewController` — the visible bar shown to editors previewing a draft.
- **Rendering pipeline**: `FrontendContextReader` (page/site/layout/language context), `RenderHookRegistry` (inject HTML at named positions), asset registry, scoped singletons per request.

### capell-app/themes-core

Owns **theme infrastructure** — reusable utilities shared across all themes. Nothing here depends on the CMS page record; everything depends on `ThemeSettings` or is stateless.

| Module | Responsibility |
|---|---|
| `Analytics` | GA4 init script (`<script>` tag), UTM param capture; `AnalyticsProvider` interface for swapping backends |
| `Cache` | Tagged component-level cache wrapper (not full-page) |
| `SEO` | Default Open Graph/Twitter from ThemeSettings, JSON-LD builder, canonical URL, sitemap writer; `AbstractThemeSchemaGenerator` base for per-theme schema |
| `Search` | `SiteSearch` contract + `DatabaseSiteSearch` LIKE-query driver + `ScoutSiteSearch` Meilisearch/Algolia driver |
| `Accessibility` | ARIA attribute string helpers, WCAG 2.1 contrast ratio |
| `Preview` | HMAC-SHA256 token generation and validation |
| `Forms` | Honeypot, Cloudflare Turnstile widget + verification |
| `Language` | Hreflang tag generation, language resolution helpers |
| `Images` | `srcset`/`sizes` builder |
| `Performance` | Critical CSS inliner, asset optimizer |
| `Http` | `ThemeTokensController` — CSS custom property endpoint |
| `Theme` | `ThemeRegistrar` — dynamic theme discovery for admin UI |
| `Widgets` | `AbstractThemeWidget` — shared widget base class |

### capell-app/capell-theme-{corporate,agency,saas}

Owns **visual presentation** for one named theme:

- **Widgets**: Mosaic content blocks specific to the theme's design language, extending `AbstractThemeWidget`
- **SEO**: `StructuredDataGenerator` extending `AbstractThemeSchemaGenerator` — generates Organization/WebSite schema from theme-specific settings (not from the CMS page record)
- **Blade views**: layouts, components, CSS custom properties
- **ServiceProvider**: calls `ThemeRegistrar::register(key, label)` in `boot()` so the admin UI discovers it automatically

### capell-app/themes-admin

Owns **Filament UI** for the theme system — the settings page and schema form. Reads from `ThemeRegistrar` to populate the active-theme dropdown dynamically; has no rendering logic.

---

## Composition model

These layers compose in request order:

```
HTTP Request
  → frontend: resolve site/language/page, check HTML cache
  → frontend: render layout (via RenderHooks)
  → themes-core: GA4 init, hreflang, canonical URL, preview middleware
  → theme (corporate/agency/saas): render widgets, apply CSS tokens
  → frontend: generate per-page OG/Twitter/JSON-LD from page record
  → themes-core: generate Organization/WebSite JSON-LD from ThemeSettings
```

## Rules

1. **themes-core must not import from any theme** — core cannot know about `CorporateThemeSettings`.
2. **frontend must not import from themes-core** — they are siblings, not parent/child.
3. **themes may import from themes-core** — that is what themes-core is for.
4. **New schema types that depend on the CMS page record** → add to frontend's `*SchemaAction` classes.
5. **New schema types that depend on ThemeSettings** → add to `AbstractThemeSchemaGenerator` or a theme subclass.
6. **New reusable theme utilities** → add to themes-core with an interface.
7. **New themes** → call `ThemeRegistrar::register(key, label)` in the theme's `ServiceProvider::boot()`.
