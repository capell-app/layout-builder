<?php

declare(strict_types=1);

namespace Capell\AuthenticationLog\Providers;

use Capell\Admin\Contracts\DashboardSettingsContributor;
use Capell\Admin\Contracts\Extenders\AdminPanelExtender;
use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Enums\DashboardEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\AuthenticationLog\Actions\ApplyAuthenticationLogSettingsAction;
use Capell\AuthenticationLog\Filament\Extenders\AuthenticationLogAdminPanelExtender;
use Capell\AuthenticationLog\Filament\Resources\AuthenticationLogs\AuthenticationLogResource;
use Capell\AuthenticationLog\Filament\Settings\Contributors\AuthenticationLogDashboardSettingsContributor;
use Capell\AuthenticationLog\Filament\Widgets\AuthenticationLogsWidget;
use Capell\Core\Facades\CapellCore;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Config::set(
            'filament-authentication-log.resources.AuthenticationLogResource',
            AuthenticationLogResource::class,
        );
        Config::set('filament-authentication-log.authenticatable.field-to-display', 'name');
    }

    public function boot(): void
    {
        if (! $this->isPackageInstalled()) {
            return;
        }

        $this->app->tag([AuthenticationLogAdminPanelExtender::class], AdminPanelExtender::TAG);
        $this->app->tag([AuthenticationLogDashboardSettingsContributor::class], DashboardSettingsContributor::TAG);

        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::resource(
            class: AuthenticationLogResource::class,
            group: 'AuthenticationLog',
        ));
        CapellAdmin::registerDashboardWidget(AuthenticationLogsWidget::class, DashboardEnum::SystemHealth);

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            ApplyAuthenticationLogSettingsAction::run();

            $schedule
                ->command('authentication-log:purge')
                ->before(function (): void {
                    ApplyAuthenticationLogSettingsAction::run();
                })
                ->monthly();
        });
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(AuthenticationLogServiceProvider::$packageName);
    }
}
