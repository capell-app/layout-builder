<?php

declare(strict_types=1);

use Capell\Analytics\Providers\AnalyticsServiceProvider;
use Capell\Core\Facades\CapellCore;
use Illuminate\Support\Facades\Route;

it('registers the analytics package metadata', function (): void {
    $package = CapellCore::getPackage(AnalyticsServiceProvider::$packageName);

    expect($package->name)->toBe(AnalyticsServiceProvider::$packageName);
});

it('loads the analytics config', function (): void {
    expect(config('capell-analytics.enabled'))->toBeTrue()
        ->and(config('capell-analytics.route_prefix'))->toBe('capell/analytics');
});

it('registers analytics routes', function (): void {
    expect(Route::has('capell-analytics.events'))->toBeTrue()
        ->and(Route::has('capell-analytics.consent'))->toBeTrue();
});
