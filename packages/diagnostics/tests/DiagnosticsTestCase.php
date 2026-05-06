<?php

declare(strict_types=1);

namespace Capell\Diagnostics\Tests;

use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Admin\Providers\Filament\AdminPanelProvider;
use Capell\Core\Facades\CapellCore;
use Capell\Diagnostics\Providers\AdminServiceProvider as DiagnosticsAdminServiceProvider;
use Capell\Diagnostics\Providers\DiagnosticsServiceProvider;
use Capell\Tests\AbstractTestCase;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Livewire\LivewireServiceProvider;
use Override;

class DiagnosticsTestCase extends AbstractTestCase
{
    use CreatesAdminUser;

    protected function getPackageServiceName(): string
    {
        return 'capell-diagnostics';
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
            DiagnosticsServiceProvider::class,
            DiagnosticsAdminServiceProvider::class,
        ];
    }

    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(AdminServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(DiagnosticsServiceProvider::$packageName);
    }
}
