<?php

declare(strict_types=1);

use Capell\Admin\Contracts\DashboardSettingsContributor;
use Capell\Admin\Contracts\Extenders\AdminPanelExtender;
use Capell\Admin\Enums\DashboardEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Support\CapellAdminManager;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Capell\LoginAudit\Filament\Extenders\LoginAuditAdminPanelExtender;
use Capell\LoginAudit\Filament\Resources\LoginAudits\LoginAuditResource;
use Capell\LoginAudit\Filament\Settings\Contributors\LoginAuditDashboardSettingsContributor;
use Capell\LoginAudit\Filament\Settings\LoginAuditSettingsSchema;
use Capell\LoginAudit\Filament\Widgets\LoginAuditsWidget;
use Capell\LoginAudit\Models\LoginAudit;
use Capell\LoginAudit\Providers\AdminServiceProvider;
use Capell\LoginAudit\Providers\LoginAuditServiceProvider;
use Capell\LoginAudit\Settings\LoginAuditSettings;

it('registers login-audit bridges through package-neutral Capell extension points', function (): void {
    $adminPanelExtenders = collect(app()->tagged(AdminPanelExtender::TAG))
        ->map(fn (object $extender): string => $extender::class);

    $dashboardContributors = collect(app()->tagged(DashboardSettingsContributor::TAG))
        ->map(fn (object $contributor): string => $contributor::class);

    expect($adminPanelExtenders)->toContain(LoginAuditAdminPanelExtender::class)
        ->and($dashboardContributors)->toContain(LoginAuditDashboardSettingsContributor::class)
        ->and(CapellCore::getProtectedTables())->toContain(config('login-audit.table_name', 'login_audit'))
        ->and(CapellCore::getModels())->toContain(LoginAudit::class);
});

it('registers login-audit settings in the settings registry', function (): void {
    $registry = resolve(SettingsSchemaRegistry::class);

    expect($registry->getSettingsClass('login_audit'))
        ->toBe(LoginAuditSettings::class)
        ->and($registry->getSchema('login_audit', 'LoginAuditSettingsSchema'))
        ->toBe(LoginAuditSettingsSchema::class);
});

it('does not register admin surfaces when login-audit is not installed', function (): void {
    CapellCore::forcePackageInstalled(LoginAuditServiceProvider::$packageName, false);
    app()->forgetInstance(CapellAdminManager::class);
    CapellAdmin::clearResolvedInstance(CapellAdminManager::class);

    $provider = new AdminServiceProvider(app());
    $provider->register();
    $provider->boot();

    expect(CapellAdmin::getAdminSurfaceRegistry()->resources())
        ->not->toContain(LoginAuditResource::class)
        ->and(CapellAdmin::getDashboardWidgets(DashboardEnum::SystemHealth))
        ->not->toContain(LoginAuditsWidget::class);

    CapellCore::forcePackageInstalled(LoginAuditServiceProvider::$packageName);
});

it('registers admin surfaces when login-audit is installed', function (): void {
    CapellCore::forcePackageInstalled(LoginAuditServiceProvider::$packageName);
    app()->forgetInstance(CapellAdminManager::class);
    CapellAdmin::clearResolvedInstance(CapellAdminManager::class);

    $provider = new AdminServiceProvider(app());
    $provider->register();
    $provider->boot();

    expect(CapellAdmin::getAdminSurfaceRegistry()->resources())
        ->toContain(LoginAuditResource::class)
        ->and(CapellAdmin::getDashboardWidgets(DashboardEnum::SystemHealth))
        ->toContain(LoginAuditsWidget::class);
});
