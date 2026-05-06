<?php

declare(strict_types=1);

use Capell\Core\Contracts\Media\MediaContract;
use Capell\MediaLibrary\Tests\Fixtures\TestCuratorOwner;
use Illuminate\Http\UploadedFile;

test('attach_from_upload_then_fetch_first_url returns non-empty string', function (): void {
    $owner = TestCuratorOwner::query()->create(['name' => 'Test Owner']);

    $owner->addMediaFromUploadedFile(
        UploadedFile::fake()->image('hero.jpg', 800, 600),
        'image',
    );

    $url = $owner->getFirstMediaUrl('image');

    expect($url)->toBeString()->not->toBeEmpty();
});

test('get_media returns collection with one MediaContract item after upload', function (): void {
    $owner = TestCuratorOwner::query()->create(['name' => 'Gallery Owner']);

    $owner->addMediaFromUploadedFile(
        UploadedFile::fake()->image('gallery.jpg'),
        'image',
    );

    $mediaCollection = $owner->getMedia('image');

    expect($mediaCollection)->toHaveCount(1);
    expect($mediaCollection->first())->toBeInstanceOf(MediaContract::class);
});

test('clear_media_collection nulls fk column and empties url', function (): void {
    $owner = TestCuratorOwner::query()->create(['name' => 'Clearable Owner']);
    $owner->addMediaFromUploadedFile(UploadedFile::fake()->image('x.jpg'), 'image');

    $owner->clearMediaCollection('image');

    expect($owner->getFirstMediaUrl('image'))->toBe('');
    expect($owner->fresh()->image_id)->toBeNull();
});

test('get_first_media_url always returns a string', function (): void {
    $owner = TestCuratorOwner::query()->create(['name' => 'String Check Owner']);
    $owner->addMediaFromUploadedFile(UploadedFile::fake()->image('string-check.jpg'), 'image');

    $url = $owner->getFirstMediaUrl('image');

    expect($url)->toBeString();
});

test('fk column is populated with curator row id after attach', function (): void {
    $owner = TestCuratorOwner::query()->create(['name' => 'FK Owner']);

    $media = $owner->addMediaFromUploadedFile(
        UploadedFile::fake()->image('fk-check.jpg'),
        'image',
    );

    $owner->refresh();

    expect($owner->image_id)->toBe($media->getKey());
});

test('get_first_media returns an instance of MediaContract', function (): void {
    $owner = TestCuratorOwner::query()->create(['name' => 'Contract Owner']);
    $owner->addMediaFromUploadedFile(UploadedFile::fake()->image('contract.jpg'), 'image');

    $media = $owner->getFirstMedia('image');

    expect($media)->toBeInstanceOf(MediaContract::class);
});
