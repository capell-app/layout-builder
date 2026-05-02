<?php

declare(strict_types=1);

use Capell\Backup\Data\PackageManifest;
use Capell\Backup\Enums\PackageType;
use Carbon\CarbonImmutable;

it('serialises into a stable manifest array', function (): void {
    $manifest = new PackageManifest(
        packageType: PackageType::PageExport,
        capellVersion: '12.0.0',
        exportedAt: CarbonImmutable::parse('2026-04-17T00:00:00Z'),
        sourceEnvironment: 'testing',
        sourceLiveVersionId: 42,
        pageCount: 2,
        siteCount: 0,
        relationCounts: ['layouts' => 1],
        note: 'release candidate',
        sourceWorkspaceId: 3,
    );

    $array = $manifest->withChecksums(['payload' => 'sha256-abc'])->toArray();

    expect($array)
        ->toHaveKey('schema_version', PackageManifest::SCHEMA_VERSION)
        ->toHaveKey('package_type', 'page-export')
        ->toHaveKey('source_workspace_id', 3)
        ->toHaveKey('source_live_version_id', 42)
        ->toHaveKey('page_count', 2)
        ->toHaveKey('relation_counts', ['layouts' => 1])
        ->toHaveKey('note', 'release candidate')
        ->toHaveKey('checksums', ['payload' => 'sha256-abc']);
});
