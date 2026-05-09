# Site Discovery

Status: **Available, discovery-owning** · Kind: **package** · Tier: **premium** · Bundle: **search-seo** · Contexts: **admin, frontend, console** · Product group: **Capell Search & SEO**

Site Discovery resolves public, indexable, discoverable Capell pages and exposes HTML and XML sitemap outputs.

## What This Plugin Adds

- Public discoverable page and URL APIs.
- HTML sitemap page type and frontend component.
- XML sitemap generation with chunking and incremental state.
- Sitemap admin page, admin actions, and generation tool.
- Lifecycle listeners that regenerate sitemap output when pages or sites change.

## Built With

- [PHP Sitemap Generator](https://github.com/icamys/php-sitemap-generator) - XML sitemap generation.

## Quick Start

1. Install the package with `composer require capell-app/site-discovery`.
2. Run package discovery/installation in the host app.
3. Generate sitemap output with `capell:xml-sitemap`.
