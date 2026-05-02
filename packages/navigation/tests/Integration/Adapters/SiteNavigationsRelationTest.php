<?php

declare(strict_types=1);

use Capell\Core\Models\Site;
use Capell\Navigation\Models\Navigation;

it('Site has navigations relation registered by the navigation package', function (): void {
    $site = Site::factory()->create();

    Navigation::factory()->count(2)->create(['site_id' => $site->id]);

    $navigations = $site->navigations;

    expect($navigations)->toHaveCount(2)
        ->and($navigations->first())->toBeInstanceOf(Navigation::class);
});

it('navigations relation only returns navigations for the owning site', function (): void {
    $siteA = Site::factory()->create();
    $siteB = Site::factory()->create();

    Navigation::factory()->create(['site_id' => $siteA->id]);
    Navigation::factory()->count(3)->create(['site_id' => $siteB->id]);

    expect($siteA->navigations)->toHaveCount(1)
        ->and($siteB->navigations)->toHaveCount(3);
});
