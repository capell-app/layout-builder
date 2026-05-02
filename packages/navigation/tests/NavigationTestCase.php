<?php

declare(strict_types=1);

namespace Capell\Navigation\Tests;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Admin\Providers\Filament\AdminPanelProvider;
use Capell\Core\Facades\CapellCore;
use Capell\Frontend\Contracts\SettingsMigrationProviderInterface;
use Capell\Frontend\Providers\FrontendServiceProvider;
use Capell\Navigation\Providers\NavigationServiceProvider;
use Capell\Navigation\Support\Loader\NavigationItemsLoader;
use Capell\Tests\AbstractTestCase;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Foundation\Application;
use Livewire\LivewireServiceProvider;
use MichalOravec\PaginateRoute\PaginateRouteServiceProvider;
use Override;

class NavigationTestCase extends AbstractTestCase
{
    protected function setUp(): void
    {
        NavigationItemsLoader::flushPageCache();

        parent::setUp();

        $this->registerAndMigrateSettings(
            CapellCore::getSettingMigrations(),
            __DIR__ . '/../../../vendor/capell-app/core/database/settings',
        );

        $this->registerAndMigrateSettings(
            CapellAdmin::getSettingMigrations(),
            __DIR__ . '/../../../vendor/capell-app/admin/database/settings',
        );

        $this->registerAndMigrateSettings(
            resolve(SettingsMigrationProviderInterface::class)->getSettingMigrations(),
            __DIR__ . '/../../../vendor/capell-app/frontend/database/settings',
        );
    }

    protected function getPackageServiceName(): string
    {
        return 'capell-navigation';
    }

    /**
     * Return the default database connection for direct query-builder access in tests.
     */
    protected function connection(): Connection
    {
        return $this->app->make(ConnectionResolverInterface::class)->connection();
    }

    /**
     * @return class-string[]
     */
    #[Override]
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            AdminServiceProvider::class,
            FrontendServiceProvider::class,
            LivewireServiceProvider::class,
            PaginateRouteServiceProvider::class,
            NavigationServiceProvider::class,
            AdminPanelProvider::class,
        ];
    }

    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(AdminServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(FrontendServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(NavigationServiceProvider::$packageName);
    }

    #[Override]
    protected function registerPackageConfigs(Application $app, ?array $packages = null): void
    {
        parent::registerPackageConfigs($app, $packages);

        $this->registerPublishConfig('admin');
        $this->registerPublishConfig('frontend');
    }
}
