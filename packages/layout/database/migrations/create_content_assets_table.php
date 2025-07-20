<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_assets');
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('content_assets', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('content_id')->index()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('order')->default(0)->index();
            $table->uuidMorphs('asset');
            $table->userstamps();
            $table->timestamps();
            $table->unique(['content_id', 'asset_type', 'asset_id'], 'content_resource_index');
        });
    }
};
