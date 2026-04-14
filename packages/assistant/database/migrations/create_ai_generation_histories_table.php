<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_generation_histories', function (Blueprint $table): void {
            $table->id();
            $table->string('action');
            $table->string('model')->nullable();
            $table->text('input')->nullable();
            $table->text('output')->nullable();
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);
            $table->float('duration')->default(0);
            $table->json('metadata')->nullable();
            $table->nullableMorphs('pageable');
            $table->unsignedBigInteger('language_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_generation_histories');
    }
};
