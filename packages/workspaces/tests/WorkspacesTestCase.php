<?php

declare(strict_types=1);

namespace Capell\Workspaces\Tests;

use Capell\Tests\AbstractTestCase;
use Capell\Workspaces\Providers\WorkspacesServiceProvider;
use Illuminate\Foundation\Application;
use Override;

class WorkspacesTestCase extends AbstractTestCase
{
    #[Override]
    protected function getPackageServiceName(): string
    {
        return 'workspaces';
    }

    /**
     * @param  Application  $app
     * @return class-string[]
     */
    #[Override]
    protected function getPackageProviders(mixed $app): array
    {
        $providers = parent::getPackageProviders($app);

        return array_merge($providers, [
            WorkspacesServiceProvider::class,
        ]);
    }
}
