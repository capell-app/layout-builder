<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->nullable()->index();
            $table->string('site_scope_key')->default('global')->index();
            $table->string('key')->index();
            $table->string('name');
            $table->string('status')->index();
            $table->text('description')->nullable();
            $table->json('variables')->nullable();
            $table->timestamps();
            $table->unique(['site_scope_key', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
