<?php

declare(strict_types=1);

namespace Capell\Tests\Layout;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Core\Facades\CapellCore;
use Capell\Frontend\Providers\FrontendServiceProvider;
use Capell\Mosaic\Providers\MosaicServiceProvider;
use Capell\Tests\AbstractTestCase;
use Capell\Tests\Fixtures\Admin\AdminPanelProvider;
use Illuminate\Foundation\Application;
use Livewire\LivewireServiceProvider;

class LayoutTestCase extends AbstractTestCase
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
    }

    protected function getPackageServiceName(): string
    {
        return 'capell-mosaic';
    }

    /**
     * @param  Application  $app
     * @return class-string[]
     */
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            MosaicServiceProvider::class,
            AdminPanelProvider::class,
            AdminServiceProvider::class,
            FrontendServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(AdminServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(FrontendServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(MosaicServiceProvider::$packageName);
    }
}
