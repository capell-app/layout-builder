<?php

declare(strict_types=1);

namespace Capell\Toolbar\Tests;

use Capell\Core\Facades\CapellCore;
use Capell\Frontend\Contracts\SettingsMigrationProviderInterface;
use Capell\Frontend\Providers\FrontendServiceProvider;
use Capell\Tests\AbstractTestCase;
use Capell\Toolbar\Providers\ToolbarServiceProvider;
use Livewire\LivewireServiceProvider;
use MichalOravec\PaginateRoute\PaginateRouteServiceProvider;

class ToolbarTestCase extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->registerAndMigrateSettings(
            resolve(SettingsMigrationProviderInterface::class)->getSettingMigrations(),
            __DIR__ . '/../../../packages/frontend/database/settings',
        );
    }

    protected function getPackageServiceName(): string
    {
        return 'capell-frontend-toolbar';
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(FrontendServiceProvider::$packageName);
    }

    /**
     * @return class-string[]
     */
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getDefaultPackageProviders(),
            FrontendServiceProvider::class,
            PaginateRouteServiceProvider::class,
            LivewireServiceProvider::class,
            ToolbarServiceProvider::class,
        ];
    }
}
