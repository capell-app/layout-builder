<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Http\Controllers;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\Loader\PageLoader;
use Capell\Frontend\Support\Loader\SiteLoader;
use Capell\Frontend\Support\Loader\SiteResolver;
use Capell\SeoSuite\Actions\GeneratePageMarkdownAction;
use Capell\SeoSuite\Actions\PageIsDiscoverableForAiDiscoveryAction;
use Capell\SeoSuite\Actions\PersistAiDiscoverySnapshotAction;
use Capell\SeoSuite\Actions\ResolveAiDiscoveryProfileAction;
use Capell\SeoSuite\Data\AiDiscoveryRenderContextData;
use Capell\SeoSuite\Enums\AiDiscoverySnapshotKindEnum;
use Capell\SeoSuite\Enums\AiDiscoveryStatusEnum;
use Capell\SeoSuite\Models\AiDiscoverySiteProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Cache;
use LogicException;
use Throwable;

class PageMarkdownController extends BaseController
{
    public function __invoke(Request $request, ?string $url = null): Response
    {
        $response = $this->render($request, $url, requireAcceptMarkdownEnabled: false, abortWhenUnavailable: true);

        abort_unless($response instanceof Response, 404);

        return $response;
    }

    public function forAcceptHeader(Request $request): ?Response
    {
        return $this->render($request, null, requireAcceptMarkdownEnabled: true, abortWhenUnavailable: false);
    }

    private function render(
        Request $request,
        ?string $url,
        bool $requireAcceptMarkdownEnabled,
        bool $abortWhenUnavailable,
    ): ?Response {
        [$site, $language, $siteDomain, $canonicalPath] = $this->resolveContext($request, $url);

        if (! $site instanceof Site || ! $language instanceof Language) {
            return $this->unavailable($abortWhenUnavailable);
        }

        $siteProfile = ResolveAiDiscoveryProfileAction::run($site, $language);

        throw_unless($siteProfile instanceof AiDiscoverySiteProfile, LogicException::class, 'Resolving an AI Discovery site profile returned an unexpected page profile.');

        if (! $this->isAvailable($siteProfile, $requireAcceptMarkdownEnabled)) {
            return $this->unavailable($abortWhenUnavailable);
        }

        $page = $this->resolvePage($site, $language, $canonicalPath);

        if (! $page instanceof Page) {
            return $this->unavailable($abortWhenUnavailable);
        }

        if (! $this->isDiscoverablePage($page, $site, $language)) {
            return $this->unavailable($abortWhenUnavailable);
        }

        $context = new AiDiscoveryRenderContextData($site, $language, $siteDomain, $page);
        $cacheKey = sprintf(
            'capell-seo-suite:ai-discovery:%d:%s:%d:page_markdown:%d',
            $site->getKey(),
            $context->domainKey(),
            $language->getKey(),
            $page->getKey(),
        );
        $ttlSeconds = $siteProfile->cache_ttl_seconds;
        $content = Cache::remember($cacheKey, $ttlSeconds, function () use ($cacheKey, $context, $page, $ttlSeconds): string {
            $generatedContent = GeneratePageMarkdownAction::run($context, $page);

            PersistAiDiscoverySnapshotAction::run(
                context: $context,
                kind: AiDiscoverySnapshotKindEnum::PageMarkdown,
                content: $generatedContent,
                cacheKey: $cacheKey,
                ttlSeconds: $ttlSeconds,
                page: $page,
            );

            return $generatedContent;
        });

        if ($content === '') {
            return $this->unavailable($abortWhenUnavailable);
        }

        return response($content, 200, [
            'Content-Type' => 'text/markdown; charset=utf-8',
            'Cache-Control' => sprintf('public, max-age=%d', $ttlSeconds),
            'ETag' => $this->etag($content),
        ]);
    }

    private function isAvailable(AiDiscoverySiteProfile $siteProfile, bool $requireAcceptMarkdownEnabled): bool
    {
        if (! $siteProfile->markdown_pages_enabled || $siteProfile->status === AiDiscoveryStatusEnum::Disabled) {
            return false;
        }

        return ! $requireAcceptMarkdownEnabled || $siteProfile->accept_markdown_enabled;
    }

    private function unavailable(bool $abortWhenUnavailable): ?Response
    {
        abort_if($abortWhenUnavailable, 404);

        return null;
    }

    /**
     * @return array{0: ?Site, 1: ?Language, 2: ?SiteDomain, 3: string}
     */
    private function resolveContext(Request $request, ?string $url): array
    {
        $site = Frontend::site();
        $language = Frontend::language();
        $reader = Frontend::contextReader();
        $siteDomain = method_exists($reader, 'domain') ? $reader->domain() : null;
        $siteDomain = $siteDomain instanceof SiteDomain ? $siteDomain : null;

        if ($site instanceof Site && $language instanceof Language) {
            return [$site, $language, $siteDomain, $this->canonicalPath($url)];
        }

        try {
            [$resolvedSite, $resolvedLanguage, $resolvedDomain, $normalizedPath] = SiteResolver::resolve(
                $this->canonicalRequestUrl($request, $url),
                SiteLoader::getSites(),
            );

            return [
                $resolvedSite,
                $resolvedLanguage,
                $resolvedDomain instanceof SiteDomain ? $resolvedDomain : null,
                $normalizedPath,
            ];
        } catch (Throwable) {
            return [null, null, null, '/'];
        }
    }

    private function resolvePage(Site $site, Language $language, string $canonicalPath): ?Page
    {
        $currentPage = Frontend::page();

        if ($currentPage instanceof Page) {
            return $currentPage;
        }

        $pageUrl = PageLoader::getPageUrl(
            site: $site,
            language: $language,
            url: $canonicalPath,
        );

        if (! $pageUrl instanceof PageUrl) {
            return null;
        }

        $page = PageLoader::loadPage(
            type: $pageUrl->pageable_type,
            id: $pageUrl->pageable_id,
            site: $site,
            language: $language,
        );

        return $page instanceof Page ? $page : null;
    }

    private function isDiscoverablePage(Page $page, Site $site, Language $language): bool
    {
        return PageIsDiscoverableForAiDiscoveryAction::run($page, $site, $language);
    }

    private function canonicalRequestUrl(Request $request, ?string $url): string
    {
        $query = $request->getQueryString();
        $canonicalUrl = rtrim($request->getSchemeAndHttpHost(), '/') . $this->canonicalPath($url);

        return $query !== null && $query !== ''
            ? $canonicalUrl . '?' . $query
            : $canonicalUrl;
    }

    private function canonicalPath(?string $url): string
    {
        $path = trim((string) $url, '/');

        if ($path === '' || $path === 'index') {
            return '/';
        }

        $canonicalPath = preg_replace('/\.md$/', '', $path);
        $canonicalPath ??= $path;

        if (str_ends_with($canonicalPath, '/index')) {
            $canonicalPath = mb_substr($canonicalPath, 0, -6);
        }

        return '/' . trim($canonicalPath, '/');
    }

    private function etag(string $content): string
    {
        return '"' . hash('sha256', $content) . '"';
    }
}
