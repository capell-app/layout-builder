<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('capell-campaigns.tables.landing_pages', 'campaign_landing_pages');
        $groupsTableName = config('capell-campaigns.tables.groups', 'campaign_groups');

        Schema::create($tableName, function (Blueprint $table) use ($groupsTableName): void {
            $table->id();
            $table->foreignId('campaign_group_id')->constrained($groupsTableName)->cascadeOnDelete();
            $table->unsignedBigInteger('page_id')->index();
            $table->string('headline')->nullable();
            $table->unsignedBigInteger('primary_goal_id')->nullable()->index();
            $table->string('utm_content')->nullable()->index();
            $table->string('utm_term')->nullable()->index();
            $table->boolean('is_primary')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('capell-campaigns.tables.landing_pages', 'campaign_landing_pages'));
    }
};
