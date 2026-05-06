# SEO Publish Gates

This focused guide extends [Overview](overview.md) for the SEO Suite package.

## Purpose

SEO Suite can warn or block publishing when page metadata, robots settings, canonical URLs, schema, internal links, social images, redirects, or Search Console checks do not meet the configured mode.

## Default Checks

- `meta_title`: blocker.
- `meta_description`: blocker.
- `robots`: blocker.
- `canonical`: warning.
- `schema`: warning.
- `internal_links`: warning.
- `social_image`: warning.
- `redirects`: blocker.
- `search_console`: ignored.

## Workflow

1. Review the Page SEO panel.
2. Refresh page or site SEO snapshots where needed.
3. Fix blocker checks before publishing.
4. Treat AI-generated suggestions as draft material for human review.
5. Regenerate sitemap output after route or content changes.

## Pitfalls

- Search Console checks need credentials and a property URL.
- AI creator output should not bypass editorial review.
- Publish gate modes should match the team's publishing risk tolerance.
