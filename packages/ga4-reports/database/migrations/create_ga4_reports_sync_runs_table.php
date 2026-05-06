<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('capell-ga4-reports.tables.sync_runs', 'ga4_reports_sync_runs');

        Schema::create($tableName, function (Blueprint $table): void {
            $table->id();
            $table->string('property_id')->index();
            $table->string('status')->index();
            $table->date('window_start')->index();
            $table->date('window_end')->index();
            $table->unsignedInteger('daily_rows')->default(0);
            $table->unsignedInteger('page_rows')->default(0);
            $table->text('error_message')->nullable();
            $table->dateTime('started_at')->index();
            $table->dateTime('finished_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('capell-ga4-reports.tables.sync_runs', 'ga4_reports_sync_runs'));
    }
};
