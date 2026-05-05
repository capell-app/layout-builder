<?php

declare(strict_types=1);

namespace Capell\GoogleAnalytics\Tests;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Providers\AdminServiceProvider as CapellAdminServiceProvider;
use Capell\Core\Facades\CapellCore;
use Capell\GoogleAnalytics\Providers\GoogleAnalyticsServiceProvider;
use Capell\GoogleAnalytics\Settings\GoogleAnalyticsSettingsMigrationProvider;
use Capell\Tests\AbstractTestCase;
use Illuminate\Foundation\Application;
use Livewire\LivewireServiceProvider;
use Override;

class GoogleAnalyticsTestCase extends AbstractTestCase
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

        /** @var GoogleAnalyticsSettingsMigrationProvider $googleAnalyticsSettingsMigrationProvider */
        $googleAnalyticsSettingsMigrationProvider = resolve(GoogleAnalyticsSettingsMigrationProvider::class);

        $this->registerAndMigrateSettings(
            $googleAnalyticsSettingsMigrationProvider->getSettingMigrations(),
            $googleAnalyticsSettingsMigrationProvider->path(),
        );
    }

    protected function getPackageServiceName(): string
    {
        return 'capell-google-analytics';
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
            GoogleAnalyticsServiceProvider::class,
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
        CapellCore::forcePackageInstalled(GoogleAnalyticsServiceProvider::$packageName);
    }
}
