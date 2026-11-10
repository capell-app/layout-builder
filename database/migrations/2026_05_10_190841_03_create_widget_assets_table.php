<?php

declare(strict_types=1);

use Capell\Core\Support\TableName;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (TableName::existsAndIsOwned('widget_assets', basename(__FILE__, '.php'))) {
            return;
        }

        Schema::create(TableName::resolve('widget_assets'), function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('workspace_id')->default(0)->index();
            $table->foreignId('widget_id')->constrained(TableName::resolve('widgets'))->cascadeOnDelete();
            $table->string('pageable_type', 100)->nullable();
            $table->unsignedBigInteger('pageable_id')->nullable();
            $table->string('container', 100)->nullable();
            $table->unsignedInteger('occurrence')->nullable()->default(1);
            $table->string('asset_type', 100);
            $table->uuid('asset_id');
            $table->unsignedInteger('order')->default(0)->index();
            $table->json('meta')->nullable();
            $table->userstamps();
            $table->timestamps();
            $table->index(['container', 'occurrence'], TableName::namedIndex('widget_assets', 'widget_assets_container_occurrence_index'));
            $table->index(['pageable_type', 'pageable_id', 'occurrence'], TableName::namedIndex('widget_assets', 'widget_assets_pageable_occurrence_index'));
            $table->index(['asset_type', 'asset_id'], TableName::namedIndex('widget_assets', 'widget_assets_resource_index'));
            $table->unique(['pageable_type', 'pageable_id', 'widget_id', 'container', 'occurrence', 'asset_type', 'asset_id', 'workspace_id'], TableName::namedIndex('widget_assets', 'widget_assets_pageable_widget_asset_index'));
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(TableName::resolve('widget_assets'));
    }
};
