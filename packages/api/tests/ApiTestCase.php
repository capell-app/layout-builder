<?php

declare(strict_types=1);

namespace Capell\Api\Tests;

use Capell\Api\Providers\ApiServiceProvider;
use Capell\Core\Facades\CapellCore;
use Capell\Tests\Packages\PackagesTestCase;
use Illuminate\Foundation\Application;
use Override;

abstract class ApiTestCase extends PackagesTestCase
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
            ApiServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(ApiServiceProvider::$packageName);
    }
}
