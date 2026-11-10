<?php

declare(strict_types=1);

use Capell\Core\Support\TableName;
use Capell\Core\Support\UserKeyType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! TableName::existsAndIsOwned('layout_bulk_change_runs', basename(__FILE__, '.php'))) {
            Schema::create(TableName::resolve('layout_bulk_change_runs'), function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('status', 32)->index();
                $table->json('criteria');
                $table->json('operation');
                $table->json('summary')->nullable();
                $table->foreignId('created_by')->type(UserKeyType::columnType())->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->type(UserKeyType::columnType())->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('applied_by')->type(UserKeyType::columnType())->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('queued_by')->type(UserKeyType::columnType())->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('reverted_by')->type(UserKeyType::columnType())->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('queued_at')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('applied_at')->nullable();
                $table->timestamp('reverted_at')->nullable();
                $table->timestamps();
            });
        }

        if (TableName::existsAndIsOwned('layout_bulk_change_results', basename(__FILE__, '.php'))) {
            return;
        }

        Schema::create(TableName::resolve('layout_bulk_change_results'), function (Blueprint $table): void {
            $table->id();
            $table->foreignId('run_id')->constrained(TableName::resolve('layout_bulk_change_runs'))->cascadeOnDelete();
            $table->foreignId('layout_id')->nullable()->constrained(TableName::resolve('layouts'))->nullOnDelete();
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
            $table->timestamp('reverted_at')->nullable();
            $table->timestamps();
            $table->index(['run_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(TableName::resolve('layout_bulk_change_results'));
        Schema::dropIfExists(TableName::resolve('layout_bulk_change_runs'));
    }
};
