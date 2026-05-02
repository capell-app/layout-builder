<?php

declare(strict_types=1);

namespace Capell\Backup\Actions;

use Capell\Backup\Contracts\PageCollisionDetector;
use Capell\Backup\Data\PageReviewRow;
use Capell\Backup\Services\Import\PackageReadResult;
use Capell\Backup\Services\Import\PageUrlCollisionDetector;
use Capell\Backup\Services\Import\ResolutionMap;
use JsonException;

/**
 * Decodes the `pages/*.json` entries in an import package into the per-row
 * data shown in the H2.1 wizard "Review" step.
 *
 * Core is collision-agnostic — it extracts URL/site data and defers to an
 * injected {@see PageCollisionDetector} to compute collision state. The
 * default {@see NullPageCollisionDetector} reports no collisions; packages
 * that care about live/draft overlap bind their own detector.
 *
 * The incoming site is looked up via the shared-relation ref in the
 * page's envelope (`shared_relations.site.ref`) and resolved through
 * {@see ResolutionMap} so tuple-level (site, language, url) matching can
 * happen once site imports are supported.
 */
final readonly class BuildPageReviewRows
{
    public function __construct(
        private PageCollisionDetector $detector = new PageUrlCollisionDetector,
    ) {}

    /**
     * @return list<PageReviewRow>
     */
    public function run(PackageReadResult $package, ResolutionMap $map): array
    {
        $rows = [];

        foreach ($package->payload as $entryPath => $contents) {
            if (! str_starts_with($entryPath, 'pages/')) {
                continue;
            }

            if (! str_ends_with($entryPath, '.json')) {
                continue;
            }

            try {
                /** @var array<string, mixed> $envelope */
                $envelope = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                continue;
            }

            if (($envelope['type'] ?? null) !== 'page') {
                continue;
            }

            $uuid = is_string($envelope['uuid'] ?? null) ? $envelope['uuid'] : null;
            if ($uuid === null) {
                continue;
            }

            $attributes = is_array($envelope['attributes'] ?? null) ? $envelope['attributes'] : [];
            $title = is_string($attributes['title'] ?? null) ? $attributes['title'] : null;

            $siteRef = null;
            $sharedRelations = $envelope['shared_relations'] ?? null;
            if (is_array($sharedRelations) && is_array($sharedRelations['site'] ?? null)) {
                $candidate = $sharedRelations['site']['ref'] ?? null;
                $siteRef = is_string($candidate) ? $candidate : null;
            }

            $resolvedSiteId = null;
            if ($siteRef !== null) {
                $localId = $map->localIdFor($siteRef);
                if (is_int($localId)) {
                    $resolvedSiteId = $localId;
                } elseif (is_string($localId) && ctype_digit($localId)) {
                    $resolvedSiteId = (int) $localId;
                }
            }

            $urls = $this->extractUrls($envelope);
            $primaryUrl = $urls === [] ? null : $urls[0]['url'];

            [$collisionState, $conflictMessages, $suggestedAction] = $this->detector->detect(
                $urls,
                $resolvedSiteId,
            );

            $rows[] = new PageReviewRow(
                uuid: $uuid,
                title: $title,
                primaryUrl: $primaryUrl,
                resolvedSiteId: $resolvedSiteId,
                siteRef: $siteRef,
                urls: $urls,
                collisionState: $collisionState,
                conflictMessages: $conflictMessages,
                suggestedAction: $suggestedAction,
            );
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $envelope
     * @return list<array{site_id: int|null, language_id: int|null, url: string}>
     */
    private function extractUrls(array $envelope): array
    {
        $owned = $envelope['owned_relations'] ?? null;
        if (! is_array($owned)) {
            return [];
        }

        $pageUrls = $owned['page_urls'] ?? null;
        if (! is_array($pageUrls)) {
            return [];
        }

        $urls = [];
        foreach ($pageUrls as $row) {
            if (! is_array($row)) {
                continue;
            }

            $url = $row['url'] ?? null;
            if (! is_string($url)) {
                continue;
            }

            if ($url === '') {
                continue;
            }

            $siteId = is_int($row['site_id'] ?? null) ? $row['site_id'] : null;
            $languageId = is_int($row['language_id'] ?? null) ? $row['language_id'] : null;

            $urls[] = [
                'site_id' => $siteId,
                'language_id' => $languageId,
                'url' => $url,
            ];
        }

        return $urls;
    }
}
