<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspace_approvals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained('publishing-studio')->cascadeOnDelete();
            $table->nullableMorphs('actionable');
            $table->unsignedTinyInteger('level');
            $table->string('action', 32);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_approvals');
    }
};
