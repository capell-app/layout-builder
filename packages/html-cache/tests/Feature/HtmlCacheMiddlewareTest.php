<?php

declare(strict_types=1);

use Capell\Core\Models\SiteDomain;
use Capell\Frontend\Support\Routing\FrontendRouteMiddlewareRegistry;
use Capell\HtmlCache\Http\Middleware\HtmlCacheMiddleware;
use Capell\HtmlCache\Support\Cache\PageCache;
use Capell\HtmlCache\Tests\HtmlCacheTestCase;
use Capell\Tests\Fixtures\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Cookie;

uses(HtmlCacheTestCase::class);

beforeEach(function (): void {
    config()->set('capell-html-cache.enabled', true);
    config()->set('capell-html-cache.write_enabled', true);
    config()->set('capell-html-cache.cache_ttl', '3600');
});

it('bypasses cached html for requests with a session cookie', function (): void {
    Storage::fake('page_cache');
    config()->set('session.cookie', 'capell_session');

    $siteDomain = SiteDomain::factory()->create([
        'scheme' => 'https',
        'domain' => 'example.test',
        'path' => null,
    ]);
    $request = Request::create('https://example.test/about', Symfony\Component\HttpFoundation\Request::METHOD_GET);
    app()->instance('request', $request);
    resolve(PageCache::class)->cache($request, response('cached html', 200, ['Content-Type' => 'text/html']));

    $request->cookies->set('capell_session', 'session-value');
    $request->setUserResolver(fn (): User => User::factory()->create());

    $response = resolve(HtmlCacheMiddleware::class)->handle(
        $request,
        fn (): Response => response('fresh html', 200, ['Content-Type' => 'text/html']),
    );

    expect($response->getContent())->toBe('fresh html')
        ->and($response->headers->get('X-Frontend-Cache'))->not->toBe('HIT')
        ->and((string) $response->headers->get('Cache-Control'))->toContain('no-store');
});

it('does not write cached html when the response contains authoring markers', function (): void {
    Storage::fake('page_cache');
    $siteDomain = SiteDomain::factory()->create([
        'scheme' => 'https',
        'domain' => 'example.test',
        'path' => null,
    ]);
    $request = Request::create('https://example.test/about', Symfony\Component\HttpFoundation\Request::METHOD_GET);
    app()->instance('request', $request);

    $response = resolve(HtmlCacheMiddleware::class)->handle(
        $request,
        fn (): Response => response('<div data-capell-editor="1"></div>', 200, ['Content-Type' => 'text/html']),
    );

    expect($response->headers->get('X-Frontend-Cache'))->toBe('BYPASS')
        ->and((string) $response->headers->get('Cache-Control'))->toContain('no-store')
        ->and(Storage::disk('page_cache')->allFiles())->toBe([]);
});

it('returns cached 404 html with a 404 status code', function (): void {
    Storage::fake('page_cache');
    $siteDomain = SiteDomain::factory()->create([
        'scheme' => 'https',
        'domain' => 'example.test',
        'path' => null,
    ]);
    $request = Request::create('https://example.test/missing', Symfony\Component\HttpFoundation\Request::METHOD_GET);
    app()->instance('request', $request);
    resolve(PageCache::class)->cache($request, response('missing cached html', 404, ['Content-Type' => 'text/html']));

    $response = resolve(HtmlCacheMiddleware::class)->handle(
        $request,
        fn (): Response => response('fresh missing', 404, ['Content-Type' => 'text/html']),
    );

    expect($response->getStatusCode())->toBe(404)
        ->and($response->getContent())->toBe('missing cached html')
        ->and($response->headers->get('X-Frontend-Cache'))->toBe('HIT');
});

it('strips configured cookies from anonymous cache hits', function (): void {
    Storage::fake('page_cache');
    config()->set('session.cookie', 'capell_session');
    $siteDomain = SiteDomain::factory()->create([
        'scheme' => 'https',
        'domain' => 'example.test',
        'path' => null,
    ]);
    $request = Request::create('https://example.test/about', Symfony\Component\HttpFoundation\Request::METHOD_GET);
    app()->instance('request', $request);
    resolve(PageCache::class)->cache($request, response('cached html', 200, ['Content-Type' => 'text/html']));

    $response = resolve(HtmlCacheMiddleware::class)->handle(
        $request,
        function (): Response {
            $response = response('fresh html', 200, ['Content-Type' => 'text/html']);
            $response->headers->setCookie(new Cookie('capell_session', 'new-session'));

            return $response;
        },
    );

    expect($response->getContent())->toBe('cached html')
        ->and($response->headers->getCookies())->toBe([]);
});

it('wraps web middleware before stripping cacheable response cookies', function (): void {
    $middleware = resolve(FrontendRouteMiddlewareRegistry::class)->all();

    expect(array_search('frontend.no_session_cookies_on_cache', $middleware, true))
        ->toBeLessThan(array_search('web', $middleware, true))
        ->and(array_search('frontend.cache', $middleware, true))
        ->toBeGreaterThan(array_search('web', $middleware, true));
});
