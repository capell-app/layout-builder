<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspace_field_comments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('workspace_id')->index();
            $table->string('entity_type');
            $table->string('entity_uuid', 64);
            $table->string('field_path');
            $table->nullableMorphs('author');
            $table->text('body');
            $table->timestamp('resolved_at')->nullable()->index();
            $table->timestamps();

            $table->index(['entity_type', 'entity_uuid', 'field_path'], 'wfc_entity_field_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_field_comments');
    }
};
