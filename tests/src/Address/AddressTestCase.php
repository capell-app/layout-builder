<?php

declare(strict_types=1);

namespace Capell\Tests\Address;

use Override;
use Capell\Address\AddressServiceProvider;
use Capell\Admin\AdminServiceProvider;
use Capell\Core\Facades\CapellCore;
use Capell\Tests\AbstractTestCase;
use Capell\Tests\Fixtures\Support\Filament\AdminPanelProvider;

class AddressTestCase extends AbstractTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            AddressServiceProvider::class,
            AdminServiceProvider::class,
            AdminPanelProvider::class,
        ];
    }

    #[Override]
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(AddressServiceProvider::$packageName);
    }

    protected function getPackageName(): string
    {
        return 'address';
    }
}
