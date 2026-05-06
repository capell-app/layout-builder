<?php

declare(strict_types=1);

use Capell\Admin\Contracts\Dashboard\SiteStatsDataProvider;
use Capell\Admin\Data\Dashboard\SiteStatsData;
use Capell\Admin\Filament\Widgets\Dashboard\SiteStatsOverviewWidget;
use Capell\Admin\Settings\AdminSettings;
use Capell\PublishingStudio\Actions\Dashboard\BuildSiteStatsAction;
use Capell\PublishingStudio\Support\Dashboard\WorkspaceSiteStatsDataProvider;
use Capell\Tests\Fixtures\Models\User;

it('returns a SiteStatsData instance', function (): void {
    $data = BuildSiteStatsAction::run('last_30_days');
    expect($data)->toBeInstanceOf(SiteStatsData::class);
});

it('binds workspace stats to the admin dashboard stats provider contract', function (): void {
    expect(resolve(SiteStatsDataProvider::class))
        ->toBeInstanceOf(WorkspaceSiteStatsDataProvider::class);
});

it('returns non-negative counts', function (): void {
    $data = BuildSiteStatsAction::run('last_30_days');
    expect($data->workQueueCount)->toBeGreaterThanOrEqual(0)
        ->and($data->publishedCount)->toBeGreaterThanOrEqual(0);
});

it('returns 7-point sparklines', function (): void {
    $data = BuildSiteStatsAction::run('last_30_days');
    expect($data->sparklinePublished)->toHaveCount(7);
});

it('accepts all valid period keys', function (string $period): void {
    $data = BuildSiteStatsAction::run($period);
    expect($data)->toBeInstanceOf(SiteStatsData::class);
})->with(['today', 'yesterday', 'last_7_days', 'this_month', 'last_30_days', 'this_year']);

it('is hidden when unauthenticated', function (): void {
    expect(SiteStatsOverviewWidget::canView())->toBeFalse();
});

it('is visible when authenticated', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    expect(SiteStatsOverviewWidget::canView())->toBeTrue();
});

it('is hidden when settings key is disabled', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $settings = resolve(AdminSettings::class);
    $settings->enabled_widgets = ['site_stats_overview' => false];
    $settings->save();

    expect(SiteStatsOverviewWidget::canView())->toBeFalse();
});
