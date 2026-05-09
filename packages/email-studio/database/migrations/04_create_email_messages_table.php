<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->nullable()->index();
            $table->string('site_scope_key')->default('global')->index();
            $table->foreignId('email_profile_id')->constrained('email_profiles')->restrictOnDelete();
            $table->foreignId('email_template_id')->nullable()->constrained('email_templates')->nullOnDelete();
            $table->foreignId('email_template_variant_id')->nullable()->constrained('email_template_variants')->nullOnDelete();
            $table->string('status')->index();
            $table->string('subject');
            $table->string('preview_text')->nullable();
            $table->longText('rendered_html')->nullable();
            $table->longText('rendered_text')->nullable();
            $table->json('context_snapshot')->nullable();
            $table->json('headers')->nullable();
            $table->string('triggered_by_type')->nullable();
            $table->unsignedBigInteger('triggered_by_id')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_messages');
    }
};
