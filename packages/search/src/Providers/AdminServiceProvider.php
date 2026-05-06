<?php

declare(strict_types=1);

namespace Capell\Search\Providers;

use Capell\Admin\Contracts\DashboardSettingsContributor;
use Capell\Admin\Enums\DashboardEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Facades\CapellCore;
use Capell\Search\Console\Commands\PurgeSearchLogsCommand;
use Capell\Search\Filament\Settings\Contributors\SearchDashboardSettingsContributor;
use Capell\Search\Filament\Widgets\SearchOverviewStatsWidget;
use Capell\Search\Filament\Widgets\TopSearchesWidget;
use Capell\Search\Filament\Widgets\TrendingSearchesWidget;
use Capell\Search\Filament\Widgets\ZeroResultSearchesWidget;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

final class AdminServiceProvider extends ServiceProvider
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

        $this->registerDashboardSettingsContributor()
            ->registerCommands()
            ->registerDashboardWidgets()
            ->registerSchedule();
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(SearchServiceProvider::$packageName);
    }

    private function registerDashboardSettingsContributor(): self
    {
        if (! class_exists(SearchDashboardSettingsContributor::class)) {
            return $this;
        }

        $this->app->tag([SearchDashboardSettingsContributor::class], DashboardSettingsContributor::TAG);

        return $this;
    }

    private function registerCommands(): self
    {
        if (! $this->app->runningInConsole()) {
            return $this;
        }

        if (! class_exists(PurgeSearchLogsCommand::class)) {
            return $this;
        }

        $this->commands([PurgeSearchLogsCommand::class]);

        return $this;
    }

    private function registerDashboardWidgets(): self
    {
        $widgetClasses = [
            SearchOverviewStatsWidget::class,
            TopSearchesWidget::class,
            TrendingSearchesWidget::class,
            ZeroResultSearchesWidget::class,
        ];

        foreach ($widgetClasses as $widgetClass) {
            if (! class_exists($widgetClass)) {
                continue;
            }

            CapellAdmin::registerDashboardWidget($widgetClass, DashboardEnum::Main);
        }

        return $this;
    }

    private function registerSchedule(): self
    {
        if (! class_exists(PurgeSearchLogsCommand::class)) {
            return $this;
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('search:purge')->monthly();
        });

        return $this;
    }
}
