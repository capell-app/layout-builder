<?php

declare(strict_types=1);

namespace Capell\GoogleAnalytics\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Capell\GoogleAnalytics\Actions\ResolveGoogleAnalyticsConfigAction;
use Capell\GoogleAnalytics\Filament\Widgets\GoogleAnalyticsOverviewStatsWidget;
use Capell\GoogleAnalytics\Filament\Widgets\GoogleAnalyticsSetupStatusWidget;
use Capell\GoogleAnalytics\Filament\Widgets\GoogleAnalyticsTopPagesWidget;
use Capell\GoogleAnalytics\Filament\Widgets\GoogleAnalyticsTrafficTrendWidget;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;
use Override;

final class GoogleAnalyticsPage extends Page
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::ChartBarSquare;

    protected string $view = 'capell-google-analytics::filament.pages.google-analytics';

    protected static ?string $slug = 'google-analytics';

    #[Override]
    public static function getSlug(?Panel $panel = null): string
    {
        return ResolveGoogleAnalyticsConfigAction::run()->routeSlug;
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return __('capell-google-analytics::widgets.google_analytics');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return __('capell-admin::navigation.group_monitoring');
    }

    #[Override]
    public function getTitle(): string
    {
        return __('capell-google-analytics::widgets.google_analytics');
    }

    #[Override]
    public function getSubheading(): ?string
    {
        return __('capell-google-analytics::widgets.google_analytics_hint');
    }

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            GoogleAnalyticsOverviewStatsWidget::class,
            GoogleAnalyticsTrafficTrendWidget::class,
            GoogleAnalyticsTopPagesWidget::class,
            GoogleAnalyticsSetupStatusWidget::class,
        ];
    }
}
