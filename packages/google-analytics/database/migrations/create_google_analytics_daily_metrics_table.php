<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('capell-google-analytics.tables.daily_metrics', 'google_analytics_daily_metrics');

        Schema::create($tableName, function (Blueprint $table): void {
            $table->id();
            $table->string('property_id');
            $table->date('metric_date');
            $table->unsignedInteger('total_users')->default(0);
            $table->unsignedInteger('sessions')->default(0);
            $table->unsignedInteger('screen_page_views')->default(0);
            $table->unsignedInteger('engaged_sessions')->default(0);
            $table->decimal('engagement_rate', 8, 4)->default(0);
            $table->decimal('average_session_duration', 10, 2)->default(0);
            $table->unsignedInteger('event_count')->default(0);
            $table->unsignedInteger('conversions')->default(0);
            $table->timestamps();

            $table->unique(['property_id', 'metric_date'], 'ga_daily_property_date_unique');
            $table->index(['property_id', 'metric_date'], 'ga_daily_property_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('capell-google-analytics.tables.daily_metrics', 'google_analytics_daily_metrics'));
    }
};
