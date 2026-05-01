<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('capell-campaigns.tables.conversion_goals', 'campaign_conversion_goals');
        $groupsTableName = config('capell-campaigns.tables.groups', 'campaign_groups');

        Schema::create($tableName, function (Blueprint $table) use ($groupsTableName): void {
            $table->id();
            $table->foreignId('campaign_group_id')->constrained($groupsTableName)->cascadeOnDelete();
            $table->unsignedBigInteger('site_id')->nullable()->index();
            $table->string('name');
            $table->string('key')->index();
            $table->string('type')->index();
            $table->string('target')->nullable()->index();
            $table->decimal('value_amount', 12, 2)->nullable();
            $table->boolean('is_primary')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['campaign_group_id', 'key', 'deleted_at'], 'campaign_goal_group_key_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('capell-campaigns.tables.conversion_goals', 'campaign_conversion_goals'));
    }
};
