<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * H6 placeholder table for full-environment restore tracking. Mirrors
 * the minimal shape of import_sessions so admin-side morph maps and
 * navigation stubs can reference BackupRestore before phase H6 lands.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_restores', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('draft')->index();
            $table->string('source_archive_path')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_restores');
    }
};
