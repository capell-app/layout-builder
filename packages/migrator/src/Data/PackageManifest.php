<?php

declare(strict_types=1);

namespace Capell\Migrator\Data;

use Capell\Migrator\Enums\PackageType;
use Carbon\CarbonImmutable;

/**
 * @phpstan-type ManifestArray array{
 *     schema_version: int,
 *     package_type: string,
 *     capell_version: string,
 *     exported_at: string,
 *     source_environment: string,
 *     source_workspace_id: ?int,
 *     source_live_version_id: ?int,
 *     page_count: int,
 *     site_count: int,
 *     relation_counts: array<string, int>,
 *     note: ?string,
 *     checksums: array<string, string>
 * }
 */
final readonly class PackageManifest
{
    /** @var int */
    public const SCHEMA_VERSION = 1;

    /**
     * @param  array<string, int>  $relationCounts
     * @param  array<string, string>  $checksums
     */
    public function __construct(
        public PackageType $packageType,
        public string $capellVersion,
        public CarbonImmutable $exportedAt,
        public string $sourceEnvironment,
        public ?int $sourceLiveVersionId,
        public int $pageCount,
        public int $siteCount,
        public array $relationCounts,
        public ?string $note = null,
        public array $checksums = [],
        public ?int $sourceWorkspaceId = null,
    ) {}

    /**
     * @return ManifestArray
     */
    public function toArray(): array
    {
        return [
            'schema_version' => self::SCHEMA_VERSION,
            'package_type' => $this->packageType->value,
            'capell_version' => $this->capellVersion,
            'exported_at' => $this->exportedAt->toIso8601String(),
            'source_environment' => $this->sourceEnvironment,
            'source_workspace_id' => $this->sourceWorkspaceId,
            'source_live_version_id' => $this->sourceLiveVersionId,
            'page_count' => $this->pageCount,
            'site_count' => $this->siteCount,
            'relation_counts' => $this->relationCounts,
            'note' => $this->note,
            'checksums' => $this->checksums,
        ];
    }

    /**
     * @param  array<string, string>  $checksums
     */
    public function withChecksums(array $checksums): self
    {
        return new self(
            packageType: $this->packageType,
            capellVersion: $this->capellVersion,
            exportedAt: $this->exportedAt,
            sourceEnvironment: $this->sourceEnvironment,
            sourceLiveVersionId: $this->sourceLiveVersionId,
            pageCount: $this->pageCount,
            siteCount: $this->siteCount,
            relationCounts: $this->relationCounts,
            note: $this->note,
            checksums: $checksums,
            sourceWorkspaceId: $this->sourceWorkspaceId,
        );
    }
}
