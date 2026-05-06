<?php

declare(strict_types=1);

use Capell\Admin\Contracts\DashboardSettingsContributor;
use Capell\Insights\Filament\Settings\Contributors\InsightsDashboardSettingsContributor;
use Capell\Insights\Filament\Widgets\InsightsOverviewStatsWidget;
use Capell\Insights\Filament\Widgets\LiveInsightsStatsWidget;
use Capell\Insights\Filament\Widgets\PopularPagesWidget;
use Capell\Insights\Filament\Widgets\RecentJourneysWidget;
use Capell\Insights\Filament\Widgets\TopActionsWidget;
use Capell\Insights\Filament\Widgets\TrendingPagesWidget;
use Livewire\Livewire;

it('exposes insights dashboard settings keys with translated labels', function (): void {
    $entries = (new InsightsDashboardSettingsContributor)->settingsKeys();

    expect(collect($entries)->pluck('key')->all())->toBe([
        'insights_overview',
        'insights_popular_pages',
        'insights_trending_pages',
        'insights_live_stats',
        'insights_recent_journeys',
        'insights_top_actions',
    ]);

    foreach ($entries as $entry) {
        expect($entry['label'])->toBeString()->not->toBe('')
            ->and(str_contains($entry['label'], 'capell-insights::'))->toBeFalse()
            ->and($entry['group'])->toBeString()->not->toBe('');
    }
});

it('has concrete translations for insights widget labels', function (): void {
    $translationKeys = [
        'insights_overview',
        'popular_pages',
        'trending_pages',
        'live_statistics',
        'recent_journeys',
        'top_actions',
        'metric',
        'value',
        'path',
        'page_views',
        'unique_visits',
        'clicks',
        'current_page_views',
        'previous_page_views',
        'change',
        'change_percentage',
        'visit',
        'steps',
        'last_path',
        'action',
        'events',
        'live_page_views',
        'live_active_visits',
        'live_top_page',
    ];

    foreach ($translationKeys as $translationKey) {
        $translated = __('capell-insights::widgets.' . $translationKey);

        expect($translated)->toBeString()->not->toBe('capell-insights::widgets.' . $translationKey);
    }
});

it('registers the insights dashboard settings contributor', function (): void {
    $contributors = collect(app()->tagged(DashboardSettingsContributor::TAG))
        ->map(fn (DashboardSettingsContributor $contributor): string => $contributor::class);

    expect($contributors)->toContain(InsightsDashboardSettingsContributor::class);
});

it('renders insights dashboard widgets', function (string $widgetClass): void {
    Livewire::test($widgetClass)->assertOk();
})->with([
    InsightsOverviewStatsWidget::class,
    PopularPagesWidget::class,
    TrendingPagesWidget::class,
    LiveInsightsStatsWidget::class,
    RecentJourneysWidget::class,
    TopActionsWidget::class,
]);

it('renders trending pages with previous count column', function (): void {
    Livewire::test(TrendingPagesWidget::class)
        ->assertOk()
        ->assertSee(__('capell-insights::widgets.previous_page_views'));
});
