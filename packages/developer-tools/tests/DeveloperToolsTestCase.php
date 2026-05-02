<?php

declare(strict_types=1);

namespace Capell\DeveloperTools\Tests;

use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Admin\Providers\Filament\AdminPanelProvider;
use Capell\Core\Facades\CapellCore;
use Capell\DeveloperTools\Providers\AdminServiceProvider as DeveloperToolsAdminServiceProvider;
use Capell\DeveloperTools\Providers\DeveloperToolsServiceProvider;
use Capell\Tests\AbstractTestCase;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Livewire\LivewireServiceProvider;
use Override;

class DeveloperToolsTestCase extends AbstractTestCase
{
    use CreatesAdminUser;

    protected function getPackageServiceName(): string
    {
        return 'capell-developer-tools';
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
            AdminPanelProvider::class,
            LivewireServiceProvider::class,
            DeveloperToolsServiceProvider::class,
            DeveloperToolsAdminServiceProvider::class,
        ];
    }

    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(AdminServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(DeveloperToolsServiceProvider::$packageName);
    }
}
