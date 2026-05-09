<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->nullable()->index();
            $table->string('site_scope_key')->default('global')->index();
            $table->foreignId('email_profile_id')->nullable()->constrained('email_profiles')->nullOnDelete();
            $table->foreignId('email_message_id')->nullable()->constrained('email_messages')->cascadeOnDelete();
            $table->foreignId('email_recipient_id')->nullable()->constrained('email_recipients')->cascadeOnDelete();
            $table->string('type')->index();
            $table->string('provider_event_id')->nullable()->index();
            $table->string('idempotency_key');
            $table->json('provider_payload')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();
            $table->unique(['email_profile_id', 'idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_events');
    }
};
