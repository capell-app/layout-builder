<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('capell-ga4-reports.tables.page_metrics', 'ga4_reports_page_metrics');

        Schema::create($tableName, function (Blueprint $table): void {
            $table->id();
            $table->string('property_id');
            $table->date('metric_date');
            $table->string('page_path', 512);
            $table->string('page_title')->nullable();
            $table->unsignedInteger('total_users')->default(0);
            $table->unsignedInteger('sessions')->default(0);
            $table->unsignedInteger('screen_page_views')->default(0);
            $table->unsignedInteger('event_count')->default(0);
            $table->unsignedInteger('conversions')->default(0);
            $table->timestamps();

            $table->unique(['property_id', 'metric_date', 'page_path'], 'ga_pages_property_date_path_unique');
            $table->index(['property_id', 'metric_date'], 'ga_pages_property_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('capell-ga4-reports.tables.page_metrics', 'ga4_reports_page_metrics'));
    }
};
