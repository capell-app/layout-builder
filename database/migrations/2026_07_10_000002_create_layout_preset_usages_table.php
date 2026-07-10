<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('layout_preset_usages')) {
            return;
        }

        Schema::create('layout_preset_usages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('preset_id')->constrained('layout_presets')->cascadeOnDelete();
            $table->uuid('preset_item_id');
            $table->foreignId('layout_id')->constrained('layouts')->cascadeOnDelete();
            $table->string('container_key', 128);
            $table->timestamp('layout_updated_at')->nullable();
            $table->timestamps();
            $table->unique(['layout_id', 'container_key']);
            $table->unique(['preset_id', 'preset_item_id', 'layout_id', 'container_key'], 'layout_preset_usage_identity');
            $table->index(['preset_id', 'preset_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('layout_preset_usages');
    }
};
