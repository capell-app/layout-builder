<?php

declare(strict_types=1);

namespace Capell\MigrationAssistant\Services\Import;

use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\MigrationAssistant\Services\Import\Resolvers\MatchResolution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Imports a site package by first materialising Site / SiteDomain shared
 * relations, then delegating page writes to the page importer.
 */
final readonly class SiteImportService
{
    public function __construct(
        private PageImportService $pageImporter = new PageImportService,
    ) {}

    public function import(
        PackageReadResult $package,
        ResolutionMap $resolutionMap,
        ?int $targetWorkspaceId = null,
    ): ImportExecutionReport {
        return DB::transaction(function () use ($package, $resolutionMap, $targetWorkspaceId): ImportExecutionReport {
            $createdSiteIds = [];
            $createdSiteDomainIds = [];
            $map = $this->materialiseSiteRelations($package, $resolutionMap, $createdSiteIds, $createdSiteDomainIds);
            $report = $this->pageImporter->import($package, $map, $targetWorkspaceId);

            return new ImportExecutionReport(
                pagesCreated: $report->pagesCreated,
                pagesSkipped: $report->pagesSkipped,
                createdPageIds: $report->createdPageIds,
                errors: $report->errors,
                pageUrlsCreated: $report->pageUrlsCreated,
                mediaReassigned: $report->mediaReassigned,
                createdSiteIds: $createdSiteIds,
                createdSiteDomainIds: $createdSiteDomainIds,
            );
        });
    }

    /**
     * @param  array<int, int|string>  $createdSiteIds
     * @param  array<int, int|string>  $createdSiteDomainIds
     */
    private function materialiseSiteRelations(
        PackageReadResult $package,
        ResolutionMap $resolutionMap,
        array &$createdSiteIds,
        array &$createdSiteDomainIds,
    ): ResolutionMap {
        $resolved = $resolutionMap->resolved;
        $importedSiteIdsBySourceId = [];
        $resolvedSiteIdsByRef = $this->resolvedSiteIdsByRef($resolved);

        foreach ($package->payload as $entryPath => $contents) {
            if (! str_starts_with($entryPath, 'relations/sites/')) {
                continue;
            }

            $descriptor = $this->decode($contents);
            $ref = is_string($descriptor['ref'] ?? null) ? $descriptor['ref'] : null;

            if ($ref === null || isset($resolved[$ref])) {
                $sourceId = $descriptor['id'] ?? null;

                if ($ref !== null && isset($resolvedSiteIdsByRef[$ref]) && (is_int($sourceId) || is_string($sourceId))) {
                    $importedSiteIdsBySourceId[(string) $sourceId] = $resolvedSiteIdsByRef[$ref];
                }

                continue;
            }

            $site = new Site;
            $site->forceFill($this->siteAttributes($descriptor));
            $site->save();

            $createdSiteIds[] = $site->getKey();
            $resolved[$ref] = new MatchResolution(localId: (int) $site->getKey(), strategy: 'imported');
            $resolvedSiteIdsByRef[$ref] = (int) $site->getKey();

            $sourceId = $descriptor['id'] ?? null;
            if (is_int($sourceId) || is_string($sourceId)) {
                $importedSiteIdsBySourceId[(string) $sourceId] = (int) $site->getKey();
            }
        }

        foreach ($package->payload as $entryPath => $contents) {
            if (! str_starts_with($entryPath, 'relations/site-domains/')) {
                continue;
            }

            $descriptor = $this->decode($contents);
            $attributes = $this->siteDomainAttributes($descriptor, $importedSiteIdsBySourceId, $resolvedSiteIdsByRef);

            if (! isset($attributes['site_id'])) {
                continue;
            }

            $this->assertNoSiteDomainConflict($attributes);

            $siteDomain = new SiteDomain;
            $siteDomain->forceFill($attributes);
            $siteDomain->save();
            $createdSiteDomainIds[] = $siteDomain->getKey();
        }

        $unresolved = array_values(array_filter(
            $resolutionMap->unresolved,
            static fn (string $ref): bool => ! isset($resolved[$ref]),
        ));

        return new ResolutionMap(resolved: $resolved, unresolved: $unresolved);
    }

    /**
     * @param  array<string, mixed>  $descriptor
     * @return array<string, mixed>
     */
    private function siteAttributes(array $descriptor): array
    {
        $attributes = is_array($descriptor['attributes'] ?? null) ? $descriptor['attributes'] : [];
        $allowed = [
            'admin',
            'default',
            'language_id',
            'meta',
            'name',
            'order',
            'status',
            'theme_id',
            'type_id',
        ];

        return array_intersect_key($attributes, array_flip($allowed));
    }

    /**
     * @param  array<string, mixed>  $descriptor
     * @param  array<string, int>  $importedSiteIdsBySourceId
     * @param  array<string, int>  $resolvedSiteIdsByRef
     * @return array<string, mixed>
     */
    private function siteDomainAttributes(array $descriptor, array $importedSiteIdsBySourceId, array $resolvedSiteIdsByRef): array
    {
        $attributes = is_array($descriptor['attributes'] ?? null) ? $descriptor['attributes'] : [];
        $allowed = [
            'default',
            'domain',
            'language_id',
            'path',
            'scheme',
            'site_id',
            'status',
        ];

        $attributes = array_intersect_key($attributes, array_flip($allowed));
        $sourceSiteId = $attributes['site_id'] ?? null;
        $siteRef = is_string($descriptor['site_ref'] ?? null) ? $descriptor['site_ref'] : null;

        if ($siteRef === null) {
            $descriptorRef = is_string($descriptor['ref'] ?? null) ? $descriptor['ref'] : null;
            $siteRef = $descriptorRef !== null && str_starts_with($descriptorRef, 'site:')
                ? $descriptorRef
                : null;
        }

        if (is_int($sourceSiteId) || is_string($sourceSiteId)) {
            $mappedSiteId = $importedSiteIdsBySourceId[(string) $sourceSiteId] ?? null;

            if ($mappedSiteId !== null) {
                $attributes['site_id'] = $mappedSiteId;

                return $attributes;
            }
        }

        if ($siteRef !== null && isset($resolvedSiteIdsByRef[$siteRef])) {
            $attributes['site_id'] = $resolvedSiteIdsByRef[$siteRef];

            return $attributes;
        }

        if (isset($attributes['site_id'])) {
            unset($attributes['site_id']);
        }

        return $attributes;
    }

    /**
     * @param  array<string, MatchResolution>  $resolved
     * @return array<string, int>
     */
    private function resolvedSiteIdsByRef(array $resolved): array
    {
        $siteIds = [];

        foreach ($resolved as $ref => $resolution) {
            if (! str_starts_with($ref, 'site:')) {
                continue;
            }

            $siteIds[$ref] = $resolution->localId;
        }

        return $siteIds;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function assertNoSiteDomainConflict(array $attributes): void
    {
        $domain = $attributes['domain'] ?? null;

        if (! is_string($domain) || $domain === '') {
            return;
        }

        $path = $attributes['path'] ?? null;
        $scheme = $attributes['scheme'] ?? null;

        $query = SiteDomain::query()
            ->where('domain', $domain)
            ->where('path', $path);

        if (is_string($scheme) && $scheme !== '') {
            $query->where('scheme', $scheme);
        } else {
            $query->where(static function (Builder $builder): void {
                $builder->whereNull('scheme')->orWhere('scheme', '');
            });
        }

        $exists = $query->exists();

        throw_if(
            $exists,
            RuntimeException::class,
            sprintf('Refusing to import conflicting site domain [%s].', $domain),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string $contents): array
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        return $decoded;
    }
}
