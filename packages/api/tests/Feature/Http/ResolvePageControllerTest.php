<?php

declare(strict_types=1);

use Capell\Api\Providers\ApiServiceProvider;
use Capell\Core\Database\Factories\TranslationFactory;
use Capell\Core\Facades\CapellCore;
use Capell\Core\LayoutBuilder\Contracts\PublicWidgetPayloadResolver;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Core\Models\Widget;
use Illuminate\Support\Facades\URL;

use function Pest\Laravel\getJson;

it('returns default page fields without layout', function (): void {
    [$pageUrl] = createPublicApiPage('/terms', [
        'title' => 'Terms',
        'content' => '<p>Terms content</p>',
    ]);

    getJson(apiResolveUrl(['url' => $pageUrl->url]))
        ->assertOk()
        ->assertExactJson([
            'data' => [
                'url' => '/terms',
                'title' => 'Terms',
                'content' => '<p>Terms content</p>',
            ],
        ]);
});

it('serves the v1 route and emits public api contract headers', function (): void {
    [$pageUrl, $page, $language, $site] = createPublicApiPage('/terms', [
        'title' => 'Terms',
        'content' => '<p>Terms content</p>',
    ]);

    URL::forceRootUrl('https://example.com');

    getJson(route('capell-api.v1.pages.resolve', ['url' => $pageUrl->url]))
        ->assertOk()
        ->assertHeader('X-Capell-Api-Version', 'v1')
        ->assertHeader('X-Capell-Cache-Tags', sprintf('api,site:%s,language:%s,page:%s', $site->getKey(), $language->getKey(), $page->getKey()))
        ->assertJsonPath('data.url', '/terms')
        ->assertJsonPath('data.title', 'Terms');

    getJson(route('capell-api.pages.resolve', ['url' => $pageUrl->url]))
        ->assertOk()
        ->assertHeader('X-Capell-Api-Version', 'v1')
        ->assertHeader('X-Capell-Cache-Tags', sprintf('api,site:%s,language:%s,page:%s', $site->getKey(), $language->getKey(), $page->getKey()))
        ->assertJsonPath('data.url', '/terms');
});

it('returns only requested fields', function (): void {
    [$pageUrl] = createPublicApiPage('/terms', [
        'title' => 'Terms',
        'content' => '<p>Terms content</p>',
    ]);

    getJson(apiResolveUrl([
        'url' => $pageUrl->url,
        'fields' => 'title,unknown',
    ]))
        ->assertOk()
        ->assertExactJson([
            'data' => [
                'title' => 'Terms',
            ],
        ]);
});

it('includes selected layout containers without html', function (): void {
    [$pageUrl, $page, $language] = createPublicApiPage('/terms', [
        'title' => 'Terms',
        'content' => '<p>Terms content</p>',
    ]);

    $mainWidget = Widget::factory()->create(['key' => 'main-widget']);
    $sidebarWidget = Widget::factory()->create(['key' => 'sidebar-widget']);

    TranslationFactory::new()
        ->translatable($mainWidget)
        ->language($language)
        ->create([
            'title' => 'Main Widget',
            'content' => '<p>Main widget</p>',
        ]);

    $layout = Layout::factory()->site($page->site)->create([
        'key' => 'article',
        'widgets' => [$mainWidget->key, $sidebarWidget->key],
        'containers' => [
            'main' => ['widgets' => [['widget_key' => $mainWidget->key, 'occurrence' => 1]]],
            'sidebar' => ['widgets' => [['widget_key' => $sidebarWidget->key, 'occurrence' => 1]]],
        ],
    ]);

    $page->update(['layout_id' => $layout->id]);

    getJson(apiResolveUrl([
        'url' => $pageUrl->url,
        'include' => 'layout',
        'containers' => 'main',
    ]))
        ->assertOk()
        ->assertJsonPath('data.layout.key', 'article')
        ->assertJsonPath('data.layout.containers.0.key', 'main')
        ->assertJsonCount(1, 'data.layout.containers')
        ->assertJsonPath('data.layout.containers.0.widgets.0.key', 'main-widget')
        ->assertJsonMissingPath('data.layout.containers.0.widgets.0.html');
});

it('treats all containers as the full layout graph', function (): void {
    [$pageUrl, $page] = createPublicApiPage('/terms');

    $mainWidget = Widget::factory()->create(['key' => 'main-widget']);
    $sidebarWidget = Widget::factory()->create(['key' => 'sidebar-widget']);

    $layout = Layout::factory()->site($page->site)->create([
        'key' => 'article',
        'widgets' => [$mainWidget->key, $sidebarWidget->key],
        'containers' => [
            'main' => ['widgets' => [['widget_key' => $mainWidget->key, 'occurrence' => 1]]],
            'sidebar' => ['widgets' => [['widget_key' => $sidebarWidget->key, 'occurrence' => 1]]],
        ],
    ]);

    $page->update(['layout_id' => $layout->id]);

    getJson(apiResolveUrl([
        'url' => $pageUrl->url,
        'include' => 'layout',
        'containers' => 'all',
    ]))
        ->assertOk()
        ->assertJsonCount(2, 'data.layout.containers')
        ->assertJsonPath('data.layout.containers.0.key', 'main')
        ->assertJsonPath('data.layout.containers.1.key', 'sidebar');
});

