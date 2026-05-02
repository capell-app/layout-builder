<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('capell-analytics.tables.events', 'analytics_events');
        $visitsTableName = config('capell-analytics.tables.visits', 'analytics_visits');

        Schema::create($tableName, function (Blueprint $table) use ($visitsTableName): void {
            $table->id();
            $table->foreignId('visit_id')->nullable()->constrained($visitsTableName)->nullOnDelete();
            $table->unsignedBigInteger('site_id')->nullable()->index();
            $table->unsignedBigInteger('language_id')->nullable()->index();
            $table->string('type')->index();
            $table->string('url', 512)->index();
            $table->string('path', 512)->index();
            $table->string('title')->nullable();
            $table->dateTime('occurred_at')->index();
            $table->unsignedInteger('sequence');
            $table->string('event_name')->nullable()->index();
            $table->string('label')->nullable();
            $table->string('location')->nullable()->index();
            $table->string('target_selector')->nullable();
            $table->integer('viewport_x')->nullable();
            $table->integer('viewport_y')->nullable();
            $table->integer('document_x')->nullable();
            $table->integer('document_y')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('capell-analytics.tables.events', 'analytics_events'));
    }
};
