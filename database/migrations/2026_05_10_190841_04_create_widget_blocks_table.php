<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('widget_blocks')) {
            return;
        }

        Schema::create('widget_blocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('widget_id')->constrained('widgets')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('key', 128);
            $table->string('type', 128)->default('content')->index();
            $table->longText('content')->nullable();
            $table->json('meta')->nullable();
            $table->json('admin')->nullable();
            $table->unsignedInteger('order')->default(0)->index();
            $table->boolean('status')->index()->default(1);
            $table->userstamps();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['widget_id', 'key', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('widget_blocks');
    }
};
