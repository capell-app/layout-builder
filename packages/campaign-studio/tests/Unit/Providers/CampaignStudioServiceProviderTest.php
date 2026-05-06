<?php

declare(strict_types=1);

use Capell\CampaignStudio\Providers\CampaignStudioServiceProvider;
use Capell\Core\Facades\CapellCore;

it('registers the campaign-studio package metadata', function (): void {
    $package = CapellCore::getPackage(CampaignStudioServiceProvider::$packageName);

    expect($package->name)->toBe(CampaignStudioServiceProvider::$packageName);
});

it('loads the campaign-studio config', function (): void {
    expect(config('capell-campaign-studio.tables.groups'))->toBe('campaign_groups')
        ->and(config('capell-campaign-studio.conversion_cookie'))->toBe('capell_campaign_visit');
});
