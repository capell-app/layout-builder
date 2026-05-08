<?php

declare(strict_types=1);

namespace Capell\AccessGate\Tests;

use Capell\AccessGate\Providers\AccessGateServiceProvider;
use Capell\AccessGate\Tests\Support\FakePageCacheMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders(mixed $app): array
    {
        return [
            AccessGateServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('access-gate.middleware.page_cache_aliases', [
            'page-cache',
            FakePageCacheMiddleware::class,
        ]);
        $app['config']->set('access-gate.status_endpoint_enabled', true);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
