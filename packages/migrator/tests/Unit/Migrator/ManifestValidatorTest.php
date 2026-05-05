<?php

declare(strict_types=1);

use Capell\Migrator\Data\PackageManifest;
use Capell\Migrator\Services\Import\ManifestValidator;

it('accepts a manifest whose schema and package type are known', function (): void {
    $validator = new ManifestValidator;

    $report = $validator->validate([
        'schema_version' => PackageManifest::SCHEMA_VERSION,
        'package_type' => 'page-export',
        'capell_version' => '0.0.1',
    ]);

    expect($report->isValid())->toBeTrue()
        ->and($report->errors)->toBe([]);
});

it('rejects a manifest with a mismatched schema version', function (): void {
    $validator = new ManifestValidator;

    $report = $validator->validate([
        'schema_version' => 99,
        'package_type' => 'page-export',
    ]);

    expect($report->isValid())->toBeFalse()
        ->and($report->errors)->not->toBe([]);
});

it('rejects a manifest with an unknown package type', function (): void {
    $validator = new ManifestValidator;

    $report = $validator->validate([
        'schema_version' => PackageManifest::SCHEMA_VERSION,
        'package_type' => 'sparkle',
    ]);

    expect($report->isValid())->toBeFalse();
});
