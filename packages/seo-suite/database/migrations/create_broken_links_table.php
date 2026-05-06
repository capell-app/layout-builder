<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('broken_links')) {
            return;
        }

        Schema::create('broken_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->string('target_url');
            $table->integer('http_status')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();

            $table->index(['page_id', 'http_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broken_links');
    }
};
