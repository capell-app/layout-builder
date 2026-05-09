<?php

declare(strict_types=1);

namespace Capell\HtmlCache\Http\Middleware;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Frontend\Contracts\CacheBypassResolver;
use Capell\Frontend\Support\Cache\SurrogateKeyNormalizer;
use Capell\Frontend\Support\Context\FrontendContext;
use Capell\Frontend\Support\Security\PublicHtmlSafetyInspector;
use Capell\HtmlCache\Support\Cache\PageCache;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class HtmlCacheMiddleware
{
    private const INCOMING_SESSION_COOKIE_ATTRIBUTE = 'capell.html_cache.incoming_session_cookie';

    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set(self::INCOMING_SESSION_COOKIE_ATTRIBUTE, $this->hasSessionCookie($request));

        if (resolve(CacheBypassResolver::class)->shouldBypass()) {
            return $next($request);
        }

        if (config('capell-html-cache.enabled', true) !== true) {
            return $this->applyCacheHeaders($request, $next($request));
        }

        if ($this->shouldBypassCacheRead($request)) {
            $response = $next($request);

            if ($this->shouldBypassHttpCache($request, $response)) {
                return $this->privateNoStore($response);
            }

            return $this->applyCacheHeaders($request, $response);
        }

        $pageCache = resolve(PageCache::class);
        $cachedPage = $pageCache->getCachePage($request);

        if ($cachedPage !== false) {
            return $this->cacheHitResponse($cachedPage, 200);
        }

        $cachedErrorPage = $pageCache->getCacheErrorPage($request);

        if ($cachedErrorPage !== false) {
            return $this->cacheHitResponse($cachedErrorPage, 404);
        }

        $response = $this->stripCookiesForCacheableAnonymousRequest($request, $next($request));

        if ($this->containsAuthoringSurface($response)) {
            $response->headers->set('X-Frontend-Cache', 'BYPASS');

            return $this->privateNoStore($response);
        }

        $cached = $this->cacheResponse($pageCache, $request, $response);

        if ($cached) {
            $this->stripConfiguredCookies($response);
        }

        $response->headers->set('X-Frontend-Cache', 'MISS');

        return $this->applyCacheHeaders($request, $response, forcePublic: $cached);
    }

    private function containsAuthoringSurface(Response $response): bool
    {
        if (mb_strpos((string) $response->headers->get('Content-Type'), 'text/html') === false) {
            return false;
        }

        return resolve(PublicHtmlSafetyInspector::class)->containsAuthoringSurface((string) $response->getContent());
    }

    private function privateNoStore(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    private function shouldBypassHttpCache(Request $request, Response $response): bool
    {
        if ($request->query->count() > 0 || $this->isInertiaRequest($request) || $response->isServerError()) {
            return true;
        }

        return str_contains((string) $response->headers->get('Cache-Control'), 'no-store');
    }

    private function shouldBypassCacheRead(Request $request): bool
    {
        if (! $request->isMethod('GET')) {
            return true;
        }

        if ($request->query->has('without_html_cache') || $request->query->count() > 0) {
            return true;
        }

        if ($request->headers->has('X-Livewire') || $this->isInertiaRequest($request)) {
            return true;
        }

        if ($request->query->has('signature') || $request->headers->has('Authorization')) {
            return true;
        }

        return config('capell-html-cache.cache_skip_authenticated', true) === true
            && ($request->user() !== null || $this->hasIncomingSessionCookie($request));
    }

    private function isInertiaRequest(Request $request): bool
    {
        if ($request->headers->has('X-Inertia')) {
            return true;
        }

        if ($request->headers->has('X-Inertia-Version')) {
            return true;
        }

        if ($request->headers->has('X-Inertia-Partial-Component')) {
            return true;
        }

        if ($request->headers->has('X-Inertia-Partial-Data')) {
            return true;
        }

        return $request->headers->has('X-Inertia-Reset');
    }

    private function cacheResponse(PageCache $pageCache, Request $request, Response $response): bool
    {
        if (config('capell-html-cache.write_enabled', true) !== true) {
            return false;
        }

        if (! $pageCache->shouldCachePage($request, $response)) {
            return false;
        }

        if ($response->getStatusCode() !== Response::HTTP_NOT_FOUND && ! FrontendContext::shouldCachePage()) {
            return false;
        }

        $pageCache->cache($request, $response);

        return true;
    }

    private function cacheHitResponse(string $content, int $statusCode): Response
    {
        $response = $this->stripConfiguredCookies(response($content, $statusCode));
        $response->headers->set('Content-Type', 'text/html');
        $response->headers->set('X-Frontend-Cache', 'HIT');

        return $this->applyCacheHeaders(request(), $response, applySurrogateKey: false, forcePublic: true);
    }

    private function applyCacheHeaders(
        Request $request,
        Response $response,
        bool $applySurrogateKey = true,
        bool $forcePublic = false,
    ): Response {
        if (! $forcePublic && $this->shouldBypassHttpCache($request, $response)) {
            return $this->privateNoStore($response);
        }

        if (! $forcePublic && (
            ! $request->isMethod('GET')
            || $this->hasIncomingSessionCookie($request)
            || $request->headers->has('Authorization')
        )) {
            $response->headers->set('Cache-Control', 'private, no-store');

            return $response;
        }

        if (! $forcePublic && str_contains((string) $response->headers->get('Cache-Control'), 'public')) {
            return $response;
        }

        if (! $forcePublic) {
            return $this->privateNoStore($response);
        }

        $configuredCacheTtl = config('capell-html-cache.cache_ttl');
        $cacheTtl = is_numeric($configuredCacheTtl) ? max(0, (int) $configuredCacheTtl) : 3600;
        $response->headers->set('Cache-Control', sprintf(
            'public, s-maxage=%d, max-age=%d, stale-while-revalidate=%d',
            intdiv($cacheTtl, 6),
            60,
            86400,
        ));
        $response->headers->set('Vary', implode(', ', config('capell-html-cache.cache_vary_headers', ['Accept-Encoding'])));

        if ($applySurrogateKey) {
            $this->applySurrogateKey($response);
        }

        return $response;
    }

    private function applySurrogateKey(Response $response): void
    {
        $keys = [];

        try {
            $context = FrontendContext::current();

            if ($context->page() instanceof Pageable) {
                $keys[] = 'page-' . $context->page()->getKey();
            }

            if ($context->site() instanceof Site) {
                $keys[] = 'site-' . $context->site()->getKey();
            }

            if ($context->language() instanceof Language) {
                $keys[] = 'lang-' . $context->language()->code;
            }
        } catch (Exception) {
            // Frontend context is optional for non-page responses.
        }

        $keys = SurrogateKeyNormalizer::normalize($keys);

        if ($keys !== []) {
            $response->headers->set('Surrogate-Key', implode(' ', $keys));
        }
    }

    private function hasSessionCookie(Request $request): bool
    {
        $sessionCookieName = config('session.cookie');

        return is_string($sessionCookieName)
            && $sessionCookieName !== ''
            && $request->cookies->has($sessionCookieName);
    }

    private function hasIncomingSessionCookie(Request $request): bool
    {
        return $request->attributes->get(self::INCOMING_SESSION_COOKIE_ATTRIBUTE, false) === true;
    }

    private function stripCookiesForCacheableAnonymousRequest(Request $request, Response $response): Response
    {
        if (! $request->isMethod('GET') || $this->hasIncomingSessionCookie($request) || $request->headers->has('Authorization')) {
            return $response;
        }

        if (! in_array($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NOT_FOUND], true)) {
            return $response;
        }

        return $this->stripConfiguredCookies($response);
    }

    private function stripConfiguredCookies(Response $response): Response
    {
        $cookiesToRemove = [
            config('session.cookie'),
            'XSRF-TOKEN',
            'PHPDEBUGBAR_STACK_DATA',
        ];

        foreach ($response->headers->getCookies() as $cookie) {
            if (in_array($cookie->getName(), $cookiesToRemove, true)) {
                $response->headers->removeCookie($cookie->getName(), $cookie->getPath(), $cookie->getDomain());
            }
        }

        return $response;
    }
}
