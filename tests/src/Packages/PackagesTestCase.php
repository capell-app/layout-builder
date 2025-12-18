<?php

declare(strict_types=1);

namespace Capell\Tests\Packages;

use Capell\Address\Providers\AddressServiceProvider;
use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Blog\Providers\BlogServiceProvider;
use Capell\Core\Providers\CapellServiceProvider;
use Capell\Frontend\Providers\FrontendServiceProvider;
use Capell\Layout\Providers\LayoutServiceProvider;
use Capell\Tests\AbstractTestCase;
use Capell\Tests\Fixtures\Support\Filament\AdminPanelProvider;
use Livewire\LivewireServiceProvider;

class PackagesTestCase extends AbstractTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            AddressServiceProvider::class,
            LayoutServiceProvider::class,
            BlogServiceProvider::class,
            FrontendServiceProvider::class,
            CapellServiceProvider::class,
            AdminPanelProvider::class,
            AdminServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }

    protected function requiredPackages(): array
    {
        return ['address', 'layout', 'blog', 'hero', 'frontend', 'admin'];
    }
}
