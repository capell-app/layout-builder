<?php

declare(strict_types=1);

namespace Capell\Api\Http\Controllers;

use Capell\Api\Providers\ApiServiceProvider;
use Capell\Api\Support\SanitizesPublicHtml;
use Capell\Core\Actions\LoadSiteDomainFromUrlAction;
use Capell\Core\Actions\ResolvePublicPageByUrlAction;
use Capell\Core\Facades\CapellCore;
use Capell\Core\LayoutBuilder\Actions\BuildPublicLayoutGraphAction;
use Capell\Core\LayoutBuilder\Data\PublicLayoutContainerData;
use Capell\Core\LayoutBuilder\Data\PublicLayoutGraphData;
use Capell\Core\LayoutBuilder\Data\PublicLayoutWidgetData;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Core\Support\Extensions\InstalledExtensionRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ResolvePageController
{
    use SanitizesPublicHtml;

    private const DEFAULT_FIELDS = ['url', 'title', 'content'];

    private const ALLOWED_FIELDS = ['url', 'title', 'content', 'meta'];

    private ?SiteDomain $resolvedSiteDomain = null;

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
            url: $this->queryString($request, 'url', '/'),
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

        return resolve(InstalledExtensionRepository::class)->has(ApiServiceProvider::$packageName);
    }

    private function resolveSite(Request $request): Site|JsonResponse|null
    {
        $site = is_scalar($request->query('site')) ? trim((string) $request->query('site')) : '';

        if ($site !== '') {
            if (! $this->hasValidContextSignature($request)) {
                return $this->forbidden();
            }

            return Site::query()->whereKey($site)->first();
        }

        $resolved = LoadSiteDomainFromUrlAction::run($request->fullUrl(), Site::query()->with('siteDomains.language', 'language')->get());
        $siteDomain = is_array($resolved) ? ($resolved[0] ?? null) : null;

        if (! $siteDomain instanceof SiteDomain) {
            return null;
        }

        $this->resolvedSiteDomain = $siteDomain;

        return $siteDomain->site;
    }

    private function hasExplicitLanguage(Request $request): bool
    {
        $language = $request->query('language');

        return is_scalar($language) && trim((string) $language) !== '';
    }

    private function hasValidContextSignature(Request $request): bool
    {
        return $request->hasValidSignatureWhileIgnoring(['url', 'fields', 'include', 'containers']);
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

        if ($site->language_id !== null) {
            $siteLanguage = $site->relationLoaded('language')
                ? $site->language
                : Language::query()->whereKey($site->language_id)->first();

            if ($siteLanguage instanceof Language) {
                return $siteLanguage;
            }
        }

        return Language::query()->orderBy((new Language)->getKeyName())->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function fields(Request $request, object $fields): array
    {
        $requestedFields = $this->requestedList($request, 'fields');
        $selectedFields = $requestedFields === []
            ? self::DEFAULT_FIELDS
            : array_values(array_intersect($requestedFields, self::ALLOWED_FIELDS));

        $data = [];

        foreach ($selectedFields as $field) {
            $value = $fields->{$field};
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

        if (! is_scalar($value)) {
            return [];
        }

        return collect(explode(',', (string) $value))
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

        if (! is_scalar($value) || trim((string) $value) === '') {
            return $default;
        }

        return (string) $value;
    }
}
