<?php

declare(strict_types=1);

namespace Capell\LoginAudit\Providers;

use Capell\Admin\Contracts\DashboardSettingsContributor;
use Capell\Admin\Contracts\Extenders\AdminPanelExtender;
use Capell\Admin\Contracts\Extenders\UserSchemaExtender;
use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Enums\DashboardEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Facades\CapellCore;
use Capell\LoginAudit\Actions\ApplyLoginAuditSettingsAction;
use Capell\LoginAudit\Bridges\LoginAuditAdminBridge;
use Capell\LoginAudit\Extenders\LoginAuditUserSchemaExtender;
use Capell\LoginAudit\Filament\Extenders\LoginAuditAdminPanelExtender;
use Capell\LoginAudit\Filament\Resources\LoginAudits\LoginAuditResource;
use Capell\LoginAudit\Filament\Settings\Contributors\LoginAuditDashboardSettingsContributor;
use Capell\LoginAudit\Filament\Widgets\LoginAuditsWidget;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Config::set(
            'filament-authentication-log.resources.AuthenticationLogResource',
            LoginAuditResource::class,
        );
        Config::set(
            'filament-authentication-log.resources.AutenticationLogResource',
            LoginAuditResource::class,
        );
        Config::set('filament-authentication-log.authenticatable.field-to-display', 'name');
    }

    public function boot(): void
    {
        if (! $this->isPackageInstalled()) {
            return;
        }

        $this->registerAdminIntegration();
        $this->registerSchedule();
    }

    private function registerAdminIntegration(): void
    {
        if ($this->supportsAdminBridges()) {
            CapellAdmin::registerAdminBridge(LoginAuditServiceProvider::$packageName, LoginAuditAdminBridge::class);
            CapellAdmin::bootAdminBridges(LoginAuditServiceProvider::$packageName);

            return;
        }

        $this->app->bind(LoginAuditUserSchemaExtender::class);
        $this->app->tag([LoginAuditUserSchemaExtender::class], UserSchemaExtender::TAG);
        $this->app->tag([LoginAuditAdminPanelExtender::class], AdminPanelExtender::TAG);
        $this->app->tag([LoginAuditDashboardSettingsContributor::class], DashboardSettingsContributor::TAG);

        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::resource(
            class: LoginAuditResource::class,
            group: 'LoginAudit',
        ));
        CapellAdmin::registerDashboardWidget(LoginAuditsWidget::class, DashboardEnum::SystemHealth);
    }

    private function registerSchedule(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            ApplyLoginAuditSettingsAction::run();

            $schedule
                ->command('login-audit:purge')
                ->before(function (): void {
                    ApplyLoginAuditSettingsAction::run();
                })
                ->monthly();
        });
    }

    private function supportsAdminBridges(): bool
    {
        try {
            $admin = CapellAdmin::getFacadeRoot();
        } catch (Throwable) {
            return false;
        }

        return is_object($admin)
            && method_exists($admin, 'registerAdminBridge')
            && method_exists($admin, 'bootAdminBridges')
            && class_exists(LoginAuditAdminBridge::class);
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(LoginAuditServiceProvider::$packageName);
    }
}
