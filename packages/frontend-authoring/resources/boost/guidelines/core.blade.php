# Capell Frontend Authoring Frontend authoring bridge, beacon, and in-page
editing. - Composer: `capell-app/frontend-authoring` - Keep authoring metadata
out of Blade, theme output, cached HTML, and public assets. - Return editable
regions only from an authenticated admin beacon response after page load. -
Non-admin users must receive no authoring HTML, JavaScript, selectors, model
IDs, field paths, or signed URLs. - Clear all URLs recorded in
`CacheEnum::modelUrlCacheKey()` when an edited model is saved.
