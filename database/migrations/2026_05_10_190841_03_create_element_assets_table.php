<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('layout_element_assets')) {
            return;
        }

        Schema::create('layout_element_assets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('workspace_id')->default(0)->index();
            $table->foreignId('layout_element_id')->constrained('elements')->cascadeOnDelete();
            $table->nullableMorphs('pageable');
            $table->string('container')->nullable();
            $table->unsignedInteger('occurrence')->nullable()->default(1);
            $table->uuidMorphs('asset');
            $table->unsignedInteger('order')->default(0)->index();
            $table->json('meta')->nullable();
            $table->userstamps();
            $table->timestamps();
            $table->index(['container', 'occurrence'], 'layout_element_assets_container_occurrence_index');
            $table->index(['pageable_type', 'pageable_id', 'occurrence'], 'layout_element_assets_pageable_occurrence_index');
            $table->index(['asset_type', 'asset_id'], 'layout_element_assets_resource_index');
            $table->unique(['pageable_type', 'pageable_id', 'layout_element_id', 'container', 'occurrence', 'asset_type', 'asset_id', 'workspace_id'], 'layout_element_assets_pageable_element_asset_index');
        });
    }

    public function down(): void
    {
        //
    }
};
