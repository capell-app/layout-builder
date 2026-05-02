<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('capell-analytics.tables.visits', 'analytics_visits');

        Schema::create($tableName, function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('site_id')->nullable()->index();
            $table->unsignedBigInteger('language_id')->nullable()->index();
            $table->string('consent_region');
            $table->string('consent_status');
            $table->text('landing_url');
            $table->text('referrer_url')->nullable();
            $table->string('utm_source')->nullable()->index();
            $table->string('utm_medium')->nullable()->index();
            $table->string('utm_campaign')->nullable()->index();
            $table->string('ip_hash')->nullable();
            $table->string('user_agent_hash')->nullable();
            $table->dateTime('started_at')->index();
            $table->dateTime('last_seen_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('capell-analytics.tables.visits', 'analytics_visits'));
    }
};
