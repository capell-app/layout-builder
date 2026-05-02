<?php

declare(strict_types=1);

use Capell\Admin\Contracts\DashboardSettingsContributor;
use Capell\Admin\Contracts\Extenders\AdminPanelExtender;
use Capell\AuthenticationLog\Filament\Extenders\AuthenticationLogAdminPanelExtender;
use Capell\AuthenticationLog\Filament\Settings\AuthenticationLogSettingsSchema;
use Capell\AuthenticationLog\Filament\Settings\Contributors\AuthenticationLogDashboardSettingsContributor;
use Capell\AuthenticationLog\Models\AuthenticationLog;
use Capell\AuthenticationLog\Settings\AuthenticationLogSettings;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;

it('registers authentication-log bridges through package-neutral Capell extension points', function (): void {
    $adminPanelExtenders = collect(app()->tagged(AdminPanelExtender::TAG))
        ->map(fn (object $extender): string => $extender::class);

    $dashboardContributors = collect(app()->tagged(DashboardSettingsContributor::TAG))
        ->map(fn (object $contributor): string => $contributor::class);

    expect($adminPanelExtenders)->toContain(AuthenticationLogAdminPanelExtender::class)
        ->and($dashboardContributors)->toContain(AuthenticationLogDashboardSettingsContributor::class)
        ->and(CapellCore::getProtectedTables())->toContain(config('authentication-log.table_name', 'authentication_log'))
        ->and(CapellCore::getModels())->toContain(AuthenticationLog::class);
});

it('registers authentication-log settings in the settings registry', function (): void {
    $registry = resolve(SettingsSchemaRegistry::class);

    expect($registry->getSettingsClass('authentication_log'))
        ->toBe(AuthenticationLogSettings::class)
        ->and($registry->getSchema('authentication_log', 'AuthenticationLogSettingsSchema'))
        ->toBe(AuthenticationLogSettingsSchema::class);
});
