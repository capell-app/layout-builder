<?php

declare(strict_types=1);

use Capell\Core\Database\Concerns\CreatesDraftsSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Kalnoy\Nestedset\NestedSet;

return new class extends Migration
{
    use CreatesDraftsSchema;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contents', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('workspace_id')->default(0)->index();
            $table->string('name');
            $table->foreignId('type_id')->constrained();
            $table->foreignId('site_id')->nullable()->constrained()->cascadeOnDelete();
            $table->json('meta')->nullable();
            $table->unsignedInteger('order')->default(0)->index();
            $table->visibleDates();
            $this->draftsCreateSchema($table);
            $table->foreignId('parent_id')->nullable()->constrained('contents')->nullOnDelete()->cascadeOnUpdate();
            $table->unsignedInteger(NestedSet::LFT)->default(0);
            $table->unsignedInteger(NestedSet::RGT)->default(0);
            $table->userstamps();
            $table->timestamps();
            $table->softDeletes();
            if (
                Schema::getConnection()->getDriverName() === 'pgsql' ||
                (
                    Schema::getConnection()->getDriverName() === 'mysql' &&
                    version_compare(DB::selectOne('select version() as v')->v, '5.8.0', '>=') &&
                    ! str_contains((string) DB::selectOne('select version() as v')->v, 'MariaDB')
                )
            ) {
                $table->index('meta->page_id', 'contents_page_id_index');
            }

            $table->index(['site_id', 'type_id', 'order']);
            $table->index(['site_id', 'type_id', 'parent_id']);
            $table->index(['site_id', 'type_id', 'visible_from', 'visible_until']);
            $table->index(['site_id', 'type_id', 'is_published', 'is_current']);
            $table->index(NestedSet::getDefaultColumns());
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contents');
    }
};
