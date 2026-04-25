<?php

declare(strict_types=1);

use Capell\Navigation\Enums\NavigationCacheEnum;
use Capell\Navigation\Support\NavigationNamesResolver;

test('resolves navigation names for site and languages', function (): void {
    // Insert Type
    $typeId = $this->connection()->table('types')->insertGetId([
        'key' => 'navigation',
        'name' => 'Navigation',
        'description' => 'Navigation type',
    ]);

    // Insert Navigations
    $nav1Id = $this->connection()->table('navigations')->insertGetId([
        'site_id' => 1,
        'language_id' => 1,
        'type_id' => $typeId,
        'key' => 'main-menu',
        'name' => 'Main Menu',
        'items' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $nav2Id = $this->connection()->table('navigations')->insertGetId([
        'site_id' => 1,
        'language_id' => 2,
        'type_id' => $typeId,
        'key' => 'footer-menu',
        'name' => 'Footer Menu',
        'items' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $cache = app('cache');
    $resolver = new NavigationNamesResolver($cache);
    $result = $resolver->resolve(1, [1, 2]);

    $this->assertArrayHasKey($nav1Id, $result);
    $this->assertArrayHasKey($nav2Id, $result);
    $this->assertEquals('Main Menu', $result[$nav1Id]);
    $this->assertEquals('Footer Menu', $result[$nav2Id]);
});

test('caches result with correct key', function (): void {
    // Insert Type
    $typeId = $this->connection()->table('types')->insertGetId([
        'key' => 'navigation',
        'name' => 'Navigation',
        'description' => 'Navigation type',
    ]);

    $this->connection()->table('navigations')->insert([
        'site_id' => 1,
        'language_id' => 1,
        'type_id' => $typeId,
        'key' => 'cache-test',
        'name' => 'Cache Test',
        'items' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $cache = app('cache');
    $resolver = new NavigationNamesResolver($cache);
    $cacheKey = NavigationCacheEnum::navigationNamesKey(1, [1]);

    $resolver->resolve(1, [1]);

    $this->assertNotNull($cache->get($cacheKey));
});

test('includes navigations with null site id', function (): void {
    // Insert Type
    $typeId = $this->connection()->table('types')->insertGetId([
        'key' => 'navigation',
        'name' => 'Navigation',
        'description' => 'Navigation type',
    ]);

    $this->connection()->table('navigations')->insert([
        'site_id' => null,
        'language_id' => 1,
        'type_id' => $typeId,
        'key' => 'global',
        'name' => 'Global Menu',
        'items' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->connection()->table('navigations')->insert([
        'site_id' => 1,
        'language_id' => 1,
        'type_id' => $typeId,
        'key' => 'site',
        'name' => 'Site Menu',
        'items' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $cache = app('cache');
    $resolver = new NavigationNamesResolver($cache);
    $result = $resolver->resolve(1, [1]);

    $this->assertCount(2, $result);
});

test('handles string site id', function (): void {
    // Insert Type
    $typeId = $this->connection()->table('types')->insertGetId([
        'key' => 'navigation',
        'name' => 'Navigation',
        'description' => 'Navigation type',
    ]);

    $this->connection()->table('navigations')->insert([
        'site_id' => 1,
        'language_id' => 1,
        'type_id' => $typeId,
        'key' => 'string-test',
        'name' => 'Menu',
        'items' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $cache = app('cache');
    $resolver = new NavigationNamesResolver($cache);
    $result = $resolver->resolve('1', [1]);

    $this->assertCount(1, $result);
});

test('returns id name mapping', function (): void {
    // Insert Type
    $typeId = $this->connection()->table('types')->insertGetId([
        'key' => 'navigation',
        'name' => 'Navigation',
        'description' => 'Navigation type',
    ]);

    $navId = $this->connection()->table('navigations')->insertGetId([
        'site_id' => 1,
        'language_id' => 1,
        'type_id' => $typeId,
        'key' => 'test',
        'name' => 'Test Menu',
        'items' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $cache = app('cache');
    $resolver = new NavigationNamesResolver($cache);
    $result = $resolver->resolve(1, [1]);

    $this->assertIsArray($result);
    $this->assertArrayHasKey($navId, $result);
    $this->assertEquals('Test Menu', $result[$navId]);
});
