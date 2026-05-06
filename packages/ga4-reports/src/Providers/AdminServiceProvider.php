<?php

declare(strict_types=1);

namespace Capell\GA4Reports\Providers;

use Capell\Admin\Contracts\DashboardSettingsContributor;
use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Enums\DashboardEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Facades\CapellCore;
use Capell\GA4Reports\Console\Commands\SyncGA4ReportsCommand;
use Capell\GA4Reports\Filament\Pages\GA4ReportsPage;
use Capell\GA4Reports\Filament\Settings\Contributors\GA4ReportsDashboardSettingsContributor;
use Capell\GA4Reports\Filament\Widgets\GA4ReportsOverviewStatsWidget;
use Capell\GA4Reports\Filament\Widgets\GA4ReportsSetupStatusWidget;
use Capell\GA4Reports\Filament\Widgets\GA4ReportsTopPagesWidget;
use Capell\GA4Reports\Filament\Widgets\GA4ReportsTrafficTrendWidget;
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
        return CapellCore::isPackageInstalled(GA4ReportsServiceProvider::$packageName);
    }

    private function registerDashboardSettingsContributor(): self
    {
        $this->app->tag([GA4ReportsDashboardSettingsContributor::class], DashboardSettingsContributor::TAG);

        return $this;
    }

    private function registerCommands(): self
    {
        if (! $this->app->runningInConsole()) {
            return $this;
        }

        $this->commands([SyncGA4ReportsCommand::class]);

        return $this;
    }

    private function registerPages(): self
    {
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::page(GA4ReportsPage::class));

        return $this;
    }

    private function registerDashboardWidgets(): self
    {
        CapellAdmin::registerDashboardWidget(GA4ReportsOverviewStatsWidget::class, DashboardEnum::Main);
        CapellAdmin::registerDashboardWidget(GA4ReportsTrafficTrendWidget::class, DashboardEnum::Main);
        CapellAdmin::registerDashboardWidget(GA4ReportsTopPagesWidget::class, DashboardEnum::Main);
        CapellAdmin::registerDashboardWidget(GA4ReportsSetupStatusWidget::class, DashboardEnum::Main);

        return $this;
    }

    private function registerSchedule(): self
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('ga4-reports:sync')->daily();
        });

        return $this;
    }
}