it('includes layout html and sanitizes unsafe html strings', function (): void {
    [$pageUrl, $page, $language] = createPublicApiPage('/terms', [
        'title' => 'Terms',
        'content' => '<p onclick="alert(1)"><a href="javascript:alert(2)">Terms</a></p><script>alert(3)</script>',
        'meta' => [
            'description' => '<span onclick="alert(1)"><a href="javascript:alert(2)">Meta</a></span><script src="/bad.js">',
        ],
    ]);

    $widget = Widget::factory()->create(['key' => 'hero']);

    $layout = Layout::factory()->site($page->site)->create([
        'key' => 'article',
        'meta' => ['summary' => '<strong onclick="alert(1)">Layout</strong><script>bad</script>'],
        'widgets' => [$widget->key],
        'containers' => [
            'main' => [
                'summary' => '<em onmouseover="alert(1)">Container</em><script',
                'widgets' => [['widget_key' => $widget->key, 'occurrence' => 1]],
            ],
        ],
    ]);

    $page->update(['layout_id' => $layout->id]);

    app()->bind(PublicWidgetPayloadResolver::class, fn (): PublicWidgetPayloadResolver => new class implements PublicWidgetPayloadResolver
    {
        /**
         * @return array<string, mixed>
         */
        public function data(Widget $widget, Page $page, Language $language, string $containerKey, int $occurrence): array
        {
            return [
                'content' => '<p onmouseover="alert(1)"><a href="javascript:alert(2)">Widget</a></p><script>alert(3)</script>',
                'nested' => ['content' => '<div onclick=alert(4)><iframe srcdoc="<script>alert(5)</script>"></iframe>Nested</div>'],
            ];
        }

        public function html(Widget $widget, Page $page, Language $language, string $containerKey, int $occurrence): string
        {
            return '<section onclick="alert(6)"><a href="javascript:alert(7)">Hero</a><script>alert(8)</script></section>';
        }
    });

    getJson(apiResolveUrl([
        'url' => $pageUrl->url,
        'fields' => 'url,title,content,meta',
        'include' => 'layout.html',
        'containers' => 'main',
    ]))
        ->assertOk()
        ->assertJsonPath('data.content', '<p><a>Terms</a></p>')
        ->assertJsonPath('data.meta.description', '<span><a>Meta</a></span>')
        ->assertJsonPath('data.layout.meta', [])
        ->assertJsonPath('data.layout.containers.0.meta', [])
        ->assertJsonPath('data.layout.containers.0.widgets.0.data.content', '<p><a>Widget</a></p>')
        ->assertJsonPath('data.layout.containers.0.widgets.0.data.nested.content', '<div>Nested</div>')
        ->assertJsonPath('data.layout.containers.0.widgets.0.html', '<section><a>Hero</a></section>');
});

it('rejects unbounded layout html requests', function (): void {
    [$pageUrl, $page] = createPublicApiPage('/terms');
    $widget = Widget::factory()->create(['key' => 'hero']);

    $layout = Layout::factory()->site($page->site)->create([
        'key' => 'article',
        'widgets' => [$widget->key],
        'containers' => [
            'main' => ['widgets' => [['widget_key' => $widget->key, 'occurrence' => 1]]],
        ],
    ]);

    $page->update(['layout_id' => $layout->id]);

    getJson(apiResolveUrl([
        'url' => $pageUrl->url,
        'include' => 'layout.html',
        'containers' => 'all',
    ]))
        ->assertStatus(422)
        ->assertExactJson(['message' => 'layout.html requires explicit bounded containers.']);
});

it('returns not found for missing pages', function (): void {
    createPublicApiPage('/terms');

    getJson(apiResolveUrl(['url' => '/missing']))
        ->assertNotFound()
        ->assertExactJson(['message' => 'Page not found']);
});

it('resolves the site from the request host and blocks unsigned explicit site selection', function (): void {
    [, , , $site] = createPublicApiPage('/terms');
    [$otherPageUrl, , , $otherSite] = createPublicApiPage('/terms', [
        'title' => 'Other Terms',
    ], 'other.example.com');

    getJson(apiResolveUrl(['url' => '/terms', 'site' => $otherSite->getKey()]))
        ->assertForbidden()
        ->assertExactJson(['message' => 'Forbidden']);

    getJson(apiResolveUrl(['url' => '/terms', 'site' => $otherSite->getKey()], 'other.example.com', signed: true))
        ->assertOk()
        ->assertJsonPath('data.url', $otherPageUrl->url)
        ->assertJsonPath('data.title', 'Other Terms');

    expect($site->getKey())->not->toBe($otherSite->getKey());
});

