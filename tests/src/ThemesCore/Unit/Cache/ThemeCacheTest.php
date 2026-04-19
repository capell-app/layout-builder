<?php

declare(strict_types=1);

use Capell\Themes\Core\Cache\ThemeCache;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;

test('remember returns the computed value on first call', function (): void {
    $cache = new ThemeCache([ThemeCache::TAG_THEME], new Repository(new ArrayStore));

    $value = $cache->remember('nav', 60, fn (): array => ['home', 'about']);

    expect($value)->toBe(['home', 'about']);
});

test('remember serves from cache on subsequent calls', function (): void {
    $cache = new ThemeCache([ThemeCache::TAG_THEME], new Repository(new ArrayStore));

    $calls = 0;
    $first = $cache->remember('nav', 60, function () use (&$calls): array {
        $calls++;

        return ['home', 'about'];
    });
    $second = $cache->remember('nav', 60, function () use (&$calls): array {
        $calls++;

        return ['different'];
    });

    expect($first)->toBe(['home', 'about']);
    expect($second)->toBe(['home', 'about']);
    expect($calls)->toBe(1);
});

test('forget removes the keyed entry', function (): void {
    $cache = new ThemeCache([ThemeCache::TAG_THEME], new Repository(new ArrayStore));

    $calls = 0;
    $cache->remember('x', 60, function () use (&$calls): string {
        $calls++;

        return 'value';
    });

    $cache->forget('x');

    $cache->remember('x', 60, function () use (&$calls): string {
        $calls++;

        return 'value';
    });

    expect($calls)->toBe(2);
});

test('flush clears all cached entries', function (): void {
    $cache = new ThemeCache([ThemeCache::TAG_ASSETS], new Repository(new ArrayStore));

    $calls = 0;
    $cache->remember('a', 60, function () use (&$calls): int {
        $calls++;

        return 1;
    });

    $cache->flush();

    $cache->remember('a', 60, function () use (&$calls): int {
        $calls++;

        return 1;
    });

    expect($calls)->toBe(2);
});
