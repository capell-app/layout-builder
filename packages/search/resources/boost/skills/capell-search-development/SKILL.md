---
name: capell-search-development
description: Use when editing Capell Search drivers, frontend search, logging, or insights.
---

# Capell Search

Frontend search route, search drivers, result click tracking, query logs, and admin insights.

## Look

- `packages/search/src`
- `packages/search/docs`
- `packages/search/README.md`

## Rules

- Keep drivers behind contracts; do not hard-code one search backend.
- Query logging must respect settings and privacy expectations.
- Frontend search should stay cache-safe and site-scoped.
- Run `vendor/bin/pest packages/search/tests`.
