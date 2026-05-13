<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('capell-public-actions.tables.dispatch_attempts', 'public_action_dispatch_attempts'), function (Blueprint $table): void {
            $table->id();
            $table->foreignId('public_action_submission_id');
            $table->foreignId('public_action_destination_id')->nullable();
            $table->string('adapter')->index();
            $table->string('status')->index();
            $table->unsignedInteger('attempt')->default(1);
            $table->string('request_hash')->index();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->text('response_summary')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('dispatched_at')->nullable()->index();
            $table->timestamps();

            $table->foreign('public_action_submission_id', 'public_action_dispatch_submission_fk')
                ->references('id')
                ->on(config('capell-public-actions.tables.submissions', 'public_action_submissions'))
                ->cascadeOnDelete();
            $table->foreign('public_action_destination_id', 'public_action_dispatch_destination_fk')
                ->references('id')
                ->on(config('capell-public-actions.tables.destinations', 'public_action_destinations'))
                ->nullOnDelete();
            $table->index(['public_action_submission_id', 'status'], 'public_action_dispatch_submission_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('capell-public-actions.tables.dispatch_attempts', 'public_action_dispatch_attempts'));
    }
};
