<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $eventsTableName = config('capell-insights.tables.events', 'insights_events');
        $visitsTableName = config('capell-insights.tables.visits', 'insights_visits');

        Schema::table($eventsTableName, function (Blueprint $table): void {
            $table->unsignedBigInteger('legacy_page_view_id')->nullable()->index('insights_events_legacy_page_view_index');
            $table->index(['type', 'occurred_at'], 'insights_events_type_occurred_index');
            $table->index(['site_id', 'type', 'occurred_at'], 'insights_events_site_type_occurred_index');
            $table->index(['path', 'type', 'occurred_at'], 'insights_events_path_type_occurred_index');
            $table->index(['visit_id', 'sequence'], 'insights_events_visit_sequence_index');
        });

        Schema::table($visitsTableName, function (Blueprint $table): void {
            $table->string('legacy_session_id', 64)->nullable()->index('insights_visits_legacy_session_index');
        });
    }

    public function down(): void
    {
        $eventsTableName = config('capell-insights.tables.events', 'insights_events');
        $visitsTableName = config('capell-insights.tables.visits', 'insights_visits');

        Schema::table($eventsTableName, function (Blueprint $table): void {
            $table->dropIndex('insights_events_legacy_page_view_index');
            $table->dropIndex('insights_events_type_occurred_index');
            $table->dropIndex('insights_events_site_type_occurred_index');
            $table->dropIndex('insights_events_path_type_occurred_index');
            $table->dropIndex('insights_events_visit_sequence_index');
            $table->dropColumn('legacy_page_view_id');
        });

        Schema::table($visitsTableName, function (Blueprint $table): void {
            $table->dropIndex('insights_visits_legacy_session_index');
            $table->dropColumn('legacy_session_id');
        });
    }
};
