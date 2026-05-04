<?php

declare(strict_types=1);

use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Migrator\Services\Import\MediaIngestService;
use Illuminate\Support\Facades\Storage;

function makeMediaArchive(string $hex, string $fileName, string $bytes): string
{
    $path = tempnam(sys_get_temp_dir(), 'capell-migrator-') . '.zip';
    $archive = new ZipArchive;
    $archive->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    $entry = sprintf('media/%s.%s', $hex, pathinfo($fileName, PATHINFO_EXTENSION));
    $archive->addFromString($entry, $bytes);
    $archive->close();

    return $path;
}

it('ingests a new media binary and creates a Media row', function (): void {
    Storage::fake('public');

    $bytes = 'binary-contents-for-fixture';
    $hex = hash('sha256', $bytes);
    $archivePath = makeMediaArchive($hex, 'hero.png', $bytes);
    $owner = Page::factory()->create();

    $descriptor = [
        'ref' => 'media:777',
        'checksum' => 'sha256-' . $hex,
        'file_name' => 'hero.png',
        'mime_type' => 'image/png',
        'collection_name' => 'hero',
    ];

    $mediaId = (new MediaIngestService)->ingest($archivePath, $descriptor, $owner);

    $row = Media::query()->whereKey($mediaId)->firstOrFail();
    expect($row->getAttribute('file_name'))->toBe('hero.png')
        ->and($row->getAttribute('mime_type'))->toBe('image/png')
        ->and($row->getAttribute('collection_name'))->toBe('hero')
        ->and((int) $row->getAttribute('size'))->toBe(strlen($bytes))
        ->and($row->getAttribute('custom_properties')['checksum'] ?? null)->toBe('sha256-' . $hex);

    Storage::disk('public')->assertExists($row->getPathRelativeToRoot());
    Storage::disk('public')->assertMissing('migrator/ingested/' . $hex . '.png');

    @unlink($archivePath);
});

it('returns the existing media id on checksum match (idempotent)', function (): void {
    Storage::fake('public');

    $bytes = 'duplicate-fixture-bytes';
    $hex = hash('sha256', $bytes);
    $archivePath = makeMediaArchive($hex, 'img.jpg', $bytes);
    $owner = Page::factory()->create();

    $existing = new Media;
    $existing->forceFill([
        'model_type' => $owner->getMorphClass(),
        'model_id' => $owner->getKey(),
        'collection_name' => 'default',
        'name' => 'img',
        'file_name' => 'img.jpg',
        'mime_type' => 'image/jpeg',
        'disk' => 'public',
        'conversions_disk' => 'public',
        'size' => strlen($bytes),
        'manipulations' => [],
        'custom_properties' => ['checksum' => 'sha256-' . $hex],
        'generated_conversions' => [],
        'responsive_images' => [],
        'order_column' => 1,
    ])->save();

    $descriptor = [
        'ref' => 'media:1',
        'checksum' => 'sha256-' . $hex,
        'file_name' => 'img.jpg',
        'mime_type' => 'image/jpeg',
    ];

    $resolvedId = (new MediaIngestService)->ingest($archivePath, $descriptor, $owner);

    expect($resolvedId)->toBe($existing->getKey())
        ->and(Media::query()->count())->toBe(1);

    @unlink($archivePath);
});

it('throws when the archive entry is missing', function (): void {
    Storage::fake('public');

    $path = tempnam(sys_get_temp_dir(), 'capell-migrator-') . '.zip';
    $archive = new ZipArchive;
    $archive->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $archive->addFromString('placeholder.txt', 'x');
    $archive->close();

    $owner = Page::factory()->create();

    $descriptor = [
        'ref' => 'media:404',
        'checksum' => 'sha256-' . str_repeat('0', 64),
        'file_name' => 'missing.png',
        'mime_type' => 'image/png',
    ];

    expect(fn (): int|string => (new MediaIngestService)->ingest($path, $descriptor, $owner))
        ->toThrow(RuntimeException::class, 'missing from archive');

    @unlink($path);
});

it('throws when archive media bytes do not match the declared checksum', function (): void {
    Storage::fake('public');

    $declaredBytes = 'expected-binary-contents';
    $actualBytes = 'tampered-binary-contents';
    $hex = hash('sha256', $declaredBytes);
    $archivePath = makeMediaArchive($hex, 'tampered.png', $actualBytes);
    $owner = Page::factory()->create();

    $descriptor = [
        'ref' => 'media:tampered',
        'checksum' => 'sha256-' . $hex,
        'file_name' => 'tampered.png',
        'mime_type' => 'image/png',
    ];

    expect(fn (): int|string => (new MediaIngestService)->ingest($archivePath, $descriptor, $owner))
        ->toThrow(RuntimeException::class, 'checksum mismatch');

    @unlink($archivePath);
});

it('rejects oversized archive media before storing it', function (): void {
    Storage::fake('public');
    config()->set('migrator.limits.max_media_bytes', 5);

    $bytes = 'oversized-binary';
    $hex = hash('sha256', $bytes);
    $archivePath = makeMediaArchive($hex, 'large.png', $bytes);
    $owner = Page::factory()->create();

    $descriptor = [
        'ref' => 'media:large',
        'checksum' => 'sha256-' . $hex,
        'file_name' => 'large.png',
        'mime_type' => 'image/png',
    ];

    expect(fn (): int|string => (new MediaIngestService)->ingest($archivePath, $descriptor, $owner))
        ->toThrow(RuntimeException::class, 'exceeds the maximum import size');

    expect(Media::query()->count())->toBe(0);
    expect(Storage::disk('public')->allFiles())->toBe([]);

    @unlink($archivePath);
});

it('rejects oversized archive media before reading the entry contents', function (): void {
    Storage::fake('public');
    config()->set('migrator.limits.max_media_bytes', 5);

    $bytes = 'oversized-media-bytes';
    $hex = hash('sha256', $bytes);
    $archivePath = makeMediaArchive($hex, 'oversized.png', $bytes);
    $owner = Page::factory()->create();

    $descriptor = [
        'ref' => 'media:oversized',
        'checksum' => 'sha256-' . $hex,
        'file_name' => 'oversized.png',
        'mime_type' => 'image/png',
    ];

    expect(fn (): int|string => (new MediaIngestService)->ingest($archivePath, $descriptor, $owner))
        ->toThrow(RuntimeException::class, 'exceeds the maximum import size');

    expect(Media::query()->count())->toBe(0);

    @unlink($archivePath);
});
