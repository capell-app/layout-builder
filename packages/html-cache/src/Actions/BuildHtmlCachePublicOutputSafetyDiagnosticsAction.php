<?php

declare(strict_types=1);

namespace Capell\HtmlCache\Actions;

use Capell\Admin\Data\Diagnostics\DiagnosticCheckData;
use Capell\Admin\Support\SiteScope;
use Capell\Core\Models\SiteDomain;
use Capell\Frontend\Support\Security\PublicHtmlSafetyInspector;
use Capell\HtmlCache\Models\CachedModelUrl;
use Capell\HtmlCache\Support\Cache\HtmlCachePathResolver;
use Capell\HtmlCache\Support\Cache\HtmlCacheStore;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Concerns\AsObject;
use Throwable;

/**
 * @method static list<DiagnosticCheckData> run(?int $siteId = null)
 */
final class BuildHtmlCachePublicOutputSafetyDiagnosticsAction
{
    use AsObject;

    /**
     * @return list<DiagnosticCheckData>
     */
    public function handle(?int $siteId = null): array
    {
        try {
            $inspection = $this->inspectCachedHtmlFiles($siteId);
        } catch (Throwable $throwable) {
            return [
                new DiagnosticCheckData(
                    status: 'amber',
                    label: (string) __('capell-html-cache::admin.cached_public_html_safety'),
                    detail: (string) __('capell-html-cache::admin.cached_html_inspection_failed'),
                    remediation: $throwable->getMessage(),
                ),
            ];
        }

        $checks = [];

        if ($inspection['truncated']) {
            $checks[] = new DiagnosticCheckData(
                status: 'amber',
                label: (string) __('capell-html-cache::admin.cached_public_html_safety'),
                detail: (string) __('capell-html-cache::admin.cached_html_inspection_limited', [
                    'inspected' => $inspection['inspected'],
                    'total' => $inspection['total'],
                ]),
            );
        }

        if ($inspection['unindexedFiles'] > 0) {
            $checks[] = new DiagnosticCheckData(
                status: 'amber',
                label: (string) __('capell-html-cache::admin.cached_public_html_safety'),
                detail: (string) __('capell-html-cache::admin.cached_html_unindexed_files', [
                    'count' => $inspection['unindexedFiles'],
                ]),
            );
        }

        if ($inspection['unsafeFiles'] === []) {
            $checks[] =
                new DiagnosticCheckData(
                    status: 'green',
                    label: (string) __('capell-html-cache::admin.cached_public_html_safety'),
                    detail: (string) __('capell-html-cache::admin.no_authoring_markers_detected', [
                        'count' => $inspection['inspected'],
                    ]),
                );

            return $checks;
        }

        return [
            ...$checks,
            ...collect($inspection['unsafeFiles'])
                ->map(fn (array $file): DiagnosticCheckData => new DiagnosticCheckData(
                    status: 'red',
                    label: (string) __('capell-html-cache::admin.cached_public_html_safety'),
                    detail: (string) __('capell-html-cache::admin.unsafe_cached_html_detail', [
                        'file' => $file['file'],
                        'matched' => $file['matched'],
                    ]),
                    remediation: $file['reason'],
                    path: $file['path'],
                ))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array{unsafeFiles: list<array{file: string, path: ?string, matched: string, reason: string}>, inspected: int, total: int, truncated: bool, unindexedFiles: int}
     */
    private function inspectCachedHtmlFiles(?int $siteId): array
    {
        $store = resolve(HtmlCacheStore::class);
        $inspector = resolve(PublicHtmlSafetyInspector::class);
        $pathResolver = resolve(HtmlCachePathResolver::class);
        $configuredLimit = config('capell-html-cache.site_health_public_html_scan_limit');
        $limit = is_numeric($configuredLimit) ? max(1, (int) $configuredLimit) : 100;
        $query = $this->scopedCachedUrlsQuery($siteId);
        $total = (clone $query)
            ->select('url_hash')
            ->distinct()
            ->count('url_hash');
        $rows = (clone $query)
            ->with('siteDomain')
            ->latest('last_seen_at')
            ->limit($limit * 3)
            ->get()
            ->unique('url_hash')
            ->take($limit)
            ->values();

        $unsafeFiles = [];
        $inspected = 0;
        $indexedFiles = [];

        foreach ($rows as $cachedModelUrl) {
            if (! $cachedModelUrl instanceof CachedModelUrl) {
                continue;
            }

            if (! $cachedModelUrl->siteDomain instanceof SiteDomain) {
                continue;
            }

            $files = [
                $pathResolver->pathForUrl($cachedModelUrl->path, $cachedModelUrl->siteDomain),
                $pathResolver->pathForUrl($cachedModelUrl->path, $cachedModelUrl->siteDomain, error: true),
            ];

            foreach ($files as $file) {
                $indexedFiles[$file] = true;
                $path = $store->path($file);
                if (! is_string($path)) {
                    continue;
                }

                if (! is_file($path)) {
                    continue;
                }

                $inspected++;
                $detection = $inspector->detectAuthoringSurface((string) file_get_contents($path));

                if ($detection === null) {
                    continue;
                }

                $unsafeFiles[] = [
                    'file' => $file,
                    'path' => $file,
                    'matched' => $detection->matched,
                    'reason' => $detection->reason,
                ];
            }
        }

        $unindexedInspection = $this->inspectUnindexedCachedHtmlFiles($siteId, $indexedFiles);

        return [
            'unsafeFiles' => [
                ...$unsafeFiles,
                ...$unindexedInspection['unsafeFiles'],
            ],
            'inspected' => $inspected + $unindexedInspection['inspected'],
            'total' => $total,
            'truncated' => $total > $limit,
            'unindexedFiles' => $unindexedInspection['unindexedFiles'],
        ];
    }

    /**
     * @param  array<string, bool>  $indexedFiles
     * @return array{unsafeFiles: list<array{file: string, path: ?string, matched: string, reason: string}>, inspected: int, unindexedFiles: int}
     */
    private function inspectUnindexedCachedHtmlFiles(?int $siteId, array $indexedFiles): array
    {
        $store = resolve(HtmlCacheStore::class);
        $inspector = resolve(PublicHtmlSafetyInspector::class);
        $configuredLimit = config('capell-html-cache.site_health_unindexed_public_html_scan_limit');
        $limit = is_numeric($configuredLimit) ? max(1, (int) $configuredLimit) : 25;
        $unsafeFiles = [];
        $inspected = 0;
        $unindexedFiles = 0;

        foreach ($this->unindexedCandidateFiles($siteId, $indexedFiles, $limit) as $file) {
            $unindexedFiles++;
            $path = $store->path($file);
            if (! is_string($path)) {
                continue;
            }

            if (! is_file($path)) {
                continue;
            }

            $inspected++;
            $detection = $inspector->detectAuthoringSurface((string) file_get_contents($path));

            if ($detection === null) {
                continue;
            }

            $unsafeFiles[] = [
                'file' => $file,
                'path' => $file,
                'matched' => $detection->matched,
                'reason' => $detection->reason,
            ];
        }

        return [
            'unsafeFiles' => $unsafeFiles,
            'inspected' => $inspected,
            'unindexedFiles' => $unindexedFiles,
        ];
    }

    /**
     * @param  array<string, bool>  $indexedFiles
     * @return list<string>
     */
    private function unindexedCandidateFiles(?int $siteId, array $indexedFiles, int $limit): array
    {
        $store = resolve(HtmlCacheStore::class);
        $pathResolver = resolve(HtmlCachePathResolver::class);
        $files = [];

        foreach ($this->scopedSiteDomainsQuery($siteId)->get() as $siteDomain) {
            if (! $siteDomain instanceof SiteDomain) {
                continue;
            }

            if ($siteDomain->scheme === null) {
                continue;
            }

            if ($siteDomain->domain === null) {
                continue;
            }

            $files = $this->appendUnindexedCandidatesForSiteDomain(
                files: $files,
                store: $store,
                pathResolver: $pathResolver,
                siteDomain: $siteDomain,
                limit: $limit,
            );

            if (count($files) >= $limit) {
                break;
            }
        }

        if ($siteId === null && count($files) < $limit) {
            try {
                $files = [
                    ...$files,
                    ...array_slice($store->files(), 0, $limit - count($files)),
                ];
            } catch (Throwable) {
                // Keep diagnostics best-effort when legacy root-level cache files are unreadable.
            }
        }

        return collect($files)
            ->filter(fn (string $file): bool => str_ends_with($file, '.html') && ! isset($indexedFiles[$file]))
            ->unique()
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $files
     * @return list<string>
     */
    private function appendUnindexedCandidatesForSiteDomain(
        array $files,
        HtmlCacheStore $store,
        HtmlCachePathResolver $pathResolver,
        SiteDomain $siteDomain,
        int $limit,
    ): array {
        $siteRootFile = $pathResolver->pathForUrl('/', $siteDomain);

        if ($store->exists($siteRootFile) && ! in_array($siteRootFile, $files, true)) {
            $files[] = $siteRootFile;
        }

        if (count($files) >= $limit) {
            return $files;
        }

        $siteRootDirectory = $this->siteDomainCacheRootDirectory($siteDomain);

        try {
            $domainFiles = $store->allFiles($siteRootDirectory);
        } catch (Throwable) {
            $domainFiles = [];
        }

        return [
            ...$files,
            ...array_slice($domainFiles, 0, $limit - count($files)),
        ];
    }

    private function siteDomainCacheRootDirectory(SiteDomain $siteDomain): string
    {
        $path = sprintf('%s.%s', $siteDomain->scheme, $siteDomain->domain);

        if (! in_array($siteDomain->path, [null, '', '/'], true)) {
            $path .= $siteDomain->path;
        }

        return rtrim($path, '/');
    }

    /**
     * @return Builder<CachedModelUrl>
     */
    private function scopedCachedUrlsQuery(?int $siteId): Builder
    {
        /** @var Builder<CachedModelUrl> $query */
        $query = CachedModelUrl::query()
            ->whereNotNull('site_domain_id');

        $query = SiteScope::applyForCurrentActor($query, denyWhenMissingActor: true);

        if ($siteId !== null) {
            $query->where('site_id', $siteId);
        }

        return $query;
    }

    /**
     * @return Builder<SiteDomain>
     */
    private function scopedSiteDomainsQuery(?int $siteId): Builder
    {
        /** @var Builder<SiteDomain> $query */
        $query = SiteDomain::query()
            ->whereNotNull('domain')
            ->whereNotNull('scheme');

        $query = SiteScope::applyForCurrentActor($query, denyWhenMissingActor: true);

        if ($siteId !== null) {
            $query->where('site_id', $siteId);
        }

        return $query;
    }
}
