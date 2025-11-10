<?php

declare(strict_types=1);

namespace Capell\Tests\Packages;

use Capell\Address\AddressServiceProvider;
use Capell\Admin\AdminServiceProvider;
use Capell\Blog\BlogServiceProvider;
use Capell\Layout\LayoutServiceProvider;
use Capell\Tests\AbstractTestCase;
use Capell\Tests\Fixtures\Support\Filament\AdminPanelProvider;

class PackagesTestCase extends AbstractTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            AddressServiceProvider::class,
            BlogServiceProvider::class,
            LayoutServiceProvider::class,
            BlogServiceProvider::class,
            AdminPanelProvider::class,
            AdminServiceProvider::class,
        ];
    }

    protected function getPackageName(): string
    {
        return 'packages';
    }
}
