<?php

declare(strict_types=1);

use Capell\SeoTools\Support\Cache\AIGenerationCache;

it('composes cache keys and TTLs correctly', function (): void {
    $cache = new AIGenerationCache('array', 60);

    $key = $cache->keyFor('page', 123);
    $ttl = $cache->ttl();

    expect($key)->toContain('page:123')
        ->and($ttl)->toBeInt()->toBeGreaterThan(0);
});
