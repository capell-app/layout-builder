<?php

declare(strict_types=1);

namespace Capell\Insights\Tests;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Core\Facades\CapellCore;
use Capell\Frontend\Contracts\SettingsMigrationProviderInterface;
use Capell\Frontend\Providers\FrontendServiceProvider;
use Capell\Insights\Providers\InsightsServiceProvider;
use Capell\Insights\Settings\InsightsSettingsMigrationProvider;
use Capell\Tests\AbstractTestCase;
use Illuminate\Foundation\Application;
use Livewire\LivewireServiceProvider;
use MichalOravec\PaginateRoute\PaginateRouteServiceProvider;
use Override;

class InsightsTestCase extends AbstractTestCase
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

        if ($this->app->bound(SettingsMigrationProviderInterface::class)) {
            $this->registerAndMigrateSettings(
                resolve(SettingsMigrationProviderInterface::class)->getSettingMigrations(),
                __DIR__ . '/../../../vendor/capell-app/frontend/database/settings',
            );
        }

        /** @var InsightsSettingsMigrationProvider $insightsSettingsMigrationProvider */
        $insightsSettingsMigrationProvider = resolve(InsightsSettingsMigrationProvider::class);

        $this->registerAndMigrateSettings(
            $insightsSettingsMigrationProvider->getSettingMigrations(),
            $insightsSettingsMigrationProvider->path(),
        );
    }

    protected function getPackageServiceName(): string
    {
        return 'capell-insights';
    }

    /**
     * @param  Application  $app
     * @return class-string[]
     */
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            AdminServiceProvider::class,
            FrontendServiceProvider::class,
            PaginateRouteServiceProvider::class,
            LivewireServiceProvider::class,
            InsightsServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(AdminServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(FrontendServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(InsightsServiceProvider::$packageName);
    }
}
