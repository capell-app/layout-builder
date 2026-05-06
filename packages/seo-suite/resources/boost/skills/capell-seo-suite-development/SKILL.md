---
name: capell-seo-suite-development
description: Use when editing Capell SEO metadata, sitemaps, schema, audits, or AI briefs.
---

# Capell SEO Suite

Metadata panels, sitemaps, structured data, broken links, Search Console insights, and publish checks.

## Look

- `packages/seo-suite/src`
- `packages/seo-suite/docs`
- `packages/seo-suite/README.md`

## Rules

- Keep publish gates explainable and non-destructive.
- Sitemaps and schema must respect site/language scope.
- AI brief and metadata suggestions should be previewed before applying.
- Run `vendor/bin/pest packages/seo-suite/tests`.
