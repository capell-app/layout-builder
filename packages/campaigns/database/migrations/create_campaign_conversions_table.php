<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('capell-campaigns.tables.conversions', 'campaign_conversions');
        $groupsTableName = config('capell-campaigns.tables.groups', 'campaign_groups');
        $landingPagesTableName = config('capell-campaigns.tables.landing_pages', 'campaign_landing_pages');
        $goalsTableName = config('capell-campaigns.tables.conversion_goals', 'campaign_conversion_goals');

        Schema::create($tableName, function (Blueprint $table) use ($goalsTableName, $groupsTableName, $landingPagesTableName): void {
            $table->id();
            $table->foreignId('campaign_group_id')->constrained($groupsTableName)->cascadeOnDelete();
            $table->foreignId('campaign_landing_page_id')->nullable()->constrained($landingPagesTableName)->nullOnDelete();
            $table->foreignId('campaign_conversion_goal_id')->constrained($goalsTableName)->cascadeOnDelete();
            $table->unsignedBigInteger('analytics_visit_id')->nullable()->index();
            $table->unsignedBigInteger('analytics_event_id')->nullable()->index();
            $table->unsignedBigInteger('site_id')->nullable()->index();
            $table->unsignedBigInteger('language_id')->nullable()->index();
            $table->json('attribution')->nullable();
            $table->timestamp('converted_at')->index();
            $table->timestamps();
            $table->unique([
                'campaign_conversion_goal_id',
                'analytics_visit_id',
                'analytics_event_id',
            ], 'campaign_conversions_goal_visit_event_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('capell-campaigns.tables.conversions', 'campaign_conversions'));
    }
};
