<?php

declare(strict_types=1);

namespace Capell\Insights\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Capell\Insights\Filament\Widgets\InsightsOverviewStatsWidget;
use Capell\Insights\Filament\Widgets\LiveInsightsStatsWidget;
use Capell\Insights\Filament\Widgets\PopularPagesWidget;
use Capell\Insights\Filament\Widgets\RecentJourneysWidget;
use Capell\Insights\Filament\Widgets\TopActionsWidget;
use Capell\Insights\Filament\Widgets\TrendingPagesWidget;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Override;

final class InsightsPage extends Page
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::ChartBar;

    protected string $view = 'capell-insights::filament.pages.insights';

    protected static ?string $slug = 'insights';

    #[Override]
    public static function getNavigationLabel(): string
    {
        return __('capell-insights::widgets.insights');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return __('capell-admin::navigation.group_monitoring');
    }

    #[Override]
    public function getTitle(): string
    {
        return __('capell-insights::widgets.insights');
    }

    #[Override]
    public function getSubheading(): ?string
    {
        return __('capell-insights::widgets.insights_hint');
    }

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            InsightsOverviewStatsWidget::class,
            LiveInsightsStatsWidget::class,
            PopularPagesWidget::class,
            TrendingPagesWidget::class,
            RecentJourneysWidget::class,
            TopActionsWidget::class,
        ];
    }
}
