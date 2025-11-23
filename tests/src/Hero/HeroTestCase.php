<?php

declare(strict_types=1);

namespace Capell\Tests\Hero;

use Capell\Admin\AdminServiceProvider;
use Capell\Core\Facades\CapellCore;
use Capell\Hero\Providers\HeroServiceProvider;
use Capell\Layout\LayoutServiceProvider;
use Capell\Tests\AbstractTestCase;
use Capell\Tests\Fixtures\Support\Filament\AdminPanelProvider;
use Override;

class HeroTestCase extends AbstractTestCase
{
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
