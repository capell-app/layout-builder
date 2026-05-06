<?php

declare(strict_types=1);

namespace Capell\Insights\Providers;

use Capell\Admin\Contracts\DashboardSettingsContributor;
use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Enums\DashboardEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Facades\CapellCore;
use Capell\Insights\Console\Commands\PurgeInsightsDataCommand;
use Capell\Insights\Filament\Pages\InsightsPage;
use Capell\Insights\Filament\Settings\Contributors\InsightsDashboardSettingsContributor;
use Capell\Insights\Filament\Widgets\InsightsOverviewStatsWidget;
use Capell\Insights\Filament\Widgets\LiveInsightsStatsWidget;
use Capell\Insights\Filament\Widgets\PopularPagesWidget;
use Capell\Insights\Filament\Widgets\RecentJourneysWidget;
use Capell\Insights\Filament\Widgets\TopActionsWidget;
use Capell\Insights\Filament\Widgets\TrendingPagesWidget;
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
        return CapellCore::isPackageInstalled(InsightsServiceProvider::$packageName);
    }

    private function registerDashboardSettingsContributor(): self
    {
        $this->app->tag([InsightsDashboardSettingsContributor::class], DashboardSettingsContributor::TAG);

        return $this;
    }

    private function registerCommands(): self
    {
        if (! $this->app->runningInConsole()) {
            return $this;
        }

        $this->commands([PurgeInsightsDataCommand::class]);

        return $this;
    }

    private function registerDashboardWidgets(): self
    {
        CapellAdmin::registerDashboardWidget(InsightsOverviewStatsWidget::class, DashboardEnum::Main);
        CapellAdmin::registerDashboardWidget(PopularPagesWidget::class, DashboardEnum::Main);
        CapellAdmin::registerDashboardWidget(TrendingPagesWidget::class, DashboardEnum::Main);
        CapellAdmin::registerDashboardWidget(LiveInsightsStatsWidget::class, DashboardEnum::Main);
        CapellAdmin::registerDashboardWidget(RecentJourneysWidget::class, DashboardEnum::Main);
        CapellAdmin::registerDashboardWidget(TopActionsWidget::class, DashboardEnum::Main);

        return $this;
    }

    private function registerPages(): self
    {
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::page(InsightsPage::class));

        return $this;
    }

    private function registerSchedule(): self
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('insights:purge')->monthly();
        });

        return $this;
    }
}
