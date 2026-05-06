<?php

declare(strict_types=1);

namespace Capell\GA4Reports\Tests;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Providers\AdminServiceProvider as CapellAdminServiceProvider;
use Capell\Core\Facades\CapellCore;
use Capell\GA4Reports\Providers\GA4ReportsServiceProvider;
use Capell\GA4Reports\Settings\GA4ReportsSettingsMigrationProvider;
use Capell\Tests\AbstractTestCase;
use Illuminate\Foundation\Application;
use Livewire\LivewireServiceProvider;
use Override;

class GA4ReportsTestCase extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->registerAndMigrateSettings(
            CapellCore::getSettingMigrations(),
            __DIR__ . '/../../../vendor/capell-app/core/database/settings',
        );

        $this->registerAndMigrateSettings(
            CapellAdmin::getSettingMigrations(),
            __DIR__ . '/../../../vendor/capell-app/admin/database/settings',
        );

        /** @var GA4ReportsSettingsMigrationProvider $googleInsightsSettingsMigrationProvider */
        $googleInsightsSettingsMigrationProvider = resolve(GA4ReportsSettingsMigrationProvider::class);

        $this->registerAndMigrateSettings(
            $googleInsightsSettingsMigrationProvider->getSettingMigrations(),
            $googleInsightsSettingsMigrationProvider->path(),
        );
    }

    protected function getPackageServiceName(): string
    {
        return 'capell-ga4-reports';
    }

    /**
     * @param  Application  $app
     * @return class-string[]
     */
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            CapellAdminServiceProvider::class,
            LivewireServiceProvider::class,
            GA4ReportsServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(CapellAdminServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(GA4ReportsServiceProvider::$packageName);
    }
}
