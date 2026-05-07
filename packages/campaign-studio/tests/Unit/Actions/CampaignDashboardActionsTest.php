<?php

declare(strict_types=1);

use Capell\CampaignStudio\Actions\BuildCampaignOverviewStatsAction;
use Capell\CampaignStudio\Actions\BuildTopCampaignStudioQueryAction;
use Capell\CampaignStudio\Actions\BuildTopLandingPagesQueryAction;
use Capell\CampaignStudio\Actions\ResolveCampaignFromUrlAction;
use Capell\CampaignStudio\Enums\CampaignStatus;
use Capell\CampaignStudio\Models\CampaignConversion;
use Capell\CampaignStudio\Models\CampaignConversionGoal;
use Capell\CampaignStudio\Models\CampaignGroup;
use Capell\CampaignStudio\Models\CampaignLandingPage;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageUrl;
use Capell\Insights\Models\InsightsVisit;
use Carbon\CarbonImmutable;

it('resolves active campaigns from utm campaign values before falling back to landing page urls', function (): void {
    $utmCampaign = CampaignGroup::factory()->create([
        'name' => 'Spring Launch',
        'slug' => 'spring-launch-slug',
        'utm_campaign' => 'spring-launch',
    ]);
    CampaignGroup::factory()->create([
        'slug' => 'retired-campaign',
        'status' => CampaignStatus::Paused,
    ]);
    $fallbackCampaign = CampaignGroup::factory()->create([
        'name' => 'Fallback Campaign',
    ]);
    $page = Page::factory()->create();

    PageUrl::factory()
        ->page($page)
        ->create([
            'site_id' => $page->site_id,
            'url' => '/campaign/fallback',
        ]);

    CampaignLandingPage::factory()
        ->for($fallbackCampaign, 'campaignGroup')
        ->create([
            'page_id' => $page->getKey(),
        ]);

    expect(ResolveCampaignFromUrlAction::run('https://capell.test/anything?utm_campaign=spring-launch'))
        ->is($utmCampaign)->toBeTrue()
        ->and(ResolveCampaignFromUrlAction::run('https://capell.test/anything?utm_campaign=retired-campaign'))->toBeNull()
        ->and(ResolveCampaignFromUrlAction::run('https://capell.test/campaign/fallback')->is($fallbackCampaign))->toBeTrue();
});

it('builds campaign overview stats from active campaigns, conversions, and campaign visits in the requested window', function (): void {
    $startsAt = CarbonImmutable::parse('2026-04-01 00:00:00');
    $endsAt = CarbonImmutable::parse('2026-04-30 23:59:59');
    $campaign = CampaignGroup::factory()->create([
        'utm_campaign' => 'spring-launch',
    ]);
    CampaignGroup::factory()->create([
        'status' => CampaignStatus::Paused,
        'utm_campaign' => 'paused-launch',
    ]);
    $goal = CampaignConversionGoal::factory()
        ->for($campaign, 'campaignGroup')
        ->create();

    CampaignConversion::factory()
        ->for($campaign, 'campaignGroup')
        ->for($goal, 'goal')
        ->create(['converted_at' => CarbonImmutable::parse('2026-04-15 12:00:00')]);
    CampaignConversion::factory()
        ->for($campaign, 'campaignGroup')
        ->for($goal, 'goal')
        ->create(['converted_at' => CarbonImmutable::parse('2026-05-02 12:00:00')]);

    InsightsVisit::factory()->create([
        'utm_campaign' => 'spring-launch',
        'last_seen_at' => CarbonImmutable::parse('2026-04-10 09:00:00'),
    ]);
    InsightsVisit::factory()->create([
        'utm_campaign' => 'spring-launch',
        'last_seen_at' => CarbonImmutable::parse('2026-04-11 09:00:00'),
    ]);
    InsightsVisit::factory()->create([
        'utm_campaign' => 'spring-launch',
        'last_seen_at' => CarbonImmutable::parse('2026-05-01 09:00:00'),
    ]);

    $stats = BuildCampaignOverviewStatsAction::run($startsAt, $endsAt);

    expect($stats)->toBe([
        'active_campaign-studio' => 1,
        'conversions' => 1,
        'conversion_rate' => 50.0,
    ]);
});

