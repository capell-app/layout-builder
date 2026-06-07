<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('layout_bulk_change_runs')) {
            Schema::create('layout_bulk_change_runs', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('status', 32)->index();
                $table->json('criteria');
                $table->json('operation');
                $table->json('summary')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('applied_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('applied_at')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('layout_bulk_change_results')) {
            return;
        }

        Schema::create('layout_bulk_change_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('run_id')->constrained('layout_bulk_change_runs')->cascadeOnDelete();
            $table->foreignId('layout_id')->nullable()->constrained('layouts')->nullOnDelete();
            $table->unsignedInteger('page_count')->default(0);
            $table->string('status', 32)->index();
            $table->string('original_container_hash', 64)->nullable();
            $table->string('proposed_container_hash', 64)->nullable();
            $table->json('original_containers')->nullable();
            $table->json('proposed_containers')->nullable();
            $table->json('changes')->nullable();
            $table->json('warnings')->nullable();
            $table->text('skipped_reason')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
            $table->index(['run_id', 'status']);
        });
    }

    public function down(): void
    {
        //
    }
};
