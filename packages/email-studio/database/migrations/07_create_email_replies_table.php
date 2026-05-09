<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_replies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->nullable()->index();
            $table->string('site_scope_key')->default('global')->index();
            $table->foreignId('email_message_id')->constrained('email_messages')->cascadeOnDelete();
            $table->foreignId('email_recipient_id')->nullable()->constrained('email_recipients')->nullOnDelete();
            $table->string('provider_message_id')->nullable()->index();
            $table->string('from_email')->index();
            $table->string('normalized_from_email')->index();
            $table->string('from_email_hash', 64)->index();
            $table->string('from_name')->nullable();
            $table->string('subject')->nullable();
            $table->longText('body')->nullable();
            $table->json('provider_payload')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_replies');
    }
};
