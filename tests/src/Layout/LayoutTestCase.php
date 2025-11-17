<?php

declare(strict_types=1);

namespace Capell\Tests\Layout;

use Override;
use Capell\Admin\AdminServiceProvider;
use Capell\Core\Facades\CapellCore;
use Capell\Layout\LayoutServiceProvider;
use Capell\Tests\AbstractTestCase;
use Capell\Tests\Fixtures\Support\Filament\AdminPanelProvider;

class LayoutTestCase extends AbstractTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            LayoutServiceProvider::class,
            AdminPanelProvider::class,
            AdminServiceProvider::class,
        ];
    }

    #[Override]
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(LayoutServiceProvider::$packageName);
    }

    protected function getPackageName(): string
    {
        return 'layout';
    }
}
