<?php

declare(strict_types=1);

namespace Capell\Analytics\Providers;

use Capell\Admin\Contracts\DashboardSettingsContributor;
use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Enums\DashboardEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Analytics\Console\Commands\PurgeAnalyticsDataCommand;
use Capell\Analytics\Filament\Pages\AnalyticsPage;
use Capell\Analytics\Filament\Settings\Contributors\AnalyticsDashboardSettingsContributor;
use Capell\Analytics\Filament\Widgets\AnalyticsOverviewStatsWidget;
use Capell\Analytics\Filament\Widgets\LiveAnalyticsStatsWidget;
use Capell\Analytics\Filament\Widgets\PopularPagesWidget;
use Capell\Analytics\Filament\Widgets\RecentJourneysWidget;
use Capell\Analytics\Filament\Widgets\TopActionsWidget;
use Capell\Analytics\Filament\Widgets\TrendingPagesWidget;
use Capell\Core\Facades\CapellCore;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (! $this->isPackageInstalled()) {
            return;
        }

        $this
            ->registerDashboardSettingsContributor()
            ->registerCommands()
            ->registerPages()
            ->registerDashboardWidgets()
            ->registerSchedule();
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(AnalyticsServiceProvider::$packageName);
    }

    private function registerDashboardSettingsContributor(): self
    {
        $this->app->tag([AnalyticsDashboardSettingsContributor::class], DashboardSettingsContributor::TAG);

        return $this;
    }

    private function registerCommands(): self
    {
        if (! $this->app->runningInConsole()) {
            return $this;
        }

        $this->commands([PurgeAnalyticsDataCommand::class]);

        return $this;
    }

    private function registerDashboardWidgets(): self
    {
        CapellAdmin::registerDashboardWidget(AnalyticsOverviewStatsWidget::class, DashboardEnum::Main);
        CapellAdmin::registerDashboardWidget(PopularPagesWidget::class, DashboardEnum::Main);
        CapellAdmin::registerDashboardWidget(TrendingPagesWidget::class, DashboardEnum::Main);
        CapellAdmin::registerDashboardWidget(LiveAnalyticsStatsWidget::class, DashboardEnum::Main);
        CapellAdmin::registerDashboardWidget(RecentJourneysWidget::class, DashboardEnum::Main);
        CapellAdmin::registerDashboardWidget(TopActionsWidget::class, DashboardEnum::Main);

        return $this;
    }

    private function registerPages(): self
    {
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::page(AnalyticsPage::class));

        return $this;
    }

    private function registerSchedule(): self
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('analytics:purge')->monthly();
        });

        return $this;
    }
}
