<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_template_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->nullable()->index();
            $table->string('site_scope_key')->default('global')->index();
            $table->foreignId('email_template_id')->constrained('email_templates')->cascadeOnDelete();
            $table->foreignId('email_profile_id')->nullable()->constrained('email_profiles')->nullOnDelete();
            $table->string('locale')->nullable()->index();
            $table->string('status')->index();
            $table->unsignedInteger('version')->default(1);
            $table->string('subject');
            $table->string('preview_text')->nullable();
            $table->longText('html_body');
            $table->longText('text_body')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_template_variants');
    }
};
