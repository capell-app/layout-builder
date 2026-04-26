<?php

declare(strict_types=1);

namespace Capell\MediaCurator\Tests;

use Awcodes\Curator\CuratorServiceProvider;
use Capell\MediaCurator\CapellMediaCuratorServiceProvider;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Storage;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

/**
 * Base test case for the capell-app/media-curator plugin.
 *
 * Boots the Curator service provider and the Capell bridge provider,
 * creates an in-memory SQLite database with the `curator` table and a
 * minimal `test_curator_owners` fixture table, and fakes the `public`
 * storage disk so uploaded files never touch the filesystem.
 */
class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        if (! class_exists(CuratorServiceProvider::class)) {
            $this->markTestSkipped('awcodes/filament-curator is not installed.');
        }

        parent::setUp();

        Storage::fake('public');
    }

    /**
     * @param  Application  $app
     * @return class-string[]
     */
    protected function getPackageProviders($app): array
    {
        if (! class_exists(CuratorServiceProvider::class)) {
            return [LivewireServiceProvider::class];
        }

        return [
            LivewireServiceProvider::class,
            CuratorServiceProvider::class,
            CapellMediaCuratorServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app->make(Repository::class)->set('database.default', 'testing');
        $app->make(Repository::class)->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Curator reads its glide token from the environment.
        $app->make(Repository::class)->set('curator.glide_token', 'test-token');
    }

    protected function defineDatabaseMigrations(): void
    {
        // Create the Curator media table (mirrors the migration stub).
        $this->app->make(ConnectionResolverInterface::class)->getSchemaBuilder()->create('curator', function (Blueprint $table): void {
            $table->id();
            $table->string('disk');
            $table->string('directory')->nullable();
            $table->string('visibility')->default('public');
            $table->string('name');
            $table->string('path')->index();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('size')->nullable();
            $table->string('type');
            $table->string('ext');
            $table->string('alt')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->text('caption')->nullable();
            $table->text('pretty_name')->nullable();
            $table->text('exif')->nullable();
            $table->longText('curations')->nullable();
            $table->timestamps();
        });

        // Owner fixture table — one FK column per media collection.
        $this->app->make(ConnectionResolverInterface::class)->getSchemaBuilder()->create('test_curator_owners', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('image_id')->nullable();
            $table->timestamps();
        });
    }
}
