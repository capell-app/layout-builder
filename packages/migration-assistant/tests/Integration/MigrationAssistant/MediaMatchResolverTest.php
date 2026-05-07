<?php

declare(strict_types=1);

use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\MigrationAssistant\Services\Import\Resolvers\MediaMatchResolver;

function createMigrationAssistantMedia(Page $owner, array $overrides = []): Media
{
    $media = new Media;
    $media->forceFill([
        'model_type' => $owner->getMorphClass(),
        'model_id' => $owner->getKey(),
        'collection_name' => 'default',
        'name' => 'hero',
        'file_name' => 'hero.png',
        'mime_type' => 'image/png',
        'disk' => 'public',
        'conversions_disk' => 'public',
        'size' => 12,
        'manipulations' => [],
        'custom_properties' => [],
        'generated_conversions' => [],
        'responsive_images' => [],
        'order_column' => 1,
        ...$overrides,
    ])->save();

    return $media;
}

it('matches media by checksum before falling back to file name', function (): void {
    $owner = Page::factory()->create();
    $checksumMedia = createMigrationAssistantMedia($owner, [
        'file_name' => 'checksum-wins.png',
        'custom_properties' => ['checksum' => 'sha256-checksum-match'],
    ]);
    createMigrationAssistantMedia($owner, [
        'file_name' => 'shared-file-name.png',
        'custom_properties' => ['checksum' => 'sha256-other'],
    ]);

    $resolution = (new MediaMatchResolver)->resolve([
        'checksum' => 'sha256-checksum-match',
        'file_name' => 'shared-file-name.png',
    ]);

    expect($resolution)->not->toBeNull()
        ->and($resolution->localId)->toBe($checksumMedia->getKey())
        ->and($resolution->strategy)->toBe('checksum')
        ->and($resolution->confidence)->toBe(1.0);
});

it('falls back to a lower confidence file name match when checksum is missing', function (): void {
    $owner = Page::factory()->create();
    $media = createMigrationAssistantMedia($owner, ['file_name' => 'fallback.jpg']);

    $resolution = (new MediaMatchResolver)->resolve(['file_name' => 'fallback.jpg']);

    expect($resolution)->not->toBeNull()
        ->and($resolution->localId)->toBe($media->getKey())
        ->and($resolution->strategy)->toBe('file_name')
        ->and($resolution->confidence)->toBe(0.6);
});

it('returns null when neither checksum nor file name matches local media', function (): void {
    $owner = Page::factory()->create();
    createMigrationAssistantMedia($owner, [
        'file_name' => 'existing.png',
        'custom_properties' => ['checksum' => 'sha256-existing'],
    ]);

    expect((new MediaMatchResolver)->resolve([
        'checksum' => 'sha256-missing',
        'file_name' => 'missing.png',
    ]))->toBeNull();
});
