<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->nullable()->index();
            $table->string('site_scope_key')->default('global')->index();
            $table->string('name');
            $table->string('provider')->index();
            $table->string('webhook_endpoint_token_hash')->nullable()->unique();
            $table->string('from_email');
            $table->string('from_name')->nullable();
            $table->string('reply_to_email')->nullable();
            $table->string('reply_to_name')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('track_opens')->default(true);
            $table->boolean('track_clicks')->default(true);
            $table->json('provider_settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_profiles');
    }
};
