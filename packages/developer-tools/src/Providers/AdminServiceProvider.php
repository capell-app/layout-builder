<?php

declare(strict_types=1);

namespace Capell\DeveloperTools\Providers;

use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Enums\DashboardEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Facades\CapellCore;
use Capell\DeveloperTools\Filament\Pages\DeveloperToolsPage;
use Capell\DeveloperTools\Filament\Pages\PermissionAuditPage;
use Capell\DeveloperTools\Filament\Pages\QueueHealthPage;
use Capell\DeveloperTools\Filament\Pages\SystemHealthPage;
use Capell\DeveloperTools\Filament\Widgets\Health\AlertsWidgetAbstract;
use Capell\DeveloperTools\Filament\Widgets\Health\CacheHealthWidgetAbstract;
use Capell\DeveloperTools\Filament\Widgets\Health\ConfigDriftWidgetAbstract;
use Capell\DeveloperTools\Filament\Widgets\Health\ContentHealthWidgetAbstract;
use Capell\DeveloperTools\Filament\Widgets\Health\MigrationsHealthWidgetAbstract;
use Capell\DeveloperTools\Filament\Widgets\Health\PackagesInstalledWidgetAbstract;
use Capell\DeveloperTools\Filament\Widgets\Health\RegistryHealthWidgetAbstract;
use Capell\DeveloperTools\Filament\Widgets\Health\SetupHealthWidgetAbstract;
use Capell\DeveloperTools\Filament\Widgets\Health\SiteHealthWidgetAbstract;
use Capell\DeveloperTools\Filament\Widgets\Health\TailwindBuildStatusWidgetAbstract;
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

        $this
            ->registerPages()
            ->registerDashboardWidgets();
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(DeveloperToolsServiceProvider::$packageName);
    }

    private function registerPages(): self
    {
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::page(DeveloperToolsPage::class));
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::page(SystemHealthPage::class));
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::page(QueueHealthPage::class));
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::page(PermissionAuditPage::class));

        return $this;
    }

    private function registerDashboardWidgets(): self
    {
        CapellAdmin::registerDashboardWidget(SiteHealthWidgetAbstract::class, DashboardEnum::Main);

        CapellAdmin::registerDashboardWidget(SetupHealthWidgetAbstract::class, DashboardEnum::SystemHealth);
        CapellAdmin::registerDashboardWidget(AlertsWidgetAbstract::class, DashboardEnum::SystemHealth);
        CapellAdmin::registerDashboardWidget(ContentHealthWidgetAbstract::class, DashboardEnum::SystemHealth);
        CapellAdmin::registerDashboardWidget(RegistryHealthWidgetAbstract::class, DashboardEnum::SystemHealth);
        CapellAdmin::registerDashboardWidget(MigrationsHealthWidgetAbstract::class, DashboardEnum::SystemHealth);
        CapellAdmin::registerDashboardWidget(PackagesInstalledWidgetAbstract::class, DashboardEnum::SystemHealth);
        CapellAdmin::registerDashboardWidget(ConfigDriftWidgetAbstract::class, DashboardEnum::SystemHealth);
        CapellAdmin::registerDashboardWidget(CacheHealthWidgetAbstract::class, DashboardEnum::SystemHealth);
        CapellAdmin::registerDashboardWidget(TailwindBuildStatusWidgetAbstract::class, DashboardEnum::SystemHealth);

        return $this;
    }
}
