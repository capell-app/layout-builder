<?php

declare(strict_types=1);

use Capell\MediaCurator\Actions\MigrateSpatieMediaToCuratorAction;
use Capell\MediaCurator\Data\MigrateSpatieMediaInput;
use Capell\MediaCurator\Tests\Fixtures\TestCuratorOwner;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Helper: seed Spatie `media` table rows paired with TestCuratorOwner rows.
 *
 * Creates $rowCount owner models and inserts matching Spatie media rows.
 * Each owner gets one media row. All media rows use the disk 'public'.
 *
 * @param  string[]  $collections  Cycled over when multiple collections requested.
 */
function seedSpatieFixture(int $rowCount, array $collections = ['image']): void
{
    $collectionCount = count($collections);

    for ($index = 0; $index < $rowCount; $index++) {
        $owner = TestCuratorOwner::query()->create(['name' => 'Owner ' . $index]);
        $collection = $collections[$index % $collectionCount];

        DB::table('media')->insert([
            'model_type' => TestCuratorOwner::class,
            'model_id' => $owner->getKey(),
            'uuid' => (string) Str::uuid(),
            'collection_name' => $collection,
            'name' => 'file-' . $index,
            'file_name' => sprintf('file-%d.jpg', $index),
            'mime_type' => 'image/jpeg',
            'disk' => 'public',
            'conversions_disk' => null,
            'size' => 10000 + $index,
            'manipulations' => '[]',
            'custom_properties' => '[]',
            'generated_conversions' => '[]',
            'responsive_images' => '[]',
            'order_column' => $index + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

/**
 * Ensure the Spatie `media` table exists before each test.
 * The TestCase only creates `curator` and `test_curator_owners`,
 * so we create `media` inline here.
 */
beforeEach(function (): void {
    if (! Schema::hasTable('media')) {
        Schema::create('media', function (Blueprint $table): void {
            $table->id();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->uuid()->nullable()->unique();
            $table->string('collection_name');
            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->string('disk');
            $table->string('conversions_disk')->nullable();
            $table->unsignedBigInteger('size');
            $table->json('manipulations');
            $table->json('custom_properties');
            $table->json('generated_conversions');
            $table->json('responsive_images');
            $table->unsignedInteger('order_column')->nullable()->index();
            $table->timestamps();
        });
    }
});

test('dry_run_reports_without_writing', function (): void {
    seedSpatieFixture(2, ['image']);

    $result = MigrateSpatieMediaToCuratorAction::run(new MigrateSpatieMediaInput(dryRun: true));

    expect($result->processed)->toBe(2);
    expect($result->created)->toBe(2);
    expect($result->ownersUpdated)->toBe(2);

    expect(DB::table('curator')->count())->toBe(0);

    $owners = DB::table('test_curator_owners')->get();
    foreach ($owners as $owner) {
        expect($owner->image_id)->toBeNull();
    }
});

test('migration_uses_spatie_disk_relative_paths_to_avoid_same_filename_collisions', function (): void {
    $firstOwner = TestCuratorOwner::query()->create(['name' => 'First Owner']);
    $secondOwner = TestCuratorOwner::query()->create(['name' => 'Second Owner']);

    $firstMediaId = DB::table('media')->insertGetId([
        'model_type' => TestCuratorOwner::class,
        'model_id' => $firstOwner->getKey(),
        'uuid' => (string) Str::uuid(),
        'collection_name' => 'image',
        'name' => 'shared-photo',
        'file_name' => 'shared.jpg',
        'mime_type' => 'image/jpeg',
        'disk' => 'public',
        'conversions_disk' => null,
        'size' => 8000,
        'manipulations' => '[]',
        'custom_properties' => '[]',
        'generated_conversions' => '[]',
        'responsive_images' => '[]',
        'order_column' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $secondMediaId = DB::table('media')->insertGetId([
        'model_type' => TestCuratorOwner::class,
        'model_id' => $secondOwner->getKey(),
        'uuid' => (string) Str::uuid(),
        'collection_name' => 'image',
        'name' => 'shared-photo',
        'file_name' => 'shared.jpg',
        'mime_type' => 'image/jpeg',
        'disk' => 'public',
        'conversions_disk' => null,
        'size' => 9000,
        'manipulations' => '[]',
        'custom_properties' => '[]',
        'generated_conversions' => '[]',
        'responsive_images' => '[]',
        'order_column' => 2,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->artisan('capell:media-migrate-to-curator')->assertSuccessful();

    expect(DB::table('curator')->count())->toBe(2);

    expect(DB::table('curator')->pluck('path')->all())->toContain(
        $firstMediaId . '/shared.jpg',
        $secondMediaId . '/shared.jpg',
    );

    expect(DB::table('curator')->pluck('directory')->all())->toContain(
        (string) $firstMediaId,
        (string) $secondMediaId,
    );
});

test('migration_preserves_spatie_metadata_in_curator_columns', function (): void {
    $owner = TestCuratorOwner::query()->create(['name' => 'Metadata Owner']);

    $mediaId = DB::table('media')->insertGetId([
        'model_type' => TestCuratorOwner::class,
        'model_id' => $owner->getKey(),
        'uuid' => (string) Str::uuid(),
        'collection_name' => 'image',
        'name' => 'metadata-photo',
        'file_name' => 'metadata.jpg',
        'mime_type' => 'image/jpeg',
        'disk' => 'public',
        'conversions_disk' => 'public',
        'size' => 12000,
        'manipulations' => json_encode(['thumb' => ['fit' => 'crop']], JSON_THROW_ON_ERROR),
        'custom_properties' => json_encode([
            'alt' => 'A dramatic test image',
            'title' => 'Metadata title',
            'description' => 'Metadata description',
            'caption' => 'Metadata caption',
            'dimensions' => ['width' => 1600, 'height' => 900],
            'credit' => 'Capell Studio',
        ], JSON_THROW_ON_ERROR),
        'generated_conversions' => json_encode(['thumb' => true], JSON_THROW_ON_ERROR),
        'responsive_images' => json_encode([
            'media_library_original' => [
                'urls' => ['metadata___media_library_original_800_450.jpg'],
                'base64svg' => 'placeholder',
            ],
        ], JSON_THROW_ON_ERROR),
        'order_column' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->artisan('capell:media-migrate-to-curator')->assertSuccessful();

    $curatorRow = DB::table('curator')->where('path', $mediaId . '/metadata.jpg')->first();
    $exif = json_decode((string) $curatorRow->exif, true);

    expect($curatorRow->alt)->toBe('A dramatic test image');
    expect($curatorRow->title)->toBe('Metadata title');
    expect($curatorRow->description)->toBe('Metadata description');
    expect($curatorRow->caption)->toBe('Metadata caption');
    expect($curatorRow->width)->toBe(1600);
    expect($curatorRow->height)->toBe(900);
    expect($exif['spatie_media_library']['custom_properties']['credit'])->toBe('Capell Studio');
    expect($exif['spatie_media_library']['responsive_images']['media_library_original']['base64svg'])->toBe('placeholder');
    expect($exif['spatie_media_library']['generated_conversions']['thumb'])->toBeTrue();
    expect($exif['spatie_media_library']['manipulations']['thumb']['fit'])->toBe('crop');
});

test('full_migration_creates_curator_rows_and_populates_owner_fk', function (): void {
    seedSpatieFixture(3, ['image']);

    $this->artisan('capell:media-migrate-to-curator')
        ->assertSuccessful();

    expect(DB::table('curator')->count())->toBe(3);

    $owners = DB::table('test_curator_owners')->get();
    foreach ($owners as $owner) {
        expect($owner->image_id)->not->toBeNull();
    }
});

test('migration_is_idempotent', function (): void {
    seedSpatieFixture(2, ['image']);

    $this->artisan('capell:media-migrate-to-curator')->assertSuccessful();
    $this->artisan('capell:media-migrate-to-curator')->assertSuccessful();

    // Exactly 2 curator rows — no duplicates on second run.
    expect(DB::table('curator')->count())->toBe(2);
});

test('collection_filter_only_migrates_matching_rows', function (): void {
    // Seed one row for 'image', one for 'hero', one for 'gallery'.
    // Only 'hero' and 'gallery' map to columns that don't exist on
    // the owner table, so only 'image' will succeed.
    seedSpatieFixture(3, ['image', 'hero', 'gallery']);

    $this->artisan('capell:media-migrate-to-curator', ['--collection' => ['image']])
        ->assertSuccessful();

    // Only the 'image' collection was requested; 1 curator row expected.
    expect(DB::table('curator')->count())->toBe(1);
});

test('missing_fk_column_is_warned_not_fatal', function (): void {
    // 'unknown_collection' maps to 'unknown_collection_id' which does not exist.
    seedSpatieFixture(1, ['unknown_collection']);

    $result = $this->artisan('capell:media-migrate-to-curator');

    $result->assertSuccessful();

    // No curator rows should be created because the column is missing.
    expect(DB::table('curator')->count())->toBe(0);
});

test('command_populates_only_null_fk_columns', function (): void {
    // Create two owner rows: one already has image_id populated, one does not.
    $existingCuratorId = DB::table('curator')->insertGetId([
        'disk' => 'public',
        'directory' => '',
        'visibility' => 'public',
        'name' => 'pre-existing',
        'path' => 'media/pre-existing.jpg',
        'size' => 5000,
        'type' => 'image/jpeg',
        'ext' => 'jpg',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $ownerWithMedia = TestCuratorOwner::query()->create(['name' => 'Already has media', 'image_id' => $existingCuratorId]);
    $ownerWithoutMedia = TestCuratorOwner::query()->create(['name' => 'No media yet']);

    // Only ownerWithoutMedia has a Spatie row pointing at it.
    DB::table('media')->insert([
        'model_type' => TestCuratorOwner::class,
        'model_id' => $ownerWithoutMedia->getKey(),
        'uuid' => (string) Str::uuid(),
        'collection_name' => 'image',
        'name' => 'new-file',
        'file_name' => 'media/new-file.jpg',
        'mime_type' => 'image/jpeg',
        'disk' => 'public',
        'conversions_disk' => null,
        'size' => 8000,
        'manipulations' => '[]',
        'custom_properties' => '[]',
        'generated_conversions' => '[]',
        'responsive_images' => '[]',
        'order_column' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->artisan('capell:media-migrate-to-curator')->assertSuccessful();

    // Pre-existing owner image_id must not be overwritten.
    expect($ownerWithMedia->fresh()->image_id)->toBe($existingCuratorId);

    // New owner should now have a non-null image_id.
    expect($ownerWithoutMedia->fresh()->image_id)->not->toBeNull();
});
