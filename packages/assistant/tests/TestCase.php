<?php

declare(strict_types=1);

namespace Capell\Assistant\Tests;

use Capell\Assistant\Providers\AssistantServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            AssistantServiceProvider::class,
        ];
    }
}
