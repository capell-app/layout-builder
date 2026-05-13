---
applyTo: 'packages/frontend-authoring/**,packages/*/resources/views/**,packages/*/src/**,packages/*/tests/**'
---

# Frontend Authoring Safety

- Anonymous and non-admin public responses must never expose editor HTML, editor JavaScript, editable markers, model IDs, field paths, selectors, permissions, package names, or signed editor URLs.
- Public pages must load as ordinary public HTML. Authoring UI is post-load admin behavior, added only after an authenticated admin beacon response.
- Cached HTML must remain safe for anonymous visitors, signed-in non-admin users, admins, crawlers, and static exports.
- When changing frontend packages, theme packages, page cache, beacon behavior, or public Blade, preserve or add tests for anonymous and non-admin responses.
