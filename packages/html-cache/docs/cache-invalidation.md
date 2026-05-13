# HTML Cache Invalidation

HTML Cache stores two things: the cached HTML file and an index of which models were seen while rendering a URL. Clear both when a package changes content that may already be cached.

## Main Actions

| Action                          | Use it when                                                                  |
| ------------------------------- | ---------------------------------------------------------------------------- |
| `ClearCachedUrlAction`          | You know the public URL that should be removed from the cache.               |
| `ClearCachedPageUrlsAction`     | You have a collection of URLs and want a simple count of cleared entries.    |
| `ClearCachedUrlsForModelAction` | You changed one model and want to clear every cached URL that referenced it. |
| `RecordCachedModelUrlsAction`   | A render pass knows which models contributed to a cached URL.                |
| `GenerateStaticSiteAction`      | A full static generation run is needed for one `Site`.                       |
| `GenerateStaticSitesAction`     | Static generation should run for all selected sites.                         |

The admin cache map reads `cached_model_urls`; it does not scan public HTML on every request. If a package renders model-backed content but never records dependencies, cache invalidation can only work by URL.

## Clear One URL

```php
use Capell\HtmlCache\Actions\ClearCachedUrlAction;

ClearCachedUrlAction::run('https://example.test/about', refresh: true);
```

`refresh: true` dispatches `Capell\Core\Actions\VisitUrlAction` after the file and index rows are removed. Use it only when the URL should be warmed immediately.

## Clear Every URL For A Model

```php
use Capell\HtmlCache\Actions\ClearCachedUrlsForModelAction;

$cleared = ClearCachedUrlsForModelAction::run($article, refresh: false);
```

This looks up rows where `cacheable_type` matches `$article->getMorphClass()` and `cacheable_id` matches the model key. If the model was never recorded with `RecordCachedModelUrlsAction`, the action returns `0`.

## Record Dependencies During Rendering

```php
use Capell\HtmlCache\Actions\RecordCachedModelUrlsAction;

RecordCachedModelUrlsAction::run($url, [
    $article->getMorphClass() => [$article->getKey()],
    $author->getMorphClass() => [$author->getKey()],
]);
```

`RecordCachedModelUrlsAction` resolves the site domain and path from the URL, upserts the current dependencies, and removes stale dependencies for the same URL hash.

## Configuration

| Key                                                     | Purpose                                                                 |
| ------------------------------------------------------- | ----------------------------------------------------------------------- |
| `capell-html-cache.enabled`                             | Turns HTML cache behaviour on or off.                                   |
| `capell-html-cache.write_enabled`                       | Allows cache writes. Disable this when investigating output safety.     |
| `capell-html-cache.minify_html`                         | Controls minification before writing cached HTML.                       |
| `capell-html-cache.cache_ttl`                           | Default cache TTL.                                                      |
| `capell-html-cache.cache_skip_authenticated`            | Keeps authenticated responses out of the public cache.                  |
| `capell-html-cache.model_event_registration_mode`       | Controls model event registration timing; default is `deferred`.        |
| `capell-html-cache.static_generation.internal_requests` | Lets static generation render through the current Laravel kernel.       |
| `capell-html-cache.public_html_authoring_markers`       | Strings used by diagnostics to detect authoring leakage in public HTML. |

## Console

```bash
vendor/bin/pest packages/html-cache/tests --configuration=phpunit.xml
```

The package command is:

```text
capell:static-site {--site=} {--internal} {--refresh}
```

`--internal` renders through the current Laravel kernel. `--refresh` deletes affected cached files before rendering.

## Extension Point

Implement `Capell\HtmlCache\Contracts\PageCacheNotifiable` when a class needs to react after a page cache entry is recorded:

```php
use Capell\HtmlCache\Contracts\PageCacheNotifiable;
use Illuminate\Database\Eloquent\Model;

final class SearchIndexCacheNotifier implements PageCacheNotifiable
{
    public function notifyPageCached(Model $model): void
    {
        // Keep side effects small; this runs from cache recording paths.
    }
}
```

Keep notifications cheap. Cache writes happen on public page renders, so slow work belongs on a queue.

## Public Output Safety

HTML Cache must remain safe for anonymous visitors, signed-in users, admins, crawlers, and static exports. Do not put authoring attributes, model IDs, signed editor URLs, field paths, package names, or permission hints into cached HTML. If a package needs admin editing, use Frontend Authoring's post-load beacon.
