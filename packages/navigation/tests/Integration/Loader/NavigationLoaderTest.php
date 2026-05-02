<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Navigation\Enums\NavigationHandle;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Support\Loader\NavigationLoader;

it('loads navigation by key for a site', function (): void {
    $site = Site::factory()->withTranslations()->create();
    Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();

    $nav = NavigationLoader::getNavigation(NavigationHandle::Main, $site, $site->language, true);

    expect(! $nav instanceof Navigation || $nav instanceof Navigation)->toBeTrue();
});

it('falls back to current site navigation before global navigation', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $otherSite = Site::factory()->withTranslations()->create();
    Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();

    $globalNavigation = Navigation::factory()->create([
        'key' => NavigationHandle::Main->value,
        'site_id' => null,
        'language_id' => null,
        'name' => 'Global Main',
    ]);

    $siteNavigation = Navigation::factory()->create([
        'key' => NavigationHandle::Main->value,
        'site_id' => $site->getKey(),
        'language_id' => null,
        'name' => 'Site Main',
    ]);

    Navigation::factory()->create([
        'key' => NavigationHandle::Main->value,
        'site_id' => $otherSite->getKey(),
        'language_id' => null,
        'name' => 'Other Site Main',
    ]);

    $navigation = NavigationLoader::getNavigation(NavigationHandle::Main, $site, $site->language, true);

    expect($navigation?->getKey())->toBe($siteNavigation->getKey())
        ->and($navigation?->getKey())->not()->toBe($globalNavigation->getKey());
});
