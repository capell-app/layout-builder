<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Capell\BlockLibrary\Models\ContentBlock;
use Capell\Core\Models\Type;
use Illuminate\Contracts\Config\Repository;
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

        ContentBlock::unsetEventDispatcher();
        Type::unsetEventDispatcher();

        if (Schema::hasTable('types')) {
            $this->createContentBlockActionTables();

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

        $this->createContentBlockActionTables();
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
        $app->make(Repository::class)->set('view.paths', [
            __DIR__ . '/../resources/views',
        ]);
    }

    private function createContentBlockActionTables(): void
    {
        if (! Schema::hasTable('languages')) {
            Schema::create('languages', static function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('code', 12);
                $table->string('flag', 12)->nullable();
                $table->json('meta')->nullable();
                $table->json('admin')->nullable();
                $table->string('locale')->nullable();
                $table->unsignedInteger('order')->default(0)->index();
                $table->boolean('default')->index()->default(false);
                $table->boolean('status')->index()->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('block_library')) {
            Schema::create('block_library', static function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('workspace_id')->default(0)->index();
                $table->unsignedBigInteger('shadowed_by_workspace_id')->default(0)->index();
                $table->string('name');
                $table->foreignId('type_id')->constrained('types');
                $table->foreignId('site_id')->nullable();
                $table->json('meta')->nullable();
                $table->unsignedInteger('order')->default(0)->index();
                $table->timestamp('visible_from')->nullable();
                $table->timestamp('visible_until')->nullable();
                $table->unsignedInteger('_lft')->nullable()->index();
                $table->unsignedInteger('_rgt')->nullable()->index();
                $table->unsignedBigInteger('parent_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('sites')) {
            Schema::create('sites', static function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->unsignedBigInteger('type_id');
                $table->unsignedBigInteger('theme_id');
                $table->unsignedBigInteger('language_id');
                $table->json('meta')->nullable();
                $table->json('admin')->nullable();
                $table->unsignedInteger('order')->default(0)->index();
                $table->boolean('default')->index()->default(false);
                $table->boolean('status')->index()->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('translations')) {
            Schema::create('translations', static function (Blueprint $table): void {
                $table->id();
                $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
                $table->morphs('translatable');
                $table->string('title')->nullable();
                $table->longText('content')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->unique(['language_id', 'translatable_type', 'translatable_id'], 'translations_key_unique');
            });
        }
    }
}
