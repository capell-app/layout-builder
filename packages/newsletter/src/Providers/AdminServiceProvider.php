<?php

declare(strict_types=1);

namespace Capell\Newsletter\Providers;

use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Enums\DashboardEnum;
use Capell\Admin\Enums\NavigationGroupPositionEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Facades\CapellCore;
use Capell\Newsletter\Console\Commands\RequeueDueProviderSyncAttemptsCommand;
use Capell\Newsletter\Enums\ResourceEnum;
use Capell\Newsletter\Filament\Widgets\NewsletterOverviewStatsWidget;
use Filament\Support\Icons\Heroicon;
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
        $this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'capell-newsletter');

        if (! $this->isPackageInstalled()) {
            return;
        }

        if ($this->app->runningInConsole()) {
            $this->commands([RequeueDueProviderSyncAttemptsCommand::class]);
        }

        $this->registerNavigationGroups();

        foreach (ResourceEnum::cases() as $resource) {
            CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::resource(
                class: $resource->value,
                group: $resource->name,
            ));
        }

        CapellAdmin::registerDashboardWidget(NewsletterOverviewStatsWidget::class, DashboardEnum::Main);

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('newsletter:sync-retry-due')->everyFiveMinutes();
        });
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(NewsletterServiceProvider::$packageName);
    }

    private function registerNavigationGroups(): self
    {
        CapellAdmin::registerNavigationGroup(
            label: 'Marketing',
            icon: Heroicon::OutlinedMegaphone,
            position: NavigationGroupPositionEnum::After,
            relativeTo: 'capell-admin::navigation.group_content',
        );

        return $this;
    }
}
