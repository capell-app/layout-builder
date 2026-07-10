<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Data\WidgetSnapshots\WidgetSnapshotLocatorData;
use Capell\LayoutBuilder\Support\WidgetSnapshots\WidgetSnapshotLocatorCodec;
use Illuminate\Encryption\Encrypter;

function snapshotLocatorData(string $purpose = WidgetSnapshotLocatorCodec::PURPOSE): WidgetSnapshotLocatorData
{
    return new WidgetSnapshotLocatorData(
        version: WidgetSnapshotLocatorCodec::VERSION,
        purpose: $purpose,
        snapshotId: 42,
        pageableType: 'page',
        pageableId: 7,
        targetInstanceId: 'target-uuid',
    );
}

it('round trips only the short versioned locator identity contract', function (): void {
    $codec = resolve(WidgetSnapshotLocatorCodec::class);
    $locator = $codec->encode(snapshotLocatorData());
    $decoded = $codec->decode($locator);

    expect(strlen($locator))->toBeLessThan(2048)
        ->and($locator)->not->toContain('content', 'widgetData')
        ->and($decoded?->toArray())->toBe(snapshotLocatorData()->toArray());
});

it('rejects locators encrypted by an old or unrelated key', function (): void {
    $oldCodec = new WidgetSnapshotLocatorCodec(new Encrypter(random_bytes(32), 'AES-256-CBC'));
    $currentCodec = new WidgetSnapshotLocatorCodec(new Encrypter(random_bytes(32), 'AES-256-CBC'));

    expect($currentCodec->decode($oldCodec->encode(snapshotLocatorData())))->toBeNull();
});

it('rejects the wrong purpose before generating a locator', function (): void {
    expect(fn (): string => resolve(WidgetSnapshotLocatorCodec::class)->encode(snapshotLocatorData('poll-vote')))
        ->toThrow(InvalidArgumentException::class);
});

it('rejects noncanonical and oversized encoded input without decryption', function (): void {
    $codec = resolve(WidgetSnapshotLocatorCodec::class);
    $locator = $codec->encode(snapshotLocatorData());

    expect($codec->decode($locator . 'x'))->toBeNull()
        ->and($codec->decode(str_repeat('a', 2049)))->toBeNull();
});
