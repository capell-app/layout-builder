<?php

declare(strict_types=1);

use Capell\Core\Enums\CacheEnum;
use Capell\Navigation\Models\Navigation;
use Illuminate\Support\Facades\Cache;

it('flushes navigation names cache on save', function (): void {
    $navigation = Navigation::factory()->create();

    Cache::driver('array')->forever(CacheEnum::NavigationNames->value . '-null-' . hash('sha256', json_encode([])), true);

    $navigation->name = 'Updated';
    $navigation->save();

    $registry = Cache::driver('array')->get('capell-core-cache-keys', []);
    expect($registry)->not()->toContain(CacheEnum::NavigationNames->value . '-null-' . hash('sha256', json_encode([])));
});

it('flushes navigation names cache on delete', function (): void {
    $navigation = Navigation::factory()->create();

    $cacheKey = CacheEnum::NavigationNames->value . '-null-' . hash('sha256', json_encode([]));
    Cache::driver('array')->forever($cacheKey, true);

    $navigation->delete();

    $registry = Cache::driver('array')->get('capell-core-cache-keys', []);
    expect($registry)->not()->toContain($cacheKey);
});
