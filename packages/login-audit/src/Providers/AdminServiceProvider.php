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
use Capell\LoginAudit\Extenders\LoginAuditUserSchemaExtender;
use Capell\LoginAudit\Filament\Extenders\LoginAuditAdminPanelExtender;
use Capell\LoginAudit\Filament\Resources\LoginAudits\LoginAuditResource;
use Capell\LoginAudit\Filament\Settings\Contributors\LoginAuditDashboardSettingsContributor;
use Capell\LoginAudit\Filament\Widgets\LoginAuditsWidget;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

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

        $this->app->bind(LoginAuditUserSchemaExtender::class);
        $this->app->tag([LoginAuditUserSchemaExtender::class], UserSchemaExtender::TAG);
        $this->app->tag([LoginAuditAdminPanelExtender::class], AdminPanelExtender::TAG);
        $this->app->tag([LoginAuditDashboardSettingsContributor::class], DashboardSettingsContributor::TAG);

        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::resource(
            class: LoginAuditResource::class,
            group: 'LoginAudit',
        ));
        CapellAdmin::registerDashboardWidget(LoginAuditsWidget::class, DashboardEnum::SystemHealth);

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

    private function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(LoginAuditServiceProvider::$packageName);
    }
}
