<?php

declare(strict_types=1);

use Capell\Themes\Core\Cache\ThemeCache;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;

test('remember stores and returns values', function (): void {
    $repo = new Repository(new ArrayStore());
    $cache = new ThemeCache([ThemeCache::TAG_THEME], $repo);

    $value = $cache->remember('nav', 60, fn () => ['home', 'about']);
    expect($value)->toBe(['home', 'about']);

    // Stored under the prefixed key.
    expect($repo->get('capell:nav'))->toBe(['home', 'about']);
});

test('forget removes the keyed entry', function (): void {
    $repo = new Repository(new ArrayStore());
    $cache = new ThemeCache([ThemeCache::TAG_THEME], $repo);

    $cache->remember('x', 60, fn () => 'value');
    expect($cache->forget('x'))->toBeTrue();
    expect($repo->get('capell:x'))->toBeNull();
});

test('flush on non-taggable store is a no-op', function (): void {
    $repo = new Repository(new ArrayStore());
    $cache = new ThemeCache([ThemeCache::TAG_ASSETS], $repo);

    $cache->remember('a', 60, fn () => 1);

    // Should not throw even though ArrayStore has no tag support.
    $cache->forgetThemeAssets();
    $cache->flush();

    expect($repo->get('capell:a'))->toBe(1);
});
