<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->nullable()->index();
            $table->unsignedBigInteger('workspace_id')->default(0)->index();
            $table->unsignedBigInteger('shadowed_by_workspace_id')->default(0)->index();
            $table->string('name');
            $table->foreignId('type_id')->constrained();
            $table->foreignId('layout_id')->constrained();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->json('meta')->nullable();
            $table->visibleDates();
            $table->unsignedInteger('order')->default(0);
            $table->userstamps();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['site_id', 'type_id']);
            $table->index(['site_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
