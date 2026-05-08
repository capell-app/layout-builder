<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('page_seo_snapshots')) {
            return;
        }

        Schema::create('page_seo_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
            $table->unsignedTinyInteger('score')->default(0);
            $table->unsignedSmallInteger('critical_count')->default(0);
            $table->unsignedSmallInteger('warning_count')->default(0);
            $table->unsignedSmallInteger('notice_count')->default(0);
            $table->unsignedSmallInteger('passed_count')->default(0);
            $table->json('issue_keys')->nullable();
            $table->json('passed_check_keys')->nullable();
            $table->string('schema_status')->default('unknown');
            $table->string('robots_status')->default('unknown');
            $table->string('canonical_status')->default('unknown');
            $table->unsignedSmallInteger('internal_link_suggestions_count')->default(0);
            $table->string('search_console_status')->default('unknown');
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();

            $table->unique(['page_id', 'site_id', 'language_id'], 'page_seo_snapshot_unique_context');
            $table->index(['site_id', 'language_id', 'score']);
            $table->index(['site_id', 'critical_count', 'warning_count']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_seo_snapshots');
    }
};
