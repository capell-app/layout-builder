# AI Discovery

AI Discovery is the SEO Suite surface that prepares Capell sites for AI search, answer engines, assistants, and crawler-controlled reuse. It is built from Capell's structured pages, translations, URLs, sitemap rules, SEO metadata, robots settings, and schema data instead of scraping rendered HTML back into Markdown.

## What It Publishes

- `/llms.txt` per active site and language, generated from published public pages.
- Optional `/llms-full.txt`, capped by page count and byte limit.
- Clean page Markdown at `/index.md` and `/{url}.md`.
- Optional `Accept: text/markdown` responses for normal public page URLs.
- `robots.txt` AI crawler rules for search crawlers, user-triggered fetchers, training crawlers, and broad crawlers.
- AI-readiness audit signals for missing summary, weak title, missing canonical, missing schema, JS-only content, disabled Markdown views, duplicate entity names, and noindex pages.

## Why Capell Can Do This Well

Capell owns the structured content. A generic package can reverse-convert HTML, but SEO Suite can render Markdown from the source page, translation, URL, and content structures. That gives cleaner output, fewer accidental prompts or editor-only artefacts, and better cache invalidation when a page, site, language, or AI Discovery profile changes.

## Editor Controls

Editors can manage AI Discovery from two places:

- Page SEO settings: include in AI index, summary, section, priority, exclusion reason, and optional Markdown override.
- AI Discovery admin page: browse public pages, see inclusion state, fill summaries, include/exclude pages, preview Markdown, and jump back to the page editor.

Site/language SEO settings control the wider output:

- Enable or disable `llms.txt`.
- Enable or disable `llms-full.txt`.
- Enable or disable page Markdown URLs.
- Enable or disable `Accept: text/markdown`.
- Set default include behaviour, default section, cache TTL, intro Markdown, and full-output limits.

## Crawler Policy

SEO Suite seeds crawler rules from package config and stores them in `ai_discovery_crawler_rules`. The package ships with policy presets:

- Search-visible, training-restricted: allow AI search and user-triggered fetchers while disallowing model-training and broad dataset crawlers.
- Open: allow all seeded AI crawler user agents.
- Restrictive: disallow all seeded AI crawler user agents.

Site-specific crawler rows override global rows with the same provider, user agent, and path. This lets one site disable a crawler without changing every site in a multi-site install.

Current seeded providers include OpenAI, Anthropic, Perplexity, Google Extended, and Common Crawl. Review crawler documentation regularly because names and behaviours can change.

## Cache And Invalidation

Generated AI Discovery documents are cached per site, domain context, language, output kind, and page where relevant. Snapshot rows store the content hash, byte size, cache key, generated time, expiry, and freshness status.

Cache is cleared or marked stale when:

- a page is saved or deleted;
- a site/language AI Discovery profile changes;
- a page AI Discovery profile changes.

Public pages continue to render normal HTML if a Markdown or AI Discovery request is not enabled for that site/language.

## Operating Checklist

- Keep public pages sitemap-visible when they should appear in AI Discovery.
- Add specific AI summaries for important pages.
- Use unique entity titles for pages that represent products, services, venues, people, or documentation concepts.
- Add canonical URLs and schema where the page represents a real entity.
- Avoid JS-only public content for pages that should be understood by crawlers.
- Preview Markdown before launch.
- Review `robots.txt` after changing the crawler policy preset.
- Revisit crawler defaults when OpenAI, Anthropic, Perplexity, Google, or Common Crawl update their public crawler guidance.

## Useful Files

- Config: `packages/seo-suite/config/capell-seo-suite.php`
- Admin page: `packages/seo-suite/src/Filament/Pages/AiDiscoveryPage.php`
- Page table: `packages/seo-suite/src/Filament/Pages/Tables/AiDiscoveryTable.php`
- `llms.txt`: `packages/seo-suite/src/Actions/GenerateLlmsTxtAction.php`
- `llms-full.txt`: `packages/seo-suite/src/Actions/GenerateLlmsFullTxtAction.php`
- Page Markdown: `packages/seo-suite/src/Actions/GeneratePageMarkdownAction.php`
- Readiness audit: `packages/seo-suite/src/Actions/BuildAiReadinessAuditAction.php`
- Robots rules: `packages/seo-suite/src/Actions/BuildAiRobotsTxtRulesAction.php`
