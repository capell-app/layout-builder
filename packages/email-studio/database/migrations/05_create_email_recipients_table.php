<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_recipients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->nullable()->index();
            $table->string('site_scope_key')->default('global')->index();
            $table->foreignId('email_message_id')->constrained('email_messages')->cascadeOnDelete();
            $table->string('type')->default('to');
            $table->string('email')->index();
            $table->string('normalized_email')->index();
            $table->string('email_hash', 64)->index();
            $table->string('name')->nullable();
            $table->string('status')->index();
            $table->string('provider_message_id')->nullable()->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('bounced_at')->nullable();
            $table->timestamp('complained_at')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamp('suppressed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_recipients');
    }
};
