<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_plugin_audit_log', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('marketplace_plugin_id')->constrained('marketplace_plugins')->cascadeOnDelete();
            $table->string('action');
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->json('data')->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_plugin_audit_log');
    }
};
