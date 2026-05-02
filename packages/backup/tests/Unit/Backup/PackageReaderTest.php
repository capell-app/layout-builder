<?php

declare(strict_types=1);

use Capell\Backup\Services\Import\PackageReader;
use Capell\Backup\Support\ChecksumGenerator;

beforeEach(function (): void {
    $this->tempArchive = tempnam(sys_get_temp_dir(), 'capell-pkg-') . '.zip';
});

afterEach(function (): void {
    if (is_file($this->tempArchive)) {
        unlink($this->tempArchive);
    }
});

function writeArchive(string $path, array $entries): void
{
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    foreach ($entries as $entry => $contents) {
        $zip->addFromString($entry, $contents);
    }

    $zip->close();
}

it('reads manifest, integrity, and payload entries from a well-formed archive', function (): void {
    $pageJson = json_encode(['uuid' => 'page-1', 'title' => 'Hello'], JSON_THROW_ON_ERROR);
    $manifestJson = json_encode(['schema_version' => 1, 'package_type' => 'page-export'], JSON_THROW_ON_ERROR);

    $integrity = ['files' => [
        'pages/page-1.json' => ChecksumGenerator::forString($pageJson),
        'manifest.json' => ChecksumGenerator::forString($manifestJson),
    ]];

    writeArchive($this->tempArchive, [
        'manifest.json' => $manifestJson,
        'integrity.json' => json_encode($integrity, JSON_THROW_ON_ERROR),
        'pages/page-1.json' => $pageJson,
    ]);

    $result = (new PackageReader)->read($this->tempArchive);

    expect($result->manifest['package_type'])->toBe('page-export')
        ->and($result->payload)->toHaveKey('pages/page-1.json');
});

it('throws when a payload file is tampered with', function (): void {
    $pageJson = json_encode(['uuid' => 'page-1'], JSON_THROW_ON_ERROR);

    $integrity = ['files' => [
        'pages/page-1.json' => 'sha256-wrong',
    ]];

    writeArchive($this->tempArchive, [
        'manifest.json' => json_encode(['schema_version' => 1], JSON_THROW_ON_ERROR),
        'integrity.json' => json_encode($integrity, JSON_THROW_ON_ERROR),
        'pages/page-1.json' => $pageJson,
    ]);

    (new PackageReader)->read($this->tempArchive);
})->throws(RuntimeException::class, 'Integrity checksum mismatch');

it('throws when an integrity-listed entry is missing from the archive', function (): void {
    $integrity = ['files' => ['pages/missing.json' => 'sha256-whatever']];

    writeArchive($this->tempArchive, [
        'manifest.json' => json_encode(['schema_version' => 1], JSON_THROW_ON_ERROR),
        'integrity.json' => json_encode($integrity, JSON_THROW_ON_ERROR),
    ]);

    (new PackageReader)->read($this->tempArchive);
})->throws(RuntimeException::class, 'is missing from archive');

it('rejects oversized media entries before reading them into payload memory', function (): void {
    config()->set('backup.limits.max_media_bytes', 5);

    $mediaBytes = 'oversized-media';
    $mediaPath = 'media/' . hash('sha256', $mediaBytes) . '.png';

    $integrity = ['files' => [
        $mediaPath => ChecksumGenerator::forString($mediaBytes),
    ]];

    writeArchive($this->tempArchive, [
        'manifest.json' => json_encode(['schema_version' => 1], JSON_THROW_ON_ERROR),
        'integrity.json' => json_encode($integrity, JSON_THROW_ON_ERROR),
        $mediaPath => $mediaBytes,
    ]);

    (new PackageReader)->read($this->tempArchive);
})->throws(RuntimeException::class, 'exceeds the maximum import size');

it('rejects oversized page payload entries before reading them', function (): void {
    config()->set('backup.limits.max_payload_json_bytes', 10);

    $pageJson = json_encode(['content' => str_repeat('x', 50)], JSON_THROW_ON_ERROR);
    $integrity = ['files' => [
        'pages/oversized.json' => ChecksumGenerator::forString($pageJson),
    ]];

    writeArchive($this->tempArchive, [
        'manifest.json' => json_encode(['schema_version' => 1], JSON_THROW_ON_ERROR),
        'integrity.json' => json_encode($integrity, JSON_THROW_ON_ERROR),
        'pages/oversized.json' => $pageJson,
    ]);

    (new PackageReader)->read($this->tempArchive);
})->throws(RuntimeException::class, 'exceeds the maximum JSON payload size');

it('rejects oversized relation payload entries before reading them', function (): void {
    config()->set('backup.limits.max_payload_json_bytes', 10);

    $relationJson = json_encode(['file_name' => str_repeat('x', 50)], JSON_THROW_ON_ERROR);
    $integrity = ['files' => [
        'relations/media/oversized.json' => ChecksumGenerator::forString($relationJson),
    ]];

    writeArchive($this->tempArchive, [
        'manifest.json' => json_encode(['schema_version' => 1], JSON_THROW_ON_ERROR),
        'integrity.json' => json_encode($integrity, JSON_THROW_ON_ERROR),
        'relations/media/oversized.json' => $relationJson,
    ]);

    (new PackageReader)->read($this->tempArchive);
})->throws(RuntimeException::class, 'exceeds the maximum JSON payload size');

it('rejects packages whose aggregate uncompressed size is too large', function (): void {
    config()->set('backup.limits.max_package_uncompressed_bytes', 30);

    $firstPageJson = json_encode(['content' => str_repeat('a', 20)], JSON_THROW_ON_ERROR);
    $secondPageJson = json_encode(['content' => str_repeat('b', 20)], JSON_THROW_ON_ERROR);
    $integrity = ['files' => [
        'pages/first.json' => ChecksumGenerator::forString($firstPageJson),
        'pages/second.json' => ChecksumGenerator::forString($secondPageJson),
    ]];

    writeArchive($this->tempArchive, [
        'manifest.json' => json_encode(['schema_version' => 1], JSON_THROW_ON_ERROR),
        'integrity.json' => json_encode($integrity, JSON_THROW_ON_ERROR),
        'pages/first.json' => $firstPageJson,
        'pages/second.json' => $secondPageJson,
    ]);

    (new PackageReader)->read($this->tempArchive);
})->throws(RuntimeException::class, 'exceeds the maximum package size');

it('rejects oversized manifest metadata before reading it', function (): void {
    config()->set('backup.limits.max_metadata_json_bytes', 10);

    $manifestJson = json_encode(['content' => str_repeat('x', 50)], JSON_THROW_ON_ERROR);
    $integrity = ['files' => []];

    writeArchive($this->tempArchive, [
        'manifest.json' => $manifestJson,
        'integrity.json' => json_encode($integrity, JSON_THROW_ON_ERROR),
    ]);

    (new PackageReader)->read($this->tempArchive);
})->throws(RuntimeException::class, 'exceeds the maximum metadata JSON size');

it('rejects oversized integrity metadata before reading it', function (): void {
    config()->set('backup.limits.max_metadata_json_bytes', 10);

    $integrity = ['files' => [
        'pages/page-1.json' => str_repeat('x', 50),
    ]];

    writeArchive($this->tempArchive, [
        'manifest.json' => json_encode(['schema_version' => 1], JSON_THROW_ON_ERROR),
        'integrity.json' => json_encode($integrity, JSON_THROW_ON_ERROR),
    ]);

    (new PackageReader)->read($this->tempArchive);
})->throws(RuntimeException::class, 'exceeds the maximum metadata JSON size');
