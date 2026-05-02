<?php

declare(strict_types=1);

use Capell\ThemeStudio\Core\Assets\ThemeTokenStore;
use Capell\ThemeStudio\Core\Data\BrandProfileData;

it('stores token css under isolated theme preset brand keys', function (): void {
    $brand = new BrandProfileData(
        primaryColor: '#123456',
        accentColor: '#abcdef',
    );

    $store = new ThemeTokenStore(storage_path('framework/testing/theme-studio'));

    $first = $store->put('corporate', 'boardroom', $brand);
    $second = $store->put('saas', 'launchpad', $brand);

    expect($first)->not->toBe($second)
        ->and(file_exists($first))->toBeTrue()
        ->and(file_get_contents($first))->toContain('--theme-primary: #123456;');
});