it('blocks unsigned explicit language selection', function (): void {
    [$pageUrl] = createPublicApiPage('/terms');

    getJson(apiResolveUrl(['url' => $pageUrl->url, 'language' => 'en']))
        ->assertForbidden()
        ->assertExactJson(['message' => 'Forbidden']);

    getJson(apiResolveUrl(['url' => $pageUrl->url, 'language' => 'en'], signed: true))
        ->assertOk()
        ->assertJsonPath('data.url', $pageUrl->url);
});

it('uses the requested public URL path when resolving path-prefixed site domains', function (): void {
    [$pageUrl] = createPublicApiPage('/terms', [
        'title' => 'Tenant Terms',
    ], siteDomainPath: '/tenant');

    getJson(apiResolveUrl(['url' => '/tenant/terms']))
        ->assertOk()
        ->assertJsonPath('data.url', $pageUrl->url)
        ->assertJsonPath('data.title', 'Tenant Terms');
});

it('does not hydrate wildcard site domains when an exact host matches', function (): void {
    [$pageUrl] = createPublicApiPage('/terms', [
        'title' => 'Exact Host Terms',
    ], domain: 'exact.example.com');

    foreach (range(1, 15) as $index) {
        createPublicApiPage('/wildcard-' . $index, [
            'title' => 'Wildcard ' . $index,
        ], domain: null);
    }

    $retrievedSiteDomains = 0;
    SiteDomain::retrieved(function () use (&$retrievedSiteDomains): void {
        $retrievedSiteDomains++;
    });

    getJson(apiResolveUrl(['url' => $pageUrl->url], host: 'exact.example.com'))
        ->assertOk()
        ->assertJsonPath('data.title', 'Exact Host Terms');

    expect($retrievedSiteDomains)->toBeLessThanOrEqual(2);
});

it('does not allow signed context URLs to change the target page URL', function (): void {
    [$pageUrl, , , $site] = createPublicApiPage('/terms');
    createPublicApiPage('/privacy');

    $signedUrl = apiResolveUrl([
        'url' => $pageUrl->url,
        'site' => $site->getKey(),
    ], signed: true);

    getJson(str_replace('url=%2Fterms', 'url=%2Fprivacy', $signedUrl))
        ->assertForbidden()
        ->assertExactJson(['message' => 'Forbidden']);
});

it('does not serve the api when the package is not installed', function (): void {
    createPublicApiPage('/terms');
    CapellCore::forcePackageInstalled(ApiServiceProvider::$packageName, false);

    getJson(apiResolveUrl(['url' => '/terms']))
        ->assertNotFound()
        ->assertExactJson(['message' => 'Page not found']);
});

/**
 * @param  array<string, mixed>  $translation
 * @return array{0: PageUrl, 1: Page, 2: Language, 3: Site}
 */
function createPublicApiPage(string $url, array $translation = [], ?string $domain = 'example.com', ?string $siteDomainPath = null): array
{
    $language = Language::factory()->english()->create();
    $site = Site::factory()->default()->create(['language_id' => $language->id]);
    SiteDomain::factory()
        ->site($site)
        ->language($language)
        ->create([
            'domain' => $domain,
            'path' => $siteDomainPath,
            'scheme' => null,
        ]);
    $page = Page::factory()
        ->site($site)
        ->withTranslations($language, [
            'title' => $translation['title'] ?? 'Terms',
            'content' => $translation['content'] ?? '<p>Terms content</p>',
            'meta' => $translation['meta'] ?? ['description' => 'Terms meta'],
        ])
        ->create();

    $pageUrl = PageUrl::factory()
        ->site($site)
        ->language($language)
        ->page($page)
        ->create(['url' => $url]);

    return [$pageUrl, $page, $language, $site];
}

/**
 * @param  array<string, mixed>  $parameters
 */
function apiResolveUrl(array $parameters = [], string $host = 'example.com', bool $signed = false): string
{
    URL::forceRootUrl('https://' . $host);

    if ($signed) {
        $signedUrl = URL::signedRoute('capell-api.pages.resolve', array_diff_key($parameters, array_flip(['fields', 'include', 'containers'])));
        $ignoredParameters = array_intersect_key($parameters, array_flip(['fields', 'include', 'containers']));

        return $ignoredParameters === []
            ? $signedUrl
            : $signedUrl . '&' . http_build_query($ignoredParameters);
    }

    return route('capell-api.pages.resolve', $parameters);
}
