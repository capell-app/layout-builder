<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspace_review_assignments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('workspace_id')->index();
            $table->nullableMorphs('reviewer');
            $table->string('required_for')->default('any');
            $table->string('decision')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['reviewer_type', 'reviewer_id', 'decision']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_review_assignments');
    }
};
