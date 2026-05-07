<?php

declare(strict_types=1);

namespace Capell\Diagnostics\Providers;

use Capell\Admin\Enums\DashboardEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Facades\CapellCore;
use Capell\Diagnostics\Filament\Pages\CommandPalettePage;
use Capell\Diagnostics\Filament\Pages\DiagnosticsPage;
use Capell\Diagnostics\Filament\Pages\PermissionAuditPage;
use Capell\Diagnostics\Filament\Pages\QueueHealthPage;
use Capell\Diagnostics\Filament\Pages\SystemHealthPage;
use Capell\Diagnostics\Filament\Widgets\Health\AlertsWidgetAbstract;
use Capell\Diagnostics\Filament\Widgets\Health\CacheHealthWidgetAbstract;
use Capell\Diagnostics\Filament\Widgets\Health\ConfigDriftWidgetAbstract;
use Capell\Diagnostics\Filament\Widgets\Health\ContentHealthWidgetAbstract;
use Capell\Diagnostics\Filament\Widgets\Health\MigrationsHealthWidgetAbstract;
use Capell\Diagnostics\Filament\Widgets\Health\PackagesInstalledWidgetAbstract;
use Capell\Diagnostics\Filament\Widgets\Health\RegistryHealthWidgetAbstract;
use Capell\Diagnostics\Filament\Widgets\Health\SetupHealthWidgetAbstract;
use Capell\Diagnostics\Filament\Widgets\Health\SiteHealthWidgetAbstract;
use Capell\Diagnostics\Filament\Widgets\Health\TailwindBuildStatusWidgetAbstract;
use Capell\Diagnostics\Palette\CapellArtisanPaletteCommandProvider;
use Capell\Diagnostics\Palette\DiagnosticsPaletteCommandProvider;
use Illuminate\Support\ServiceProvider;

final class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag([
            CapellArtisanPaletteCommandProvider::class,
            DiagnosticsPaletteCommandProvider::class,
        ], 'capell.diagnostics.command-palette-provider');
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
        return CapellCore::isPackageInstalled(DiagnosticsServiceProvider::$packageName);
    }

    private function registerPages(): self
    {
        CapellAdmin::registerExtensionPage(DiagnosticsServiceProvider::$packageName, DiagnosticsPage::class);
        CapellAdmin::registerExtensionPage(DiagnosticsServiceProvider::$packageName, CommandPalettePage::class);
        CapellAdmin::registerExtensionPage(DiagnosticsServiceProvider::$packageName, SystemHealthPage::class);
        CapellAdmin::registerExtensionPage(DiagnosticsServiceProvider::$packageName, QueueHealthPage::class);
        CapellAdmin::registerExtensionPage(DiagnosticsServiceProvider::$packageName, PermissionAuditPage::class);

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
