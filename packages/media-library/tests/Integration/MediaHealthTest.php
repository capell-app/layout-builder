<?php

declare(strict_types=1);

use Capell\Admin\Support\CapellAdminManager;
use Capell\Admin\Support\Extensions\ExtensionPageRegistry;
use Capell\MediaLibrary\Actions\DashboardReports\BuildMediaHealthQueryAction;
use Capell\MediaLibrary\Filament\Pages\MediaHealthPage;
use Capell\MediaLibrary\Tests\Fixtures\TestCuratorOwner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('media_health_query_uses_curator_rows_and_known_owner_foreign_keys', function (): void {
    config()->set('capell.media_library.owner_foreign_keys', [
        ['table' => 'test_curator_owners', 'column' => 'image_id'],
    ]);

    $healthyMediaId = insertCuratorHealthMedia('healthy', 'Useful alt text', now());
    $missingAltMediaId = insertCuratorHealthMedia('missing-alt', null, now());
    $unusedMediaId = insertCuratorHealthMedia('unused', 'Unused alt text', now());
    $staleMediaId = insertCuratorHealthMedia('stale', 'Stale alt text', now()->subDays(91));

    TestCuratorOwner::query()->create(['name' => 'Healthy Owner', 'image_id' => $healthyMediaId]);
    TestCuratorOwner::query()->create(['name' => 'Missing Alt Owner', 'image_id' => $missingAltMediaId]);
    TestCuratorOwner::query()->create(['name' => 'Stale Owner', 'image_id' => $staleMediaId]);

    $records = BuildMediaHealthQueryAction::run()->get()->keyBy('id');

    expect($records->keys()->all())->not->toContain($healthyMediaId);
    expect($records->keys()->all())->toContain($missingAltMediaId, $unusedMediaId, $staleMediaId);
    expect((int) $records->get($missingAltMediaId)->usage_count)->toBe(1);
    expect((int) $records->get($unusedMediaId)->usage_count)->toBe(0);
});

test('media_health_query_is_empty_when_curator_table_has_not_been_installed', function (): void {
    Schema::dropIfExists('curator');

    expect(BuildMediaHealthQueryAction::run()->get())->toHaveCount(0);
});

test('media health page registers as an extension page', function (): void {
    app()->singleton(ExtensionPageRegistry::class, fn (): ExtensionPageRegistry => new ExtensionPageRegistry);
    app()->singleton(CapellAdminManager::class, fn (): CapellAdminManager => new CapellAdminManager);

    resolve(CapellAdminManager::class)->registerExtensionPage(
        'capell-app/media-library',
        MediaHealthPage::class,
    );

    $extensionPage = collect(resolve(ExtensionPageRegistry::class)->entries())
        ->first(fn (array $extensionPage): bool => $extensionPage['page'] === MediaHealthPage::class);

    expect($extensionPage['page'] ?? null)->toBe(MediaHealthPage::class);
});

function insertCuratorHealthMedia(string $name, ?string $alt, DateTimeInterface $updatedAt): int
{
    return DB::table('curator')->insertGetId([
        'disk' => 'public',
        'directory' => 'media',
        'visibility' => 'public',
        'name' => $name,
        'path' => 'media/' . $name . '.jpg',
        'width' => 800,
        'height' => 600,
        'size' => 10000,
        'type' => 'image/jpeg',
        'ext' => 'jpg',
        'alt' => $alt,
        'title' => null,
        'description' => null,
        'caption' => null,
        'exif' => null,
        'curations' => null,
        'created_at' => now(),
        'updated_at' => $updatedAt,
    ]);
}
