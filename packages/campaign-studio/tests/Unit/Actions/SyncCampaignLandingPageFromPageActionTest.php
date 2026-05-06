<?php

declare(strict_types=1);

use Capell\CampaignStudio\Actions\SyncCampaignLandingPageFromPageAction;
use Capell\CampaignStudio\Models\CampaignConversionGoal;
use Capell\CampaignStudio\Models\CampaignGroup;
use Capell\CampaignStudio\Models\CampaignLandingPage;
use Capell\Core\Models\Page;

it('syncs campaign page meta to a landing page record', function (): void {
    $campaignGroup = CampaignGroup::factory()->create();
    $goal = CampaignConversionGoal::factory()
        ->for($campaignGroup, 'campaignGroup')
        ->create();
    $page = Page::factory()->create([
        'name' => 'Spring signup',
        'meta' => [
            'campaign' => [
                'campaign_group_id' => $campaignGroup->getKey(),
                'is_landing_page' => true,
                'primary_goal_id' => $goal->getKey(),
                'utm_content' => 'hero',
                'utm_term' => 'spring',
            ],
        ],
    ]);

    $landingPage = SyncCampaignLandingPageFromPageAction::run($page);

    expect($landingPage)->toBeInstanceOf(CampaignLandingPage::class)
        ->and($landingPage->campaign_group_id)->toBe($campaignGroup->getKey())
        ->and($landingPage->page_id)->toBe($page->getKey())
        ->and($landingPage->primary_goal_id)->toBe($goal->getKey())
        ->and($landingPage->utm_content)->toBe('hero')
        ->and($landingPage->utm_term)->toBe('spring');
});

it('removes the landing page record when the page is no longer a campaign landing page', function (): void {
    $campaignGroup = CampaignGroup::factory()->create();
    $page = Page::factory()->create([
        'meta' => [
            'campaign' => [
                'campaign_group_id' => $campaignGroup->getKey(),
                'is_landing_page' => true,
            ],
        ],
    ]);

    SyncCampaignLandingPageFromPageAction::run($page);

    $page->forceFill([
        'meta' => [
            'campaign' => [
                'campaign_group_id' => $campaignGroup->getKey(),
                'is_landing_page' => false,
            ],
        ],
    ])->save();

    SyncCampaignLandingPageFromPageAction::run($page);

    expect(CampaignLandingPage::query()->where('page_id', $page->getKey())->exists())->toBeFalse();
});
