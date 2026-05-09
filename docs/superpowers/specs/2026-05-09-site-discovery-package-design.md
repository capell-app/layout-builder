# Site Discovery Package Design

## Summary

Extract sitemap functionality out of SEO Suite into a new required package named `capell-app/site-discovery`. The package should not be framed as only XML sitemap generation. Its core responsibility is deciding which frontend pages and URLs are public, indexable, and safe to expose to discovery surfaces.

SEO Suite will require Site Discovery and consume its public discovery contract for AI Discovery. SEO Suite keeps metadata, schema, audits, Search Console insights, broken-link reporting, publish gates, and AI-facing SEO output such as `llms.txt`.

## Problem

SEO Suite currently uses sitemap classes for two separate concerns:

- Sitemap output: HTML sitemap page, XML sitemap files, admin sitemap tooling, sitemap commands, and regeneration listeners.
- Discoverability policy: finding pages that are published, visible, accessible, not hidden, and not marked `noindex`.

Moving XML sitemap code into a simple `capell-app/sitemap` package would leave AI Discovery depending on sitemap internals, which names the boundary after one output instead of the domain rule both features need. Removing AI Discovery would avoid the dependency, but it would weaken SEO Suite and leave the real abstraction problem unsolved.

## Decision

Create `capell-app/site-discovery` as a foundational Search & SEO package.

The package owns:

- Discoverable page and URL querying.
- Public contracts/data for discoverable entries.
- Extension points for packages to contribute discoverable URLs.
- XML sitemap generation.
- HTML sitemap page rendering.
- Sitemap admin page, header actions, admin tool, Livewire components, commands, and lifecycle listeners.
- Sitemap page type creation/interception.
- The `icamys/php-sitemap-generator` dependency.

SEO Suite requires Site Discovery and owns:

- Page and site SEO metadata panels.
- Canonical URL, robots directives, Open Graph, social card, and schema tooling.
- SEO audit snapshots, scoring, and publish checks.
- Broken-link and not-found URL reporting.
- Search Console sync and insights.
- AI Creator/content brief/title/meta assistance.
- AI Discovery profiles, crawler policy, `robots.txt`, `llms.txt`, `llms-full.txt`, page Markdown output, and AI readiness audits.

## Package Boundary

Site Discovery exposes a domain API such as:

- `DiscoverPublicPagesAction::run(Site $site, Language $language)`
- `DiscoverPublicUrlsAction::run(Site $site, Language $language)`
- `DiscoverablePageData`
- `DiscoverableUrlData`
- `DiscoverableUrlSource`, a contributor contract for packages that add non-page URLs to discovery output

These public API names are the default implementation target. If implementation exposes a better existing Capell naming pattern, the plan must call out the replacement explicitly before code is changed. The public API must avoid sitemap-specific naming for page discovery. Sitemap generation can depend on discovery, not the other way around.

SEO Suite must not import `Capell\SiteDiscovery\Support\Sitemap\...` classes. It should depend only on the discovery-facing action/contracts/data. The new sitemap package internals can continue to use `Support\Sitemap\...` for XML-specific implementation.

## Migration Shape

Move these sitemap-specific concerns from `packages/seo-suite` to `packages/site-discovery`:

- `GenerateSitemapAction`
- `XmlSitemapCommand`
- `Sitemapable`
- `SitemapPageData`, `SitemapUrlItemData`, `SiteMapData` where still needed for sitemap output
- `SitemapCacheKey`
- `SitemapGeneratorException`
- Sitemap Filament pages and extenders
- Sitemap Livewire page/tool components
- `SitemapAdminTool`
- `SitemapPageCreator`
- `SitemapPageTypeInterceptor`
- `SitemapLoader`
- `Support/Sitemap/*`
- Sitemap views and tests

Convert `PagesForSitemap` into a neutral discovery query/action in Site Discovery. XML sitemap generation and SEO Suite AI Discovery both call the neutral API.

Update SEO Suite by:

- Replacing references to `PagesForSitemap` with the Site Discovery public discovery API.
- Removing sitemap service provider registration: sitemap command, sitemap default page, sitemap registry, sitemap listeners, sitemap page type, sitemap Livewire components, sitemap Filament page, and sitemap admin actions.
- Adding `capell-app/site-discovery` as a required dependency in `composer.json` and `capell.json`.
- Removing `icamys/php-sitemap-generator` from SEO Suite.
- Updating README/package metadata so SEO Suite no longer claims to own sitemap generation directly.

Update root package metadata by:

- Adding PSR-4 autoload and test namespace entries for `Capell\SiteDiscovery`.
- Adding package path metadata and local composer overlay entries where required.
- Registering `capell-app/site-discovery` in package discovery docs/metadata in the same style as active packages.

## Data Flow

For XML sitemap output:

1. Site Discovery receives a site/language/domain context.
2. It resolves discoverable public URLs through its discovery API.
3. XML sitemap services write files, chunk indexes, state, and cache markers.
4. Sitemap listeners regenerate when relevant page/site events occur.

For SEO Suite AI Discovery:

1. SEO Suite resolves the AI Discovery site profile.
2. SEO Suite calls Site Discovery's neutral public-page discovery API.
3. SEO Suite syncs AI Discovery page profiles for those pages.
4. SEO Suite filters by AI profile inclusion and renders `llms.txt`, page Markdown links, and readiness output.

## Testing

Site Discovery tests should cover:

- Discoverable pages exclude hidden, inaccessible, unpublished, disabled-type, and `noindex` pages.
- XML sitemap generation, chunking, sitemap indexes, ETags, missing-file responses, and incremental state.
- HTML sitemap page output does not expose editor/admin metadata.
- Lifecycle listeners regenerate relevant site sitemap output.
- Package boundaries prevent Site Discovery from importing SEO Suite.

SEO Suite tests should cover:

- AI Discovery uses Site Discovery's public discovery API.
- `llms.txt` excludes `noindex`/non-discoverable pages through the new API.
- SEO Suite no longer registers sitemap pages, sitemap commands, sitemap Livewire components, or sitemap admin tools.
- Package boundaries allow SEO Suite to import only Site Discovery public contracts/actions/data, not sitemap internals.

Run the narrow package suites first:

```bash
vendor/bin/pest packages/site-discovery/tests --configuration=phpunit.xml
vendor/bin/pest packages/seo-suite/tests --configuration=phpunit.xml
```

Then run broader checks once the split is stable.

## Risks

The highest-risk area is namespace and autoload churn because many sitemap classes and tests currently live under `Capell\SeoSuite`. The implementation should move code in small slices and keep tests passing at each boundary.

The second risk is accidentally making Site Discovery depend on SEO Suite robots enums. Site Discovery should either own the relevant discoverability enum/value object, or check raw page metadata for stable public values such as `noindex` without importing SEO Suite.

The third risk is installer/package metadata drift. `composer.json`, `composer.local.json`, package manifests, README files, and package recommendation metadata should be updated together.

## Out Of Scope

- Removing AI Discovery from SEO Suite.
- Renaming SEO Suite.
- Changing public URL semantics beyond the existing hidden/published/type/noindex behavior.
- Reworking Core package APIs unless a narrow extension point is required for package-contributed discoverable URLs.
