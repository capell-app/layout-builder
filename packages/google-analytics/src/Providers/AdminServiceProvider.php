<?php

declare(strict_types=1);

namespace Capell\GoogleAnalytics\Providers;

use Capell\Admin\Contracts\DashboardSettingsContributor;
use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Enums\DashboardEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Facades\CapellCore;
use Capell\GoogleAnalytics\Console\Commands\SyncGoogleAnalyticsCommand;
use Capell\GoogleAnalytics\Filament\Pages\GoogleAnalyticsPage;
use Capell\GoogleAnalytics\Filament\Settings\Contributors\GoogleAnalyticsDashboardSettingsContributor;
use Capell\GoogleAnalytics\Filament\Widgets\GoogleAnalyticsOverviewStatsWidget;
use Capell\GoogleAnalytics\Filament\Widgets\GoogleAnalyticsSetupStatusWidget;
use Capell\GoogleAnalytics\Filament\Widgets\GoogleAnalyticsTopPagesWidget;
use Capell\GoogleAnalytics\Filament\Widgets\GoogleAnalyticsTrafficTrendWidget;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

final class AdminServiceProvider extends ServiceProvider
{
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
        return CapellCore::isPackageInstalled(GoogleAnalyticsServiceProvider::$packageName);
    }

    private function registerDashboardSettingsContributor(): self
    {
        $this->app->tag([GoogleAnalyticsDashboardSettingsContributor::class], DashboardSettingsContributor::TAG);

        return $this;
    }

    private function registerCommands(): self
    {
        if (! $this->app->runningInConsole()) {
            return $this;
        }

        $this->commands([SyncGoogleAnalyticsCommand::class]);

        return $this;
    }

    private function registerPages(): self
    {
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::page(GoogleAnalyticsPage::class));

        return $this;
    }

    private function registerDashboardWidgets(): self
    {
        CapellAdmin::registerDashboardWidget(GoogleAnalyticsOverviewStatsWidget::class, DashboardEnum::Main);
        CapellAdmin::registerDashboardWidget(GoogleAnalyticsTrafficTrendWidget::class, DashboardEnum::Main);
        CapellAdmin::registerDashboardWidget(GoogleAnalyticsTopPagesWidget::class, DashboardEnum::Main);
        CapellAdmin::registerDashboardWidget(GoogleAnalyticsSetupStatusWidget::class, DashboardEnum::Main);

        return $this;
    }

    private function registerSchedule(): self
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('google-analytics:sync')->daily();
        });

        return $this;
    }
}
