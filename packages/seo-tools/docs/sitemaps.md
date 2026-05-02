# Sitemaps

The SEO Tools package owns the entire sitemap stack: the public HTML/XML sitemap pages, the on-disk XML generator, the incremental regeneration command, and chunked sitemap-index pagination.

## HTML sitemap

Served by the Livewire component `Capell\SeoTools\Livewire\Page\Sitemap` using the view `capell::livewire.page.sitemap`.

- Builds a tree of pages via `SitemapBuilder` and passes `results` to `capell::components.sitemap.sitemap-page` for recursive rendering.
- Each page is rendered as an anchor with its label and a child count when it has descendants.
- CSS for the vertical layout is inlined under an `@once` block.

HTML responses may be minified by `capell-app/html-minify` when `capell-frontend.minify_html` is enabled. Minification is conservative — attribute order and quotes are preserved so HTML-comparison tests stay stable.

## XML sitemap

The same Livewire component serves the XML sitemap when the request URL ends with `-xml`.

- `setup()` intercepts `*-xml` URLs and throws `HttpResponseException` with the XML response.
- `returnXmlSitemap()` reads the generated XML file from the configured storage disk and directory.
- It computes a SHA-256 digest of the file contents and serves a weak ETag `W/"<digest>"`.
- If `If-None-Match` matches the weak or strong ETag (or `*`), a `304 Not Modified` is returned with `ETag`, `Cache-Control`, `Expires`, and `Last-Modified` headers.
- Otherwise a `200 OK` is returned with the XML body and the same headers.
- Files larger than 1 MB are streamed via `response()->stream()`.

## Generation

Generator service: `Capell\SeoTools\Support\Sitemap\XmlSitemapGenerator` builds per-domain sitemap XML files.

The generator iterates a site's domains and listable pages, adding URLs with their `lastmod`, `changefreq`, and `priority` attributes, and writes files under the configured storage disk and directory (defaults: `disk=local`, `directory=sitemaps`).

| Method                         | Purpose                                                                  |
| ------------------------------ | ------------------------------------------------------------------------ |
| `generate(Site $site): string` | Produce sitemaps and return the XML of the first domain's file.          |
| `process(...)`                 | Same as `generate` with progress callbacks for CLI / ops.                |
| `processIncremental(...)`      | Skip domains whose pages haven't changed — see Incremental Regeneration. |
| `delete(Site $site)`           | Remove existing sitemap files, chunk files, and state files per domain.  |

### Configuration

| Key                                | Env                                | Default        | Purpose                                                                              |
| ---------------------------------- | ---------------------------------- | -------------- | ------------------------------------------------------------------------------------ |
| `capell.sitemap.disk`              | —                                  | `local`        | Storage disk name.                                                                   |
| `capell.sitemap.directory`         | —                                  | `sitemaps`     | Base directory under the disk.                                                       |
| `capell.sitemap.max_urls_per_file` | `CAPELL_SITEMAP_MAX_URLS_PER_FILE` | `50000`        | URLs per sitemap file before splitting into chunks.                                  |
| `capell.sitemap.xml_path`          | `CAPELL_SITEMAP_XML_PATH`          | `/sitemap-xml` | Public path appended to the domain base URL when constructing chunk `<loc>` entries. |

These keys live in `capell/core`'s `config/capell.php`.

## Incremental regeneration

```
php artisan capell:xml-sitemap --incremental
```

Skips any domain whose pages have not changed since the last run.

- State is stored per domain in `{sitemap_directory}/.state/{domainKey}.json` as a URL→lastmod map.
- On each run the generator builds the full page list, derives the current URL→lastmod map, and compares it against the stored state.
- If nothing has changed the domain's XML is left untouched and the table row shows **skipped**.
- If any URL was added, removed, or has a different `lastmod`, the XML is rewritten and the state file is updated.
- Full runs (`capell:xml-sitemap` without `--incremental`) also save state after writing, so the next incremental run has an accurate baseline.

```
 Domain          | Language | URLs | File                   | Status
 example.com     | English  | 342  | sitemaps/...xml        | regenerated
 fr.example.com  | French   | 340  | —                      | skipped
```

Command source: `src/Console/Commands/XmlSitemapCommand.php`.

## Sitemap index pagination

When a domain's sitemap exceeds `capell.sitemap.max_urls_per_file` URLs, the generator splits output into numbered chunk files and writes a `<sitemapindex>` index:

- Chunk files: `{domainKey}-p1.xml`, `{domainKey}-p2.xml`, …
- Index file: `{domainKey}.xml` (replaces the standard `<urlset>` file)
- Chunk `<loc>` values: `{domain.full_url}{capell.sitemap.xml_path}?p=N`

The Livewire `Sitemap` component serves chunk files transparently via `?p=N` — same URL, same `-xml` suffix, different `p` value.

## Troubleshooting

- **304 not returned** — ensure the client sends `If-None-Match` with `W/"<digest>"`, `"<digest>"`, the raw digest, or `*`. Confirm the sitemap XML file exists on the configured disk and directory.
- **HTML differences in tests** — set `capell-frontend.minify_html=false` in testing, or rely on the conservative `Capell\Frontend\Contracts\HtmlMinifier` binding provided by `capell-app/html-minify`.

## References

- `src/Livewire/Page/Sitemap.php` — public HTML + XML serving
- `src/Filament/Pages/SitemapPage.php` — admin sitemap viewer
- `src/Support/Sitemap/XmlSitemapGenerator.php` — file generation
- `src/Support/Sitemap/SitemapBuilder.php` — page tree assembly
- `src/Support/Sitemap/SitemapStateStore.php` — incremental state
- `src/Console/Commands/XmlSitemapCommand.php` — `capell:xml-sitemap`
- `resources/views/livewire/page/sitemap.blade.php` and `resources/views/components/pages/sitemap.blade.php` — render templates
