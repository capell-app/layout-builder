<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publishing-studio', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('color', 32)->nullable();
            $table->string('status', 32)->default('open')->index();
            $table->string('kind', 32)->default('manual')->index();
            $table->unsignedBigInteger('base_version_id')->nullable()->index();
            $table->unsignedBigInteger('cloned_from_id')->nullable()->index();
            $table->json('settings')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('publish_at')->nullable()->index();
            $table->timestamp('unpublish_at')->nullable()->index();
            $table->timestamp('embargo_until')->nullable()->index();
            $table->timestamp('review_reminder_at')->nullable()->index();
            $table->timestamp('published_at')->nullable();
            $table->userstamps();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'updated_at']);
            $table->index(['status', 'publish_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publishing-studio');
    }
};
