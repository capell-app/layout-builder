# capell-app/themes-core

Shared cross-cutting services used by every Capell theme (Corporate, Agency, SaaS).
Install this package directly only when building a custom theme; the three bundled
themes pull it in as a dependency automatically.

## What's inside

| Module            | Namespace         | Description                                                                |
| ----------------- | ----------------- | -------------------------------------------------------------------------- |
| **Accessibility** | `…\Accessibility` | `AriaHelper` (ARIA attribute strings), `ContrastChecker` (WCAG 2.1 ratio)  |
| **Analytics**     | `…\Analytics`     | `GoogleAnalytics4` (GA4 script), `UtmCollector` (UTM parameter capture)    |
| **Cache**         | `…\Cache`         | `ThemeCache` — tagged cache wrapper with remember / forget / flush         |
| **Data**          | `…\Data`          | `ThemeSettings` — spatie/laravel-data DTO for active theme + brand colors  |
| **Forms**         | `…\Forms`         | `HoneypotField` (spam trap), `Turnstile` (Cloudflare widget + verify)      |
| **Images**        | `…\Images`        | `ResponsiveImage` — srcset/sizes builder                                   |
| **Language**      | `…\Language`      | `HreflangGenerator`, `LanguageManager`                                     |
| **Mail**          | `…\Mail`          | `FormSubmissionNotification`, `NewsletterWelcome` Mailable classes         |
| **Middleware**    | `…\Middleware`    | `PreviewMiddleware` — validates HMAC-signed preview tokens                 |
| **Mobile**        | `…\Mobile`        | `TouchTargets` — WCAG 2.5.5 44 px touch target helpers                     |
| **Performance**   | `…\Performance`   | `CriticalCssInliner`, `AssetOptimizer`                                     |
| **Preview**       | `…\Preview`       | `PreviewMode` — token generation + validation for draft previews           |
| **SEO**           | `…\SEO`           | `StructuredDataBuilder`, `SitemapGenerator`, `CanonicalUrl`, `SocialCards` |
| **Search**        | `…\Search`        | `DatabaseSiteSearch` — LIKE-query search with pagination + highlighting    |

## Installation

```bash
composer require capell-app/themes-core
```

The service provider is discovered automatically. If you are developing against
a local checkout, add a path repository to `composer.local.json`:

```json
{
    "repositories": [{ "type": "path", "url": "packages/themes-core" }]
}
```

## Usage examples

### Search

```php
use Capell\Themes\Core\Search\DatabaseSiteSearch;

$search = new DatabaseSiteSearch($db, table: 'pages');
$paginator = $search->search('laravel', perPage: 10, page: 1);

foreach ($paginator as $result) {
    echo $result->title;   // string
    echo $result->url;     // '/slug'
    echo $result->excerpt; // truncated, up to 200 chars
    echo $result->score;   // float — keyword frequency score
}
```

Highlight matches for display:

```php
$highlighted = $search->highlight($result->excerpt, $query);
// returns HTML-escaped text with <mark>…</mark> around matches
```

### Preview mode

```php
use Capell\Themes\Core\Preview\PreviewMode;

$preview = new PreviewMode(secret: config('app.key'));

// In a controller or command — generate a signed URL
$url = $preview->signedUrl('/pages/my-draft', baseUrl: 'https://example.com', minutes: 60);

// In PreviewMiddleware — validate the token from ?preview=…
$valid = $preview->validateToken($token, path: '/pages/my-draft');
```

### Contrast checker

```php
use Capell\Themes\Core\Accessibility\ContrastChecker;

$checker = new ContrastChecker;
$ratio = $checker->ratio('#ffffff', '#0e1b4c'); // float
$checker->meetsAA($ratio);      // bool — ≥ 4.5
$checker->meetsAALarge($ratio); // bool — ≥ 3.0
$checker->meetsAAA($ratio);     // bool — ≥ 7.0
```

### Structured data (JSON-LD)

```php
use Capell\Themes\Core\SEO\StructuredDataBuilder;

$ld = StructuredDataBuilder::new()
    ->organization('Acme Corp', 'https://acme.example')
    ->address(street: '1 Main St', city: 'Boston', region: 'MA', country: 'US')
    ->render(); // returns <script type="application/ld+json">…</script>
```

## Tests

```bash
php -d memory_limit=-1 vendor/bin/pest packages/themes-core/tests
```

See [../../TESTING.md](../../TESTING.md) for full testing instructions.

## License

MIT
