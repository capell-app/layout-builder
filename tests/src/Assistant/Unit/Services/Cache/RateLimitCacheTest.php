<?php

declare(strict_types=1);

use Capell\SeoTools\Support\Cache\RateLimitCache;

it('returns TTL and key composition for rate limiting', function (): void {
    $cache = new RateLimitCache('array');

    $key = $cache->keyFor('user-1');
    $ttl = $cache->ttl();

    expect($key)->toContain('user-1')
        ->and($ttl)->toBeInt()->toBeGreaterThan(0);
});
