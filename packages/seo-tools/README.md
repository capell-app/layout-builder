# Capell SEO Tools

**Product group:** Capell Search & SEO
**Tier:** Premium

SEO Tools gives Capell sites the discoverability layer most CMS builds leave until too late: XML sitemaps, social metadata, JSON-LD, robots controls, `llms.txt`, editor SEO scoring, redirect opportunities, Search Console insights, and AI-assisted SEO briefs.

## When to install it

Install SEO Tools when editors need to manage how pages appear in search, social previews, AI discovery tools, and structured data outputs.

## Quick install

```bash
composer require capell-app/seo-tools
php artisan capell:seo-tools-install
php artisan capell:seo-tools-setup
```

The package registers through Laravel discovery. It depends on `capell-app/admin` and `capell-app/frontend`.

## What appears in the admin

| Area                 | What editors can do                                                                                                         |
| -------------------- | --------------------------------------------------------------------------------------------------------------------------- |
| Page SEO panel       | Review score, previews, issues, passed checks, canonical, robots, links, schema, redirects, Search Console, and brief ideas |
| Page SEO fields      | Improve titles, descriptions, social previews, robots directives, and canonical URLs                                        |
| SEO audit            | Scan pages by score, critical issues, warnings, schema coverage, and setup state                                            |
| Broken link handling | Review failing URLs and create redirect-manager entries from high-value opportunities                                       |
| Search Console       | Surface clicks, impressions, CTR, and position when the integration is configured                                           |
| Settings             | Configure AI-assisted SEO prompts, limits, provider defaults, schema, and sitemaps                                          |
| Dashboard/widgets    | Inspect AI usage and generation history when enabled                                                                        |

## What developers get

- Actions for page SEO reports, scoring, social metadata, page/site schema, breadcrumbs, sitemaps, and `llms.txt`.
- `StructuredDataBuilder`, `CanonicalUrl`, `SocialCards`, and sitemap support classes.
- A schema template registry for default or project-specific JSON-LD requirements.
- Search Console and publish-check contracts that keep integrations behind package boundaries.
- Redirect-opportunity and internal-link suggestion builders for editor workflows.
- AI generation history models, settings, rate limiting, and event hooks.
- Extenders that add SEO reports, publish guidance, and AI-assist controls to Capell admin forms.

## Configuration

The main config file is `config/capell-seo-tools.php`. Configure model defaults, prompt templates, rate limits, sitemap behavior, schema templates, Search Console credentials, provider settings, and publish-gate severity modes there.

Publish gate modes live under `publish_gates`. Defaults map critical issues to blockers and warning/notice issues to warnings. Per-check overrides can set any SEO issue key to `blocker`, `warning`, or `ignored`:

```php
'publish_gates' => [
    'default' => [
        'critical' => 'blocker',
        'warning' => 'warning',
        'notice' => 'warning',
    ],
    'checks' => [
        'search_console' => 'ignored',
    ],
],
```

## Deeper docs

- [SEO metadata and discoverability](docs/seo-meta-and-discoverability.md)
- [SEO intelligence](docs/seo-intelligence.md)
- [Schema templates](docs/schema-templates.md)
- [Search Console](docs/search-console.md)
- [Sitemaps](docs/sitemaps.md)
- [OpenAI / AI-assisted SEO integration](../../docs/openai-integration.md)
