<?php

declare(strict_types=1);

use Capell\Campaigns\Providers\CampaignsServiceProvider;
use Capell\Core\Facades\CapellCore;

it('registers the campaigns package metadata', function (): void {
    $package = CapellCore::getPackage(CampaignsServiceProvider::$packageName);

    expect($package->name)->toBe(CampaignsServiceProvider::$packageName);
});

it('loads the campaigns config', function (): void {
    expect(config('capell-campaigns.tables.groups'))->toBe('campaign_groups')
        ->and(config('capell-campaigns.conversion_cookie'))->toBe('capell_campaign_visit');
});
