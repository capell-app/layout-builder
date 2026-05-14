<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('layout_module_assets')) {
            return;
        }

        Schema::create('layout_module_assets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('workspace_id')->default(0)->index();
            $table->foreignId('layout_module_id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('pageable');
            $table->string('container')->nullable();
            $table->unsignedInteger('occurrence')->nullable()->default(1);
            $table->uuidMorphs('asset');
            $table->unsignedInteger('order')->default(0)->index();
            $table->json('meta')->nullable();
            $table->userstamps();
            $table->timestamps();
            $table->index(['container', 'occurrence'], 'container_occurrence_index');
            $table->index(['pageable_type', 'pageable_id', 'occurrence'], 'pageable_occurrence_index');
            $table->index(['asset_type', 'asset_id'], 'resource_index');
            $table->unique(['pageable_type', 'pageable_id', 'layout_module_id', 'container', 'occurrence', 'asset_type', 'asset_id', 'workspace_id'], 'pageable_widget_asset_index');
        });
    }

    public function down(): void
    {
        //
    }
};
