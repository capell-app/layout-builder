<?php

declare(strict_types=1);

namespace Capell\Search\Tests;

use Capell\Core\Facades\CapellCore;
use Capell\Search\Providers\SearchServiceProvider;
use Capell\Tests\Packages\PackagesTestCase;
use Illuminate\Foundation\Application;
use Override;

abstract class SearchTestCase extends PackagesTestCase
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
            SearchServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(SearchServiceProvider::$packageName);
    }
}