it('ranks top campaigns by in-window conversions and calculates visit conversion rates', function (): void {
    $startsAt = CarbonImmutable::parse('2026-04-01 00:00:00');
    $endsAt = CarbonImmutable::parse('2026-04-30 23:59:59');
    $firstCampaign = CampaignGroup::factory()->create([
        'name' => 'Alpha Campaign',
        'utm_campaign' => 'alpha-campaign',
    ]);
    $secondCampaign = CampaignGroup::factory()->create([
        'name' => 'Beta Campaign',
        'utm_campaign' => 'beta-campaign',
    ]);
    $firstGoal = CampaignConversionGoal::factory()->for($firstCampaign, 'campaignGroup')->create();
    $secondGoal = CampaignConversionGoal::factory()->for($secondCampaign, 'campaignGroup')->create();

    CampaignConversion::factory()
        ->count(2)
        ->for($firstCampaign, 'campaignGroup')
        ->for($firstGoal, 'goal')
        ->create(['converted_at' => CarbonImmutable::parse('2026-04-08 12:00:00')]);
    CampaignConversion::factory()
        ->for($secondCampaign, 'campaignGroup')
        ->for($secondGoal, 'goal')
        ->create(['converted_at' => CarbonImmutable::parse('2026-04-08 12:00:00')]);
    CampaignConversion::factory()
        ->for($firstCampaign, 'campaignGroup')
        ->for($firstGoal, 'goal')
        ->create(['converted_at' => CarbonImmutable::parse('2026-05-08 12:00:00')]);

    InsightsVisit::factory()->count(4)->create([
        'utm_campaign' => 'alpha-campaign',
        'last_seen_at' => CarbonImmutable::parse('2026-04-09 10:00:00'),
    ]);
    InsightsVisit::factory()->count(2)->create([
        'utm_campaign' => 'beta-campaign',
        'last_seen_at' => CarbonImmutable::parse('2026-04-09 10:00:00'),
    ]);

    $rows = BuildTopCampaignStudioQueryAction::run(limit: 2, startsAt: $startsAt, endsAt: $endsAt);

    expect($rows)->toHaveCount(2)
        ->and($rows[0]->campaignGroupId)->toBe($firstCampaign->getKey())
        ->and($rows[0]->conversions)->toBe(2)
        ->and($rows[0]->visits)->toBe(4)
        ->and($rows[0]->conversionRate)->toBe(50.0)
        ->and($rows[1]->campaignGroupId)->toBe($secondCampaign->getKey())
        ->and($rows[1]->conversions)->toBe(1);
});

it('ranks landing pages by in-window conversions with their campaign names', function (): void {
    $startsAt = CarbonImmutable::parse('2026-04-01 00:00:00');
    $endsAt = CarbonImmutable::parse('2026-04-30 23:59:59');
    $campaign = CampaignGroup::factory()->create([
        'name' => 'Spring Launch',
    ]);
    $goal = CampaignConversionGoal::factory()->for($campaign, 'campaignGroup')->create();
    $firstLandingPage = CampaignLandingPage::factory()
        ->for($campaign, 'campaignGroup')
        ->create(['headline' => 'Signup Page']);
    $secondLandingPage = CampaignLandingPage::factory()
        ->for($campaign, 'campaignGroup')
        ->create(['headline' => 'Demo Page']);

    CampaignConversion::factory()
        ->count(2)
        ->for($campaign, 'campaignGroup')
        ->for($goal, 'goal')
        ->for($firstLandingPage, 'landingPage')
        ->create(['converted_at' => CarbonImmutable::parse('2026-04-12 12:00:00')]);
    CampaignConversion::factory()
        ->for($campaign, 'campaignGroup')
        ->for($goal, 'goal')
        ->for($secondLandingPage, 'landingPage')
        ->create(['converted_at' => CarbonImmutable::parse('2026-04-12 12:00:00')]);
    CampaignConversion::factory()
        ->for($campaign, 'campaignGroup')
        ->for($goal, 'goal')
        ->for($firstLandingPage, 'landingPage')
        ->create(['converted_at' => CarbonImmutable::parse('2026-05-12 12:00:00')]);

    $rows = BuildTopLandingPagesQueryAction::run(limit: 2, startsAt: $startsAt, endsAt: $endsAt);

    expect($rows)->toHaveCount(2)
        ->and($rows[0]->landingPageId)->toBe($firstLandingPage->getKey())
        ->and($rows[0]->landingPageName)->toBe('Signup Page')
        ->and($rows[0]->campaignName)->toBe('Spring Launch')
        ->and($rows[0]->conversions)->toBe(2)
        ->and($rows[1]->landingPageId)->toBe($secondLandingPage->getKey())
        ->and($rows[1]->conversions)->toBe(1);
});
