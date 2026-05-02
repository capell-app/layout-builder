<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_creator_contexts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('site_id');
            $table->enum('tone', ['professional', 'friendly', 'playful', 'authoritative'])
                ->default('professional');
            $table->string('industry')->default('');
            $table->text('brand_voice_notes')->nullable();
            $table->text('target_audience')->nullable();
            $table->timestamps();

            $table->unique('site_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_creator_contexts');
    }
};
