<?php

declare(strict_types=1);

namespace Capell\Redirects\Tests;

use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Admin\Providers\Filament\AdminPanelProvider;
use Capell\Core\Facades\CapellCore;
use Capell\Redirects\Providers\RedirectsServiceProvider;
use Capell\Tests\AbstractTestCase;
use Livewire\LivewireServiceProvider;
use Override;

abstract class RedirectsTestCase extends AbstractTestCase
{
    protected function getPackageServiceName(): string
    {
        return 'capell-redirects';
    }

    /** @return array<int, class-string> */
    #[Override]
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            AdminServiceProvider::class,
            AdminPanelProvider::class,
            LivewireServiceProvider::class,
            RedirectsServiceProvider::class,
        ];
    }

    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(AdminServiceProvider::$packageName);
        CapellCore::registerPackage(
            RedirectsServiceProvider::$packageName,
            path: realpath(__DIR__ . '/../'),
        );
        CapellCore::forcePackageInstalled(RedirectsServiceProvider::$packageName);
    }
}
