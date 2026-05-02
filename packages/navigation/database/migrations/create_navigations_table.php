<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('navigations')) {
            return;
        }

        Schema::create('navigations', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('key')->index();
            $table->foreignId('type_id')->constrained();
            $table->foreignId('site_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('language_id')->nullable()->constrained()->cascadeOnDelete();
            $table->json('items')->nullable();
            $table->json('meta')->nullable();
            $table->visibleDates();
            $table->userstamps();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['site_id', 'language_id']);
            $table->unique(['key', 'site_id', 'language_id'], 'navigations_key_site_language_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('navigations');
    }
};
