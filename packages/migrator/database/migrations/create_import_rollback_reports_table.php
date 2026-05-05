<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_rollback_reports', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('import_session_id')->constrained('import_sessions')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_filename')->nullable();
            $table->string('source_package_checksum')->nullable();
            $table->json('created_models')->nullable();
            $table->json('summary')->nullable();
            $table->text('manual_instructions');
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            $table->index(['import_session_id', 'executed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_rollback_reports');
    }
};
