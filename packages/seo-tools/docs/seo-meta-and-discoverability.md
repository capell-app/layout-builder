# SEO Meta & Discoverability

The SEO Tools package owns the page-level meta pipeline: social-sharing tags, JSON-LD `@graph` structured data, robots/canonical handling, page SEO scoring, Search Console signals, and the `/llms.txt` AI-discoverability feed.

## Social meta

`Capell\SeoTools\Actions\BuildSocialMetaAction` composes the full social-sharing meta set:

- **Open Graph** — dynamic `og:type` per page type (`article` for Article/BlogPosting pages, `website` otherwise), plus `og:image:width`, `og:image:height`, `og:image:type`, `og:locale`, and `og:locale:alternate` entries.
- **Twitter Card** — `twitter:card`, `twitter:title`, `twitter:description`, `twitter:image`, `twitter:image:alt`, plus `twitter:site` from a configurable site-wide X/Twitter handle.
- **Article** — `article:published_time`, `article:modified_time`, `article:author` for article-type pages.
- **Admin overrides** — `social_title`, `social_description`, `social_image` fields in the translation SEO meta, with fall-back to meta title / description / primary image.

Enum: `Capell\SeoTools\Enums\OpenGraphTypeEnum` decides which `og:type` a page type emits.

DTO: `Capell\SeoTools\Data\SocialMetaData`.

## Structured data (`@graph`)

`Capell\SeoTools\Actions\SchemaGraphAction` emits a single JSON-LD `@graph` script per page with stable cross-referenced `@id`s:

- `Organization` — site owner, logo, sameAs.
- `WebSite` — with `SearchAction` pointing at the internal search route when enabled.
- `WebPage` — canonical, primary image, breadcrumb reference.
- `Article` / `BlogPosting` — auto-populated author, publisher, datePublished, dateModified, image.
- `BreadcrumbList` — from the page's ancestor chain.

Entities are keyed by `Capell\SeoTools\Enums\SchemaEntityTypeEnum::toId()`, which produces stable per-entity URIs so cross-references survive URL changes.

Opt-in is per component via `Capell\SeoTools\Enums\MetaSchemaEnum::Graph`. Legacy per-component schema renderers continue to work unchanged — projects can migrate on their own schedule.

The graph is rendered through `resources/views/components/schema/graph.blade.php` (with sibling component views for `website`, `webpage`, `breadcrumb`, `image`, `organization`).

DTO: `Capell\Core\Data\SchemaGraphData` (lives in `capell/core` because the data shape is shared with non-SEO callers).

Schema requirements are also surfaced to editors through the [schema template registry](schema-templates.md).

## Robots & canonical

- `Capell\SeoTools\Enums\RobotsDirectiveEnum` replaces inline checkbox options, emitting combinations like `index, follow`, `noindex, nofollow`, `noindex, follow`, etc.
- A canonical URL text override field on the translation SEO meta lets editors set an explicit canonical when the auto-computed value is wrong (e.g. campaign landing pages).

## `/llms.txt` — AI discoverability

`Capell\SeoTools\Actions\GenerateLlmsTxtAction` + `LlmsTxtController` publish a site content index at `/llms.txt` for AI crawlers:

- Generated from **published, indexable** pages only (respects `noindex` directives).
- Entry shape: `Capell\Core\Data\LlmsTxtEntryData` (title, URL, description, last-modified).
- Cached for 1 hour (key scoped by site + language).
- Toggleable per site via the `llms_txt_enabled` site meta.

## SEO report

`Capell\SeoTools\Actions\BuildPageSeoReportAction` combines metadata checks, previews, internal-link suggestions, schema coverage, and Search Console insights into `PageSeoReportData`. The page editor panel and SEO audit table both consume this report shape.

See [SEO intelligence](seo-intelligence.md) for score semantics, publish-check integration, redirect opportunities, and AI content briefs.

## Related files

| Concern           | File                                                                                                |
| ----------------- | --------------------------------------------------------------------------------------------------- |
| Social meta       | `src/Actions/BuildSocialMetaAction.php`, `src/Data/SocialMetaData.php`                              |
| Schema `@graph`   | `src/Actions/SchemaGraphAction.php`, `src/Enums/SchemaEntityTypeEnum.php`                           |
| Schema views      | `resources/views/components/schema/{graph,website,webpage,breadcrumb,image,organization}.blade.php` |
| SEO reports       | `src/Actions/BuildPageSeoReportAction.php`, `src/Data/PageSeoReportData.php`                        |
| Schema templates  | `src/Support/SchemaTemplates/SchemaTemplateRegistry.php`                                            |
| Search Console    | `src/Actions/BuildPageSearchConsoleInsightsAction.php`, `src/Support/SearchConsole/*`               |
| llms.txt          | `src/Actions/GenerateLlmsTxtAction.php`, `src/Http/Controllers/LlmsTxtController.php`               |
| Enums             | `src/Enums/{OpenGraphTypeEnum,RobotsDirectiveEnum,SchemaEntityTypeEnum,MetaSchemaEnum}.php`         |
| Hook registration | `src/Support/RenderHooks/RegisterSeoHeadHooks.php`                                                  |
| Shared DTOs       | `capell/core` → `src/Data/{SchemaGraphData,LlmsTxtEntryData}.php`                                   |

For editor-facing checks, start with [SEO intelligence](seo-intelligence.md).
