<?php

declare(strict_types=1);

namespace Capell\FrontendAuthoring\Tests;

use Capell\Core\Facades\CapellCore;
use Capell\Frontend\Contracts\SettingsMigrationProviderInterface;
use Capell\Frontend\Providers\FrontendServiceProvider;
use Capell\FrontendAuthoring\Providers\FrontendAuthoringServiceProvider;
use Capell\Tests\AbstractTestCase;
use Livewire\LivewireServiceProvider;
use MichalOravec\PaginateRoute\PaginateRouteServiceProvider;

class FrontendAuthoringTestCase extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->registerAndMigrateSettings(
            resolve(SettingsMigrationProviderInterface::class)->getSettingMigrations(),
            __DIR__ . '/../../../vendor/capell-app/frontend/database/settings',
        );
    }

    protected function getPackageServiceName(): string
    {
        return 'capell-frontend-authoring';
    }

    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(FrontendServiceProvider::$packageName);
    }

    /**
     * @return class-string[]
     */
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            FrontendServiceProvider::class,
            PaginateRouteServiceProvider::class,
            LivewireServiceProvider::class,
            FrontendAuthoringServiceProvider::class,
        ];
    }
}
