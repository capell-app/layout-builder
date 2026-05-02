<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_creator_sessions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('site_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('status', ['in_progress', 'generating', 'review', 'submitted', 'abandoned'])
                ->default('in_progress');
            $table->tinyInteger('stage')->default(0)->unsigned();
            $table->text('intent')->nullable();
            $table->json('clarifications')->nullable();
            $table->json('layout_proposal')->nullable();
            $table->json('generated_output')->nullable();
            $table->json('ai_messages')->nullable();
            $table->unsignedBigInteger('ai_history_id')->nullable();
            $table->unsignedBigInteger('workspace_id')->nullable();
            $table->timestamps();

            $table->foreign('ai_history_id')
                ->references('id')
                ->on('ai_generation_histories')
                ->nullOnDelete();

            $table->index(['site_id', 'user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_creator_sessions');
    }
};
