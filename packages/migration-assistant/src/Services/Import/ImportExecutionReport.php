<?php

declare(strict_types=1);

namespace Capell\MigrationAssistant\Services\Import;

use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;

/**
 * Summary of a single import run. Stored back on the ImportSession so the
 * wizard can show what happened after the Execute step and so operators
 * have an auditable trail of what an archive touched.
 */
final readonly class ImportExecutionReport
{
    /**
     * @param  array<int, string>  $errors
     * @param  array<int, int|string>  $createdPageIds
     * @param  array<int, int|string>  $createdSiteIds
     * @param  array<int, int|string>  $createdSiteDomainIds
     */
    public function __construct(
        public int $pagesCreated,
        public int $pagesSkipped,
        public array $createdPageIds,
        public array $errors,
        public int $pageUrlsCreated = 0,
        public int $mediaReassigned = 0,
        public array $createdSiteIds = [],
        public array $createdSiteDomainIds = [],
    ) {}

    public function isSuccess(): bool
    {
        return $this->errors === [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'pages_created' => $this->pagesCreated,
            'pages_skipped' => $this->pagesSkipped,
            'page_urls_created' => $this->pageUrlsCreated,
            'media_reassigned' => $this->mediaReassigned,
            'created_page_ids' => $this->createdPageIds,
            'created_site_ids' => $this->createdSiteIds,
            'created_site_domain_ids' => $this->createdSiteDomainIds,
            'errors' => $this->errors,
        ];
    }

    /**
     * @return list<array{class: class-string, id: int|string}>
     */
    public function createdModels(): array
    {
        $pages = array_map(
            static fn (int|string $id): array => [
                'class' => Page::class,
                'id' => $id,
            ],
            $this->createdPageIds,
        );

        $sites = array_map(
            static fn (int|string $id): array => [
                'class' => Site::class,
                'id' => $id,
            ],
            $this->createdSiteIds,
        );

        $siteDomains = array_map(
            static fn (int|string $id): array => [
                'class' => SiteDomain::class,
                'id' => $id,
            ],
            $this->createdSiteDomainIds,
        );

        return [
            ...$pages,
            ...$sites,
            ...$siteDomains,
        ];
    }
}
