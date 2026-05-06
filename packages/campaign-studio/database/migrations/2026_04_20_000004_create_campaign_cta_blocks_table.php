<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('capell-campaign-studio.tables.cta_blocks', 'campaign_cta_blocks');
        $groupsTableName = config('capell-campaign-studio.tables.groups', 'campaign_groups');

        Schema::create($tableName, function (Blueprint $table) use ($groupsTableName): void {
            $table->id();
            $table->foreignId('campaign_group_id')->constrained($groupsTableName)->cascadeOnDelete();
            $table->unsignedBigInteger('site_id')->nullable()->index();
            $table->string('name');
            $table->string('key')->index();
            $table->string('headline')->nullable();
            $table->text('body')->nullable();
            $table->json('actions')->nullable();
            $table->json('default_utm')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['campaign_group_id', 'key', 'deleted_at'], 'campaign_cta_group_key_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('capell-campaign-studio.tables.cta_blocks', 'campaign_cta_blocks'));
    }
};
