<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('versions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('number')->index();
            $table->string('name')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_live')->default(false);
            $table->json('manifest');
            $table->foreignId('source_workspace_id')->nullable()->constrained('workspaces')->nullOnDelete();
            $table->foreignId('rollback_of_version_id')->nullable()->constrained('versions')->nullOnDelete();
            $table->nullableMorphs('published_by');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['is_live', 'number']);
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('versions');
    }
};
