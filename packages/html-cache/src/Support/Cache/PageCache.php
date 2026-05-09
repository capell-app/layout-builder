<?php

declare(strict_types=1);

namespace Capell\HtmlCache\Support\Cache;

use Capell\Frontend\Contracts\CacheBypassResolver;
use Capell\Frontend\Contracts\HtmlMinifier;
use Capell\Frontend\Support\Security\PublicHtmlSafetyInspector;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Silber\PageCache\Cache;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final class PageCache extends Cache
{
    public const ERROR_EXTENSION = '.404.html';

    public const ERROR_PAGE = '404-error.html';

    public function cache(SymfonyRequest $request, SymfonyResponse $response): void
    {
        /** @var Request $laravelRequest */
        $laravelRequest = $request;

        /** @var Response $laravelResponse */
        $laravelResponse = $response;

        $cacheLocation = $this->getDirectoryAndFileNames($laravelRequest, $laravelResponse);

        if ($cacheLocation === null) {
            return;
        }

        [$path, $filename, $extension] = $cacheLocation;
        $content = (string) $response->getContent();

        if ($extension === 'html' && $this->containsAuthoringSurface($content)) {
            return;
        }

        $this->files->makeDirectory($path, 0775, true, true);

        if ($response->getStatusCode() === SymfonyResponse::HTTP_NOT_FOUND) {
            $this->files->put(
                $this->join([$path, $filename . self::ERROR_EXTENSION]),
                $laravelResponse->getContent(),
                true,
            );

            return;
        }

        if ($extension === 'html' && config('capell-html-cache.minify_html', true) === true) {
            $content = resolve(HtmlMinifier::class)->minify($content);
        }

        $this->files->put(
            $this->join([$path, $filename . '.' . $extension]),
            $content,
            true,
        );
    }

    public function getCachePage(Request $request): bool|string
    {
        $path = $this->getFileFromRequest($request);

        return File::exists($path) ? File::get($path) : false;
    }

    public function getCacheErrorPage(Request $request): bool|string
    {
        $path = $this->getFileFromRequest($request, self::ERROR_EXTENSION);

        return File::exists($path) ? File::get($path) : false;
    }

    public function shouldCachePage(Request $request, SymfonyResponse $response): bool
    {
        if (resolve(CacheBypassResolver::class)->shouldBypass()) {
            return false;
        }

        if (config('capell-html-cache.enabled', true) !== true) {
            return false;
        }

        if ($request->has('without_html_cache')) {
            return false;
        }

        if ($request->query->count() > 0) {
            return false;
        }

        if ($this->isInertiaRequest($request)) {
            return false;
        }

        if (! $request->isMethod('GET')) {
            return false;
        }

        if ($this->sessionHasUserState($request)) {
            return false;
        }

        if (! in_array($response->getStatusCode(), [200, 404], true)) {
            return false;
        }

        if (mb_strpos((string) $response->headers->get('Content-Type'), 'text/html') === false) {
            return false;
        }

        if ($this->containsAuthoringSurface((string) $response->getContent())) {
            return false;
        }

        return ! $request->headers->has('x-livewire');
    }

    protected function aliasFilename($filename): string
    {
        if (in_array($filename, [null, '', 'index'], true)) {
            return 'pc__index__pc';
        }

        return $filename;
    }

    protected function getDirectoryAndFileNames($request, $response): ?array
    {
        /** @var Request $laravelRequest */
        $laravelRequest = $request;
        /** @var Response $laravelResponse */
        $laravelResponse = $response;

        $segments = $this->safeRequestSegments($laravelRequest);

        if ($segments === null) {
            return null;
        }

        $filename = $this->aliasFilename(array_pop($segments));
        $extension = $this->guessFileExtension($laravelResponse);

        return [$this->getCachePath(implode('/', $segments)), $filename, $extension];
    }

    private function getFileFromRequest(Request $request, string $extension = '.html'): string
    {
        $segments = $this->safeRequestSegments($request);

        if ($segments === null) {
            return $this->getCachePath('__invalid__') . DIRECTORY_SEPARATOR . 'pc__invalid__pc' . $extension;
        }

        $filename = $this->aliasFilename(array_pop($segments));

        return $this->getCachePath(implode(DIRECTORY_SEPARATOR, $segments)) . DIRECTORY_SEPARATOR . $filename . $extension;
    }

    /** @return array<int, string>|null */
    private function safeRequestSegments(Request $request): ?array
    {
        $segments = $request->segments();

        foreach ($segments as $segment) {
            if ($segment === '..' || str_contains((string) $segment, "\0") || str_contains((string) $segment, '\\')) {
                return null;
            }
        }

        return $segments;
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

    private function sessionHasUserState(Request $request): bool
    {
        if (! $request->hasSession()) {
            return false;
        }

        $session = $request->session();
        if (filled($session->get('_flash.old', []))) {
            return true;
        }

        if (filled($session->get('_flash.new', []))) {
            return true;
        }

        if ($session->has('errors')) {
            return true;
        }

        if ($session->has('_old_input')) {
            return true;
        }

        if ($session->has('status')) {
            return true;
        }

        if ($session->has('enquiry_status')) {
            return true;
        }

        return $session->has('roadmap-status');
    }

    private function containsAuthoringSurface(string $content): bool
    {
        return resolve(PublicHtmlSafetyInspector::class)->containsAuthoringSurface($content);
    }
}
