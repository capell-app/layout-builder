<?php

declare(strict_types=1);

namespace Capell\Analytics\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Capell\Analytics\Filament\Widgets\AnalyticsOverviewStatsWidget;
use Capell\Analytics\Filament\Widgets\LiveAnalyticsStatsWidget;
use Capell\Analytics\Filament\Widgets\PopularPagesWidget;
use Capell\Analytics\Filament\Widgets\RecentJourneysWidget;
use Capell\Analytics\Filament\Widgets\TopActionsWidget;
use Capell\Analytics\Filament\Widgets\TrendingPagesWidget;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Override;

final class AnalyticsPage extends Page
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::ChartBar;

    protected string $view = 'capell-analytics::filament.pages.analytics';

    protected static ?string $slug = 'analytics';

    #[Override]
    public static function getNavigationLabel(): string
    {
        return __('capell-analytics::widgets.analytics');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return __('capell-admin::navigation.group_monitoring');
    }

    #[Override]
    public function getTitle(): string|Htmlable
    {
        return __('capell-analytics::widgets.analytics');
    }

    #[Override]
    public function getSubheading(): string|Htmlable|null
    {
        return __('capell-analytics::widgets.analytics_hint');
    }

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            AnalyticsOverviewStatsWidget::class,
            LiveAnalyticsStatsWidget::class,
            PopularPagesWidget::class,
            TrendingPagesWidget::class,
            RecentJourneysWidget::class,
            TopActionsWidget::class,
        ];
    }
}
