<?php

declare(strict_types=1);

namespace Capell\Backup\Services\Import;

use Capell\Backup\Contracts\BackupContextResolver;
use Capell\Backup\Contracts\BackupRowContributor;
use Capell\Backup\Contracts\NullBackupContextResolver;
use Capell\Backup\Contracts\NullBackupRowContributor;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageUrl;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Applies a read page-export package to the local database, writing new
 * Page records into the given target context.
 *
 * Write flow per transaction:
 *   1. Pass 1 — create a Page row per descriptor, recording source-id → local-id.
 *   2. Pass 2 — rewrite parent_id using the source→local table.
 *   3. Per-page — restore owned PageUrl rows and rebind Media owners.
 */
final readonly class PageImportService
{
    public function __construct(
        private BackupContextResolver $contextResolver = new NullBackupContextResolver,
        private BackupRowContributor $rowContributor = new NullBackupRowContributor,
    ) {}

    public function import(
        PackageReadResult $package,
        ResolutionMap $resolutionMap,
    ): ImportExecutionReport {
        $created = [];
        $skipped = 0;
        $errors = [];
        $urlsCreated = 0;
        $mediaReassigned = 0;

        return $this->contextResolver->wrap(function () use (
            $package,
            $resolutionMap,
            &$created,
            &$skipped,
            &$errors,
            &$urlsCreated,
            &$mediaReassigned,
        ): ImportExecutionReport {
            DB::transaction(function () use (
                $package,
                $resolutionMap,
                &$created,
                &$skipped,
                &$errors,
                &$urlsCreated,
                &$mediaReassigned,
            ): void {
                /** @var array<string, array<string, mixed>> $descriptorsByPath */
                $descriptorsByPath = [];
                /** @var array<int|string, int|string> $sourceIdToLocalId */
                $sourceIdToLocalId = [];

                foreach ($package->payload as $entryPath => $contents) {
                    if (! str_starts_with($entryPath, 'pages/')) {
                        continue;
                    }

                    try {
                        $descriptor = $this->decode($contents);
                        $descriptorsByPath[$entryPath] = $descriptor;

                        $pageId = $this->writePage($descriptor, $resolutionMap);
                        if ($pageId === null) {
                            $skipped++;

                            continue;
                        }

                        $created[] = $pageId;
                        $sourceId = $this->extractSourceId($descriptor);
                        if ($sourceId !== null) {
                            $sourceIdToLocalId[$sourceId] = $pageId;
                        }
                    } catch (Throwable $e) {
                        $errors[] = sprintf('[%s] %s', $entryPath, $e->getMessage());
                    }
                }

                foreach ($descriptorsByPath as $entryPath => $descriptor) {
                    $sourceId = $this->extractSourceId($descriptor);
                    $localId = $sourceId === null ? null : ($sourceIdToLocalId[$sourceId] ?? null);
                    if ($localId === null) {
                        continue;
                    }

                    try {
                        $this->remapParent($localId, $descriptor, $sourceIdToLocalId);
                        $urlsCreated += $this->restorePageUrls($localId, $descriptor);
                        $mediaReassigned += $this->rebindMedia($localId, $descriptor, $resolutionMap);
                    } catch (Throwable $e) {
                        $errors[] = sprintf('[%s owned-relations] %s', $entryPath, $e->getMessage());
                    }
                }
            });

            return new ImportExecutionReport(
                pagesCreated: count($created),
                pagesSkipped: $skipped,
                createdPageIds: $created,
                errors: $errors,
                pageUrlsCreated: $urlsCreated,
                mediaReassigned: $mediaReassigned,
            );
        });
    }

    /**
     * @param  array<string, mixed>  $descriptor
     */
    private function writePage(array $descriptor, ResolutionMap $map): int|string|null
    {
        $attributes = is_array($descriptor['attributes'] ?? null) ? $descriptor['attributes'] : [];
        $shared = is_array($descriptor['shared_relations'] ?? null) ? $descriptor['shared_relations'] : [];

        $rewrites = [
            'layout' => 'layout_id',
            'type' => 'type_id',
            'site' => 'site_id',
        ];

        foreach ($rewrites as $relationKey => $column) {
            $ref = $shared[$relationKey]['ref'] ?? null;
            if (! is_string($ref)) {
                continue;
            }

            $localId = $map->localIdFor($ref);
            if ($localId === null) {
                return null;
            }

            $attributes[$column] = $localId;
        }

        unset(
            $attributes['id'],
            $attributes['uuid'],
            $attributes['_lft'],
            $attributes['_rgt'],
            $attributes['created_by'],
            $attributes['updated_by'],
            $attributes['deleted_by'],
            $attributes['created_at'],
            $attributes['updated_at'],
            $attributes['deleted_at'],
        );

        $attributes = $this->rowContributor->normalizeIncomingRow($attributes);
        $attributes = array_intersect_key($attributes, array_flip($this->importablePageAttributes()));

        // parent_id is written in pass 2 once the source → local map is built.
        $attributes['parent_id'] = null;

        $page = new Page;
        $page->forceFill($attributes);
        $page->save();

        return $page->getKey();
    }

    /**
     * @return list<string>
     */
    private function importablePageAttributes(): array
    {
        return [
            'admin',
            'layout_id',
            'meta',
            'name',
            'order',
            'parent_id',
            'site_id',
            'type_id',
            'visible_from',
            'visible_until',
        ];
    }

    /**
     * @param  array<string, mixed>  $descriptor
     * @param  array<int|string, int|string>  $sourceIdToLocalId
     */
    private function remapParent(int|string $localId, array $descriptor, array $sourceIdToLocalId): void
    {
        $attributes = is_array($descriptor['attributes'] ?? null) ? $descriptor['attributes'] : [];
        $sourceParentId = $attributes['parent_id'] ?? null;

        if ($sourceParentId === null) {
            return;
        }

        $localParentId = $sourceIdToLocalId[$sourceParentId] ?? null;
        if ($localParentId === null) {
            return;
        }

        Page::query()->whereKey($localId)->update(['parent_id' => $localParentId]);
    }

    /**
     * @param  array<string, mixed>  $descriptor
     */
    private function restorePageUrls(int|string $localId, array $descriptor): int
    {
        $ownedRelations = is_array($descriptor['owned_relations'] ?? null) ? $descriptor['owned_relations'] : [];
        $pageUrls = is_array($ownedRelations['page_urls'] ?? null) ? $ownedRelations['page_urls'] : [];
        $page = Page::query()->findOrFail($localId);
        $count = 0;

        foreach ($pageUrls as $urlAttributes) {
            if (! is_array($urlAttributes)) {
                continue;
            }

            $url = new PageUrl;
            $url->fill($this->restorablePageUrlAttributes($urlAttributes, $page));
            $url->save();
            $count++;
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $urlAttributes
     * @return array<string, mixed>
     */
    private function restorablePageUrlAttributes(array $urlAttributes, Page $page): array
    {
        $allowed = [
            'language_id',
            'status',
            'type',
            'url',
            'target_url',
            'status_code',
            'is_manual',
            'notes',
        ];

        $attributes = array_intersect_key($urlAttributes, array_flip($allowed));
        $attributes['site_id'] = $page->site_id;
        $attributes['pageable_type'] = $page->getMorphClass();
        $attributes['pageable_id'] = $page->getKey();

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $descriptor
     */
    private function rebindMedia(int|string $localId, array $descriptor, ResolutionMap $map): int
    {
        $bindings = is_array($descriptor['media_bindings'] ?? null) ? $descriptor['media_bindings'] : [];
        $count = 0;

        foreach ($bindings as $binding) {
            $ref = $binding['ref'] ?? null;
            if (! is_string($ref)) {
                continue;
            }

            $localMediaId = $map->localIdFor($ref);
            if ($localMediaId === null) {
                continue;
            }

            Media::query()
                ->whereKey($localMediaId)
                ->update([
                    'model_type' => (new Page)->getMorphClass(),
                    'model_id' => $localId,
                ]);
            $count++;
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $descriptor
     */
    private function extractSourceId(array $descriptor): int|string|null
    {
        return $descriptor['id'] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string $contents): array
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($contents, true);

        return $decoded;
    }
}
