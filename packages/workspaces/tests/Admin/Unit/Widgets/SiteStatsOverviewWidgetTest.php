<?php

declare(strict_types=1);

use Capell\Admin\Data\Dashboard\SiteStatsData;
use Capell\Admin\Filament\Widgets\Dashboard\SiteStatsOverviewWidget;
use Capell\Admin\Settings\AdminSettings;
use Capell\Core\Models\PageView;
use Capell\Tests\Fixtures\Models\User;
use Capell\Workspaces\Actions\Dashboard\BuildSiteStatsAction;

it('returns a SiteStatsData instance', function (): void {
    $data = BuildSiteStatsAction::run('last_30_days');
    expect($data)->toBeInstanceOf(SiteStatsData::class);
});

it('returns non-negative counts', function (): void {
    $data = BuildSiteStatsAction::run('last_30_days');
    expect($data->totalViews)->toBeGreaterThanOrEqual(0)
        ->and($data->totalVisitors)->toBeGreaterThanOrEqual(0)
        ->and($data->workQueueCount)->toBeGreaterThanOrEqual(0)
        ->and($data->publishedCount)->toBeGreaterThanOrEqual(0);
});

it('returns 7-point sparklines', function (): void {
    $data = BuildSiteStatsAction::run('last_30_days');
    expect($data->sparklineViews)->toHaveCount(7)
        ->and($data->sparklineVisitors)->toHaveCount(7)
        ->and($data->sparklinePublished)->toHaveCount(7);
});

it('counts access log views for the period', function (): void {
    PageView::factory()->create([
        'visits' => 5,
        'viewed_at' => now()->subDays(10),
    ]);

    $data = BuildSiteStatsAction::run('last_30_days');

    expect($data->totalViews)->toBeGreaterThanOrEqual(5);
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
