<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Tests;

use Capell\Core\Facades\CapellCore;
use Capell\SiteSearch\Providers\SiteSearchServiceProvider;
use Capell\Tests\Packages\PackagesTestCase;
use Illuminate\Foundation\Application;
use Override;

abstract class SiteSearchTestCase extends PackagesTestCase
{
    /**
     * @param  Application  $app
     * @return class-string[]
     */
    #[Override]
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            SiteSearchServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(SiteSearchServiceProvider::$packageName);
    }
}
