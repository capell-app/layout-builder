<?php

declare(strict_types=1);

namespace Capell\Tests\Hero;

use Capell\Admin\CapellAdminManager;
use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Core\CapellCoreManager;
use Capell\Core\Facades\CapellCore;
use Capell\Hero\Providers\HeroServiceProvider;
use Capell\Layout\Providers\LayoutServiceProvider;
use Capell\Tests\AbstractTestCase;
use Capell\Tests\Fixtures\Support\Filament\AdminPanelProvider;
use Override;

class HeroTestCase extends AbstractTestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->registerAndMigrateSettings([
            ...CapellCoreManager::getSettingMigrations(),
        ], __DIR__ . '/../../../vendor/capell-app/core/database/settings');

        $this->registerAndMigrateSettings([
            ...CapellAdminManager::getSettingMigrations(),
        ], __DIR__ . '/../../../vendor/capell-app/admin/database/settings');
    }

    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            LayoutServiceProvider::class,
            HeroServiceProvider::class,
            AdminPanelProvider::class,
            AdminServiceProvider::class,
        ];
    }

    #[Override]
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(AdminServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(LayoutServiceProvider::$packageName);
    }

    protected function requiredPackages(): array
    {
        return ['layout'];
    }
}
