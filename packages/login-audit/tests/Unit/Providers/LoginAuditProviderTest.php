<?php

declare(strict_types=1);

use Capell\Admin\Contracts\DashboardSettingsContributor;
use Capell\Admin\Contracts\Extenders\AdminPanelExtender;
use Capell\Admin\Contracts\Extenders\UserSchemaExtender;
use Capell\Admin\Data\Bridges\AdminBridgeContextData;
use Capell\Admin\Enums\DashboardEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Support\Bridges\AdminBridgeRegistrar;
use Capell\Admin\Support\CapellAdminManager;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Capell\LoginAudit\Bridges\LoginAuditAdminBridge;
use Capell\LoginAudit\Extenders\LoginAuditUserSchemaExtender;
use Capell\LoginAudit\Filament\Extenders\LoginAuditAdminPanelExtender;
use Capell\LoginAudit\Filament\Resources\LoginAudits\LoginAuditResource;
use Capell\LoginAudit\Filament\Settings\Contributors\LoginAuditDashboardSettingsContributor;
use Capell\LoginAudit\Filament\Settings\LoginAuditSettingsSchema;
use Capell\LoginAudit\Filament\Widgets\LoginAuditsWidget;
use Capell\LoginAudit\Models\LoginAudit;
use Capell\LoginAudit\Providers\AdminServiceProvider;
use Capell\LoginAudit\Providers\LoginAuditServiceProvider;
use Capell\LoginAudit\Settings\LoginAuditSettings;
use Capell\Tests\Support\LegacyAdminBridgeFallbackHost;

function invokeLoginAuditProviderMethod(object $provider, string $method): void
{
    $reflection = new ReflectionMethod($provider, $method);
    $reflection->invoke($provider);
}

function resetLoginAuditAdminBridgeState(): void
{
    app()->forgetInstance(CapellAdminManager::class);
    CapellAdmin::clearResolvedInstance(CapellAdminManager::class);
    CapellAdmin::clearAdminSurfaceContributions();
}

it('declares its login audit provider for auth-context loading', function (): void {
    $manifest = json_decode(
        (string) file_get_contents(dirname(__DIR__, 3) . '/capell.json'),
        associative: true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($manifest['providers']['auth'] ?? [])->toContain(LoginAuditServiceProvider::class);
});

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

it('registers the current login-audit admin bridge surface', function (): void {
    resetLoginAuditAdminBridgeState();

    (new LoginAuditAdminBridge)->register(
        new AdminBridgeRegistrar,
        AdminBridgeContextData::forPackage(LoginAuditServiceProvider::$packageName),
    );

    $surfaceRegistry = CapellAdmin::getAdminSurfaceRegistry();

    expect($surfaceRegistry->schemaExtendersForTag(UserSchemaExtender::TAG))->toContain(LoginAuditUserSchemaExtender::class)
        ->and($surfaceRegistry->panelExtenders())->toContain(LoginAuditAdminPanelExtender::class)
        ->and($surfaceRegistry->resources())->toContain(LoginAuditResource::class)
        ->and(CapellAdmin::getDashboardWidgets(DashboardEnum::SystemHealth))->toContain(LoginAuditsWidget::class)
        ->and(collect(app()->tagged(DashboardSettingsContributor::TAG))->contains(
            fn (object $contributor): bool => $contributor instanceof LoginAuditDashboardSettingsContributor,
        ))->toBeTrue();
});

it('keeps the legacy admin fallback when the bridge host is unavailable', function (): void {
    $host = new LegacyAdminBridgeFallbackHost;
    CapellAdmin::swap($host);

    try {
        invokeLoginAuditProviderMethod(new AdminServiceProvider(app()), 'registerAdminIntegration');

        $userSchemaExtenders = collect(app()->tagged(UserSchemaExtender::TAG))
            ->map(fn (object $extender): string => $extender::class);
        $adminPanelExtenders = collect(app()->tagged(AdminPanelExtender::TAG))
            ->map(fn (object $extender): string => $extender::class);
        $dashboardContributors = collect(app()->tagged(DashboardSettingsContributor::TAG))
            ->map(fn (object $contributor): string => $contributor::class);

        expect($userSchemaExtenders)->toContain(LoginAuditUserSchemaExtender::class)
            ->and($adminPanelExtenders)->toContain(LoginAuditAdminPanelExtender::class)
            ->and($dashboardContributors)->toContain(LoginAuditDashboardSettingsContributor::class)
            ->and(collect($host->surfaceContributions)->pluck('class'))->toContain(LoginAuditResource::class)
            ->and(array_keys($host->dashboardWidgets))->toContain(LoginAuditsWidget::class);
    } finally {
        app()->forgetInstance(CapellAdminManager::class);
        CapellAdmin::clearResolvedInstance(CapellAdminManager::class);
    }
});
