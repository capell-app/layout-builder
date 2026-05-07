<?php

declare(strict_types=1);

use Capell\ThemeStudio\Core\Assets\ThemeTokenStore;
use Capell\ThemeStudio\Core\Data\BrandProfileData;

it('stores token css under isolated theme preset and brand keys', function (): void {
    $brand = new BrandProfileData(
        primaryColor: '#123456',
        accentColor: '#abcdef',
    );

    $store = new ThemeTokenStore(storage_path('framework/testing/theme-tokens'));

    $firstPath = $store->put('corporate', 'boardroom', $brand);
    $secondPath = $store->put('saas', 'launchpad', $brand);

    expect($firstPath)->not->toBe($secondPath)
        ->and(file_exists($firstPath))->toBeTrue()
        ->and(file_get_contents($firstPath))->toContain('--theme-primary: #123456;');
});

it('does not rewrite token css when the generated content is unchanged', function (): void {
    $brand = new BrandProfileData(
        primaryColor: '#123456',
        accentColor: '#abcdef',
    );

    $store = new ThemeTokenStore(storage_path('framework/testing/theme-tokens-idempotent'));

    $path = $store->put('corporate', 'boardroom', $brand);
    touch($path, time() - 60);
    clearstatcache(true, $path);
    $firstModifiedAt = filemtime($path);

    sleep(1);

    $secondPath = $store->put('corporate', 'boardroom', $brand);

    expect($secondPath)->toBe($path)
        ->and(filemtime($path))->toBe($firstModifiedAt);
});
