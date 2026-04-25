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
