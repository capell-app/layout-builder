# Capell HTML Minify

**Product group:** Capell Foundation
**Tier:** Free

Conservative HTML minification for Capell frontend rendering and static page-cache writes.

This package owns the `voku/html-min` dependency for Capell. It binds the `Capell\Frontend\Contracts\HtmlMinifier` contract to a voku-backed implementation when installed, and it registers the legacy `frontend.minify` middleware alias as a no-op for existing frontend routes.

Minification preserves attribute order, class order, omitted tags, and quoted attributes so rendered HTML remains stable in tests.
