<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Capell\Core\Models\Type;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;
use Sinnbeck\DomAssertions\DomAssertionsServiceProvider;

class ContentBlockRenderingTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Type::unsetEventDispatcher();

        if (Schema::hasTable('types')) {
            return;
        }

        Schema::create('types', static function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('type', 64);
            $table->string('key', 128);
            $table->string('group')->nullable();
            $table->json('meta')->nullable();
            $table->json('admin')->nullable();
            $table->unsignedInteger('order')->default(0)->index();
            $table->boolean('default')->default(false);
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['type', 'key']);
            $table->index(['type', 'default']);
            $table->index(['type', 'status']);
        });
    }

    /**
     * @param  Application  $app
     * @return class-string[]
     */
    protected function getPackageProviders(mixed $app): array
    {
        return [
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            DomAssertionsServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment(mixed $app): void
    {
        $app->make('config')->set('view.paths', [
            __DIR__ . '/../resources/views',
        ]);
    }
}
