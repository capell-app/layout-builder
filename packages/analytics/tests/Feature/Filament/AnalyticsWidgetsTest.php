<?php

declare(strict_types=1);

use Capell\Admin\Contracts\DashboardSettingsContributor;
use Capell\Analytics\Filament\Settings\Contributors\AnalyticsDashboardSettingsContributor;
use Capell\Analytics\Filament\Widgets\AnalyticsOverviewStatsWidget;
use Capell\Analytics\Filament\Widgets\PopularPagesWidget;
use Capell\Analytics\Filament\Widgets\RecentJourneysWidget;
use Capell\Analytics\Filament\Widgets\TopActionsWidget;
use Capell\Analytics\Filament\Widgets\TrendingPagesWidget;
use Livewire\Livewire;

it('exposes analytics dashboard settings keys with translated labels', function (): void {
    $entries = (new AnalyticsDashboardSettingsContributor)->settingsKeys();

    expect(collect($entries)->pluck('key')->all())->toBe([
        'analytics_overview',
        'analytics_popular_pages',
        'analytics_trending_pages',
        'analytics_recent_journeys',
        'analytics_top_actions',
    ]);

    foreach ($entries as $entry) {
        expect($entry['label'])->toBeString()->not->toBe('')
            ->and(str_contains($entry['label'], 'capell-analytics::'))->toBeFalse()
            ->and($entry['group'])->toBeString()->not->toBe('');
    }
});

it('has concrete translations for analytics widget labels', function (): void {
    $translationKeys = [
        'analytics_overview',
        'popular_pages',
        'trending_pages',
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
    ];

    foreach ($translationKeys as $translationKey) {
        $translated = __('capell-analytics::widgets.' . $translationKey);

        expect($translated)->toBeString()->not->toBe('capell-analytics::widgets.' . $translationKey);
    }
});

it('registers the analytics dashboard settings contributor', function (): void {
    $contributors = collect(app()->tagged(DashboardSettingsContributor::TAG))
        ->map(fn (DashboardSettingsContributor $contributor): string => $contributor::class);

    expect($contributors)->toContain(AnalyticsDashboardSettingsContributor::class);
});

it('renders analytics dashboard widgets', function (string $widgetClass): void {
    Livewire::test($widgetClass)->assertOk();
})->with([
    AnalyticsOverviewStatsWidget::class,
    PopularPagesWidget::class,
    TrendingPagesWidget::class,
    RecentJourneysWidget::class,
    TopActionsWidget::class,
]);

it('renders trending pages with previous count column', function (): void {
    Livewire::test(TrendingPagesWidget::class)
        ->assertOk()
        ->assertSee(__('capell-analytics::widgets.previous_page_views'));
});
