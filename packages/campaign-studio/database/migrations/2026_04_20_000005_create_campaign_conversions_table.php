<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('capell-campaign-studio.tables.conversions', 'campaign_conversions');
        $groupsTableName = config('capell-campaign-studio.tables.groups', 'campaign_groups');
        $landingPagesTableName = config('capell-campaign-studio.tables.landing_pages', 'campaign_landing_pages');
        $goalsTableName = config('capell-campaign-studio.tables.conversion_goals', 'campaign_conversion_goals');

        Schema::create($tableName, function (Blueprint $table) use ($goalsTableName, $groupsTableName, $landingPagesTableName): void {
            $table->id();
            $table->foreignId('campaign_group_id')->constrained($groupsTableName)->cascadeOnDelete();
            $table->foreignId('campaign_landing_page_id')->nullable()->constrained($landingPagesTableName)->nullOnDelete();
            $table->foreignId('campaign_conversion_goal_id')->constrained($goalsTableName)->cascadeOnDelete();
            $table->unsignedBigInteger('insights_visit_id')->nullable()->index();
            $table->unsignedBigInteger('insights_event_id')->nullable()->index();
            $table->nullableMorphs('source');
            $table->unsignedBigInteger('site_id')->nullable()->index();
            $table->unsignedBigInteger('language_id')->nullable()->index();
            $table->json('attribution')->nullable();
            $table->dateTime('converted_at')->index();
            $table->timestamps();
            $table->unique([
                'campaign_conversion_goal_id',
                'insights_visit_id',
                'insights_event_id',
                'source_type',
                'source_id',
            ], 'campaign_conversions_goal_visit_event_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('capell-campaign-studio.tables.conversions', 'campaign_conversions'));
    }
};
