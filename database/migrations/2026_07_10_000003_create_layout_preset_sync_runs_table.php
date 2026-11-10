<?php

declare(strict_types=1);

use Capell\Core\Support\TableName;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (TableName::existsAndIsOwned('layout_preset_sync_runs', basename(__FILE__, '.php'))) {
            return;
        }

        Schema::create(TableName::resolve('layout_preset_sync_runs'), function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('preset_id')->constrained(TableName::resolve('layout_presets'))->cascadeOnDelete();
            $table->unsignedInteger('revision');
            $table->unsignedBigInteger('initiated_by')->nullable()->index();
            $table->foreignId('excluded_usage_id')->nullable()->constrained(TableName::resolve('layout_preset_usages'))->nullOnDelete();
            $table->string('status', 48)->index();
            $table->json('summary')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['preset_id', 'revision']);
        });

        Schema::create(TableName::resolve('layout_preset_sync_results'), function (Blueprint $table): void {
            $table->id();
            $table->foreignId('run_id')->constrained(TableName::resolve('layout_preset_sync_runs'))->cascadeOnDelete();
            $table->foreignId('usage_id')->nullable()->constrained(TableName::resolve('layout_preset_usages'))->nullOnDelete();
            $table->foreignId('layout_id')->nullable()->constrained(TableName::resolve('layouts'))->nullOnDelete();
            $table->string('container_key', 128)->nullable();
            $table->string('status', 48)->index();
            $table->string('reason')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();
            $table->unique(['run_id', 'usage_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(TableName::resolve('layout_preset_sync_results'));
        Schema::dropIfExists(TableName::resolve('layout_preset_sync_runs'));
    }
};
