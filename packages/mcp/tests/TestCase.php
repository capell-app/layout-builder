<?php

declare(strict_types=1);

namespace Capell\Mcp\Tests;

use Capell\Mcp\Providers\CapellMcpServiceProvider;
use Capell\Mcp\Tests\Fixtures\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Relation::morphMap([
            'mcp_user' => User::class,
        ]);
    }

    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        $providers = [
            CapellMcpServiceProvider::class,
        ];

        if (class_exists('Laravel\\Mcp\\Server\\McpServiceProvider')) {
            array_unshift($providers, 'Laravel\\Mcp\\Server\\McpServiceProvider');
        }

        return $providers;
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }
}
