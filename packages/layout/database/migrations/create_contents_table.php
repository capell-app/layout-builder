<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contents');
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contents', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->foreignId('type_id')->constrained();
            $table->foreignId('site_id')->nullable()->constrained()->cascadeOnDelete();
            $table->json('meta')->nullable();
            $table->unsignedInteger('order')->default(0)->index();
            $table->publishDates('publish');
            $this->draftsCreateSchema($table);
            $table->uuid('parent_uuid')->nullable()->constrained('contents', 'uuid')->nullOnDelete()->cascadeOnUpdate();
            $table->nestedSet();
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
                $table->index('meta->page_uuid', 'contents_page_uuid_index');
            }
        });
    }

    private function draftsCreateSchema(Blueprint $table): void
    {
        $uuid = config('drafts.column_names.uuid', 'uuid');
        $publishedAt = config('drafts.column_names.published_at', 'published_at');
        $isPublished = config('drafts.column_names.is_published', 'is_published');
        $isCurrent = config('drafts.column_names.is_current', 'is_current');
        $publisherMorphName = config('drafts.column_names.publisher_morph_name', 'publisher');

        $table->uuid($uuid)->index(); // custom not nullable
        $table->timestamp($publishedAt)->nullable();
        $table->boolean($isPublished)->default(false);
        $table->boolean($isCurrent)->default(false);
        $table->nullableMorphs($publisherMorphName);

        $table->index([$uuid, $isPublished, $isCurrent]);
    }
};
