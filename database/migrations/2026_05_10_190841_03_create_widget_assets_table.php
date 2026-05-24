<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('widget_assets')) {
            return;
        }

        Schema::create('widget_assets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('workspace_id')->default(0)->index();
            $table->foreignId('widget_id')->constrained('widgets')->cascadeOnDelete();
            $table->nullableMorphs('pageable');
            $table->string('container')->nullable();
            $table->unsignedInteger('occurrence')->nullable()->default(1);
            $table->uuidMorphs('asset');
            $table->unsignedInteger('order')->default(0)->index();
            $table->json('meta')->nullable();
            $table->userstamps();
            $table->timestamps();
            $table->index(['container', 'occurrence'], 'widget_assets_container_occurrence_index');
            $table->index(['pageable_type', 'pageable_id', 'occurrence'], 'widget_assets_pageable_occurrence_index');
            $table->index(['asset_type', 'asset_id'], 'widget_assets_resource_index');
            $table->unique(['pageable_type', 'pageable_id', 'widget_id', 'container', 'occurrence', 'asset_type', 'asset_id', 'workspace_id'], 'widget_assets_pageable_widget_asset_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('widget_assets');
    }
};
