<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('widget_assets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('widget_id')->constrained()->cascadeOnDelete();
            $table->morphs('pageable');
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
            $table->unique(['pageable_type', 'pageable_id', 'widget_id', 'container', 'occurrence', 'asset_type', 'asset_id'], 'pageable_widget_asset_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('widget_assets');
    }
};
