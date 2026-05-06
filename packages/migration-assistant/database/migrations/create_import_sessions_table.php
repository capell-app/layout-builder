<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_sessions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kind', 32)->index();
            $table->string('status', 32)->default('draft')->index();
            $table->string('source_environment')->nullable();
            $table->string('source_filename')->nullable();
            $table->string('source_package_path')->nullable();
            $table->string('source_package_checksum')->nullable();
            $table->string('working_dir')->nullable();
            $table->json('manifest')->nullable();
            $table->json('resolution_map')->nullable();
            $table->json('page_decisions')->nullable();
            $table->json('relation_decisions')->nullable();
            $table->json('validation_results')->nullable();
            $table->json('result_summary')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->userstamps();
            $table->timestamps();

            $table->index(['kind', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_sessions');
    }
};
