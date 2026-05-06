# Frontend Authoring

Frontend Authoring replaces the old frontend toolbar package. It keeps the beacon route and adds cache-safe in-page editing for rendered frontend pages.

See [In-page editing](in-page-editing.md) for the full admin flow, screenshot, screenshot-package requirements, and browser-test contract.

## Runtime Flow

1. The frontend page renders normally and can be served from HTML cache.
2. The frontend beacon client posts the current URL to `capell-frontend.beacon`.
3. The beacon resolves the `PageUrl` and checks admin access.
4. Only for admins, the beacon returns the authoring script and editable region metadata.
5. The browser decorates matching DOM selectors with edit controls.
6. Editing opens a signed route in a modal.
7. Saving updates the field, clears every cached URL recorded for that model, and refreshes the page.

## Cache Rule

Authoring metadata is not rendered into cached Blade, theme output, or public frontend assets. Editable regions are selector-based and returned only from an authenticated admin beacon response. Anonymous and non-admin users must not receive editor HTML, editor JavaScript, labels, selectors, model IDs, field paths, package hints, or signed editor URLs.

If another frontend package wants editable content, it registers regions here. It should not smuggle authoring data into its Blade templates.

## Built-In Regions

- Page title: `Translation.title`
- Page description: `Translation.meta.description`
- Page content: `Translation.content`

Additional packages can register regions via the `capell-frontend-authoring:editable-regions` tag.
