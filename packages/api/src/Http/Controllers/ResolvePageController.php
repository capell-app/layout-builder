<?php

declare(strict_types=1);

namespace Capell\Api\Http\Controllers;

use Capell\Api\Providers\ApiServiceProvider;
use Capell\Api\Support\SanitizesPublicHtml;
use Capell\Core\Actions\LoadSiteDomainFromUrlAction;
use Capell\Core\Actions\ResolvePublicPageByUrlAction;
use Capell\Core\Data\PublicPageFieldsData;
use Capell\Core\Enums\ExtensionStatusEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\LayoutBuilder\Actions\BuildPublicLayoutGraphAction;
use Capell\Core\LayoutBuilder\Data\PublicLayoutContainerData;
use Capell\Core\LayoutBuilder\Data\PublicLayoutGraphData;
use Capell\Core\LayoutBuilder\Data\PublicLayoutWidgetData;
use Capell\Core\Models\CapellExtension;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Throwable;

final class ResolvePageController
{
    use SanitizesPublicHtml;

    private const DEFAULT_FIELDS = ['url', 'title', 'content'];

    private const ALLOWED_FIELDS = ['url', 'title', 'content', 'meta'];

    private ?SiteDomain $resolvedSiteDomain = null;

    private ?string $resolvedUrlPath = null;

    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->packageIsInstalled()) {
            return $this->notFound();
        }

        $site = $this->resolveSite($request);

        if ($site instanceof JsonResponse) {
            return $site;
        }

        if (! $site instanceof Site) {
            return $this->notFound();
        }

        if ($this->hasExplicitLanguage($request) && ! $this->hasValidContextSignature($request)) {
            return $this->forbidden();
        }

        $language = $this->resolveLanguage($site, $request->query('language'));

        if (! $language instanceof Language) {
            return $this->notFound();
        }

        $resolution = ResolvePublicPageByUrlAction::run(
            site: $site,
            language: $language,
            url: $this->resolvedUrlPath ?? $this->queryString($request, 'url', '/'),
        );

        if (! $resolution->found()) {
            return $this->notFound();
        }

        $data = $this->fields($request, $resolution->fields);

        if ($this->shouldIncludeLayout($request) && $resolution->layout instanceof Layout && $resolution->page instanceof Page) {
            $data['layout'] = $this->layout($request, $resolution->layout, $resolution->page, $language);
        }

        return response()->json(['data' => $data]);
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['message' => 'Page not found'], 404);
    }

    private function forbidden(): JsonResponse
    {
        return response()->json(['message' => 'Forbidden'], 403);
    }

    private function packageIsInstalled(): bool
    {
        if (CapellCore::isPackageInstalled(ApiServiceProvider::$packageName)) {
            return true;
        }

        try {
            return CapellExtension::query()
                ->where('composer_name', ApiServiceProvider::$packageName)
                ->where('status', ExtensionStatusEnum::Enabled)
                ->where('marketplace_runtime_allowed', true)
                ->exists();
        } catch (Throwable) {
            return false;
        }
    }

    private function resolveSite(Request $request): Site|JsonResponse|null
    {
        $site = is_string($request->query('site')) ? trim($request->query('site')) : '';

        if ($site !== '') {
            if (! $this->hasValidContextSignature($request)) {
                return $this->forbidden();
            }

            return Site::query()->whereKey($site)->first();
        }

        $publicPageUrl = $this->publicPageUrl($request);
        $resolved = LoadSiteDomainFromUrlAction::run($publicPageUrl, $this->candidateSitesForUrl($publicPageUrl));
        $siteDomain = is_array($resolved) ? ($resolved[0] ?? null) : null;
        $urlPath = is_array($resolved) ? ($resolved[1] ?? null) : null;

        if (! $siteDomain instanceof SiteDomain) {
            return null;
        }

        $this->resolvedSiteDomain = $siteDomain;
        $this->resolvedUrlPath = is_string($urlPath) && $urlPath !== '' ? $urlPath : null;

        return $siteDomain->site;
    }

    private function hasExplicitLanguage(Request $request): bool
    {
        $language = $request->query('language');

        return is_string($language) && trim($language) !== '';
    }

    private function hasValidContextSignature(Request $request): bool
    {
        return $request->hasValidSignatureWhileIgnoring(['fields', 'include', 'containers']);
    }

    private function resolveLanguage(Site $site, mixed $language): ?Language
    {
        $language = is_scalar($language) ? trim((string) $language) : '';

        if ($language !== '') {
            $byId = Language::query()->whereKey($language)->first();

            if ($byId instanceof Language) {
                return $byId;
            }

            return Language::query()
                ->where('code', $language)
                ->orWhere('locale', $language)
                ->first();
        }

        $domainLanguage = $this->resolvedSiteDomain?->language;

        if ($domainLanguage instanceof Language) {
            return $domainLanguage;
        }

        $siteLanguageId = $site->getAttribute('language_id');

        if (is_int($siteLanguageId)) {
            $siteLanguage = $site->relationLoaded('language')
                ? $site->language
                : Language::query()->whereKey($siteLanguageId)->first();

            if ($siteLanguage instanceof Language) {
                return $siteLanguage;
            }
        }

        return Language::query()->orderBy((new Language)->getKeyName())->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function fields(Request $request, PublicPageFieldsData $fields): array
    {
        $requestedFields = $this->requestedList($request, 'fields');
        $selectedFields = $requestedFields === []
            ? self::DEFAULT_FIELDS
            : array_values(array_intersect($requestedFields, self::ALLOWED_FIELDS));

        $data = [];

        foreach ($selectedFields as $field) {
            $value = match ($field) {
                'url' => $fields->url,
                'title' => $fields->title,
                'content' => $fields->content,
                'meta' => $fields->meta,
                default => null,
            };

            $data[$field] = $this->sanitizeHtmlValue($value);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function layout(Request $request, Layout $layout, Page $page, Language $language): array
    {
        $graph = BuildPublicLayoutGraphAction::run(
            layout: $layout,
            page: $page,
            language: $language,
            containers: $this->requestedContainers($request),
            includeHtml: in_array('layout.html', $this->requestedList($request, 'include'), true),
        );

        return $this->layoutGraph($graph);
    }

    /**
     * @return array<string, mixed>
     */
    private function layoutGraph(PublicLayoutGraphData $graph): array
    {
        return [
            'key' => $graph->key,
            'meta' => $this->sanitizeHtmlValue($graph->meta),
            'containers' => array_map(
                fn (PublicLayoutContainerData $container): array => $this->layoutContainer($container),
                $graph->containers,
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function layoutContainer(PublicLayoutContainerData $container): array
    {
        return [
            'key' => $container->key,
            'meta' => $this->sanitizeHtmlValue($container->meta),
            'widgets' => array_map(
                fn (PublicLayoutWidgetData $widget): array => $this->layoutWidget($widget),
                $container->widgets,
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function layoutWidget(PublicLayoutWidgetData $widget): array
    {
        $data = [
            'key' => $widget->key,
            'occurrence' => $widget->occurrence,
            'type' => $widget->type,
            'data' => $this->sanitizeHtmlValue($widget->data),
        ];

        if ($widget->html !== null) {
            $data['html'] = $this->sanitizeHtmlValue($widget->html);
        }

        return $data;
    }

    private function shouldIncludeLayout(Request $request): bool
    {
        $include = $this->requestedList($request, 'include');

        return in_array('layout', $include, true) || in_array('layout.html', $include, true);
    }

    /**
     * @return array<int, string>
     */
    private function requestedList(Request $request, string $key): array
    {
        $value = $request->query($key);

        if (! is_string($value)) {
            return [];
        }

        return collect(explode(',', $value))
            ->map(fn (string $item): string => trim($item))
            ->filter(fn (string $item): bool => $item !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function requestedContainers(Request $request): array
    {
        $containers = $this->requestedList($request, 'containers');

        if (in_array('all', $containers, true)) {
            return ['*'];
        }

        return $containers;
    }

    private function queryString(Request $request, string $key, string $default): string
    {
        $value = $request->query($key);

        if (! is_string($value) || trim($value) === '') {
            return $default;
        }

        return $value;
    }

    private function publicPageUrl(Request $request): string
    {
        return rtrim($request->getSchemeAndHttpHost(), '/') . '/' . ltrim($this->queryString($request, 'url', '/'), '/');
    }

    /**
     * @return Collection<int, Site>
     */
    private function candidateSitesForUrl(string $url): Collection
    {
        $parts = parse_url($url);
        $host = is_string($parts['host'] ?? null) ? $parts['host'] : null;
        $scheme = is_string($parts['scheme'] ?? null) ? $parts['scheme'] : 'https';
        $exactHostSites = $this->candidateSitesForDomain($host, $scheme, includeWildcardDomains: true);

        if ($exactHostSites->isNotEmpty()) {
            return $exactHostSites;
        }

        return $this->candidateSitesForDomain(null, $scheme, includeWildcardDomains: false);
    }

    /**
     * @return Collection<int, Site>
     */
    private function candidateSitesForDomain(?string $host, string $scheme, bool $includeWildcardDomains): Collection
    {
        return Site::query()
            ->with([
                'language',
                'siteDomains' => fn (BuilderContract $query): BuilderContract => $this->candidateSiteDomainQuery($query, $host, $scheme, includeWildcardDomains: $includeWildcardDomains)
                    ->with('language'),
            ])
            ->whereHas('siteDomains', fn (BuilderContract $query): BuilderContract => $this->candidateSiteDomainQuery($query, $host, $scheme, includeWildcardDomains: false))
            ->get();
    }

    private function candidateSiteDomainQuery(BuilderContract $query, ?string $host, string $scheme, bool $includeWildcardDomains): BuilderContract
    {
        return $query
            ->where(function (BuilderContract $query) use ($host, $includeWildcardDomains): void {
                if ($host === null) {
                    $query->whereNull('domain');

                    return;
                }

                $query->where('domain', $host);

                if ($includeWildcardDomains) {
                    $query->orWhereNull('domain');
                }
            })
            ->where(function (BuilderContract $query) use ($scheme): void {
                $query
                    ->whereNull('scheme')
                    ->orWhere('scheme', false)
                    ->orWhere('scheme', $scheme);
            });
    }
}
