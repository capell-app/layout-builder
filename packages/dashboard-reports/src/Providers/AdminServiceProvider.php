<?php

declare(strict_types=1);

namespace Capell\DashboardReports\Providers;

use Capell\Admin\Contracts\Dashboard\ContentHealthDataProvider;
use Capell\Admin\Contracts\DashboardSettingsContributor;
use Capell\Admin\Enums\DashboardEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Support\Dashboard\NullContentHealthDataProvider;
use Capell\Core\Facades\CapellCore;
use Capell\DashboardReports\Filament\Settings\Contributors\DashboardReportsDashboardSettingsContributor;
use Capell\DashboardReports\Filament\Widgets\ContentHealthWidget;
use Capell\DashboardReports\Filament\Widgets\PublishingTrendChartWidget;
use Capell\DashboardReports\Support\Dashboard\DashboardReportsContentHealthDataProvider;
use Illuminate\Support\ServiceProvider;

final class AdminServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! $this->isPackageInstalled()) {
            return;
        }

        $this
            ->registerDashboardDataProviders()
            ->registerDashboardSettingsContributor()
            ->registerDashboardWidgets();
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(DashboardReportsServiceProvider::$packageName);
    }

    private function registerDashboardDataProviders(): self
    {
        if (! $this->app->bound(ContentHealthDataProvider::class)) {
            $this->app->singleton(ContentHealthDataProvider::class, DashboardReportsContentHealthDataProvider::class);

            return $this;
        }

        $contentHealthDataProvider = $this->app->make(ContentHealthDataProvider::class);

        if ($contentHealthDataProvider instanceof NullContentHealthDataProvider) {
            $this->app->forgetInstance(ContentHealthDataProvider::class);
            $this->app->singleton(ContentHealthDataProvider::class, DashboardReportsContentHealthDataProvider::class);
        }

        return $this;
    }

    private function registerDashboardSettingsContributor(): self
    {
        $this->app->tag([DashboardReportsDashboardSettingsContributor::class], DashboardSettingsContributor::TAG);

        return $this;
    }

    private function registerDashboardWidgets(): self
    {
        CapellAdmin::registerDashboardWidget(PublishingTrendChartWidget::class, DashboardEnum::Main);
        CapellAdmin::registerDashboardWidget(ContentHealthWidget::class, DashboardEnum::Main);

        return $this;
    }
}
