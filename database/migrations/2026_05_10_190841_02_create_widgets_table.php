<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('widgets')) {
            return;
        }

        Schema::create('widgets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('workspace_id')->default(0)->index();
            $table->string('name');
            $table->foreignId('blueprint_id')->constrained();
            $table->string('key', 128);
            $table->visibleDates();
            $table->longText('content')->nullable();
            $table->json('meta')->nullable();
            $table->json('admin')->nullable();
            $table->unsignedInteger('order')->default(0)->index();
            $table->boolean('status')->index()->default(1);
            $table->userstamps();
            $table->timestamps();
            $table->unique(['key', 'deleted_at', 'workspace_id']);
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        //
    }
};
