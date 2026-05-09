<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_tracking_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->nullable()->index();
            $table->string('site_scope_key')->default('global')->index();
            $table->foreignId('email_recipient_id')->constrained('email_recipients')->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->string('type')->index();
            $table->text('destination_url')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_tracking_tokens');
    }
};
