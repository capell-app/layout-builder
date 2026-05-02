<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Navigation\Contracts\NavigationNamesResolver;
use Capell\Navigation\Models\Navigation;

it('is bound in the container when the navigation package is loaded', function (): void {
    expect(app()->bound(NavigationNamesResolver::class))->toBeTrue();
});

it('resolves navigation names for a given site and language', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->create();

    $navigation = Navigation::factory()->create([
        'name' => 'Main Navigation',
        'site_id' => $site->id,
        'language_id' => $language->id,
    ]);

    $names = resolve(NavigationNamesResolver::class)->resolve($site->id, [$language->id]);

    expect($names)->toHaveKey($navigation->id)
        ->and($names[$navigation->id])->toBe('Main Navigation');
});

it('includes navigations without a site when resolving by site', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->create();

    $globalNavigation = Navigation::factory()->create([
        'name' => 'Global',
        'site_id' => null,
        'language_id' => null,
    ]);

    $names = resolve(NavigationNamesResolver::class)->resolve($site->id, [$language->id]);

    expect($names)->toHaveKey($globalNavigation->id);
});

it('returns empty array when no navigations match', function (): void {
    $names = resolve(NavigationNamesResolver::class)->resolve(99999, [99999]);

    expect($names)->toBeEmpty();
});
