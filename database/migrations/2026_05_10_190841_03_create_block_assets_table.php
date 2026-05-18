<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('block_assets')) {
            return;
        }

        if (Schema::hasTable('layout_element_assets')) {
            Schema::rename('layout_element_assets', 'block_assets');

            Schema::table('block_assets', function (Blueprint $table): void {
                if (Schema::hasColumn('block_assets', 'layout_element_id')) {
                    $table->renameColumn('layout_element_id', 'block_id');
                }
            });

            return;
        }

        Schema::create('block_assets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('workspace_id')->default(0)->index();
            $table->foreignId('block_id')->constrained('blocks')->cascadeOnDelete();
            $table->nullableMorphs('pageable');
            $table->string('container')->nullable();
            $table->unsignedInteger('occurrence')->nullable()->default(1);
            $table->uuidMorphs('asset');
            $table->unsignedInteger('order')->default(0)->index();
            $table->json('meta')->nullable();
            $table->userstamps();
            $table->timestamps();
            $table->index(['container', 'occurrence'], 'block_assets_container_occurrence_index');
            $table->index(['pageable_type', 'pageable_id', 'occurrence'], 'block_assets_pageable_occurrence_index');
            $table->index(['asset_type', 'asset_id'], 'block_assets_resource_index');
            $table->unique(['pageable_type', 'pageable_id', 'block_id', 'container', 'occurrence', 'asset_type', 'asset_id', 'workspace_id'], 'block_assets_pageable_block_asset_index');
        });
    }

    public function down(): void
    {
        //
    }
};
