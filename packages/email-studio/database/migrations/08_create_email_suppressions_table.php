<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_suppressions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->nullable()->index();
            $table->string('site_scope_key')->default('global')->index();
            $table->string('email')->index();
            $table->string('normalized_email')->index();
            $table->string('email_hash', 64)->index();
            $table->string('reason')->index();
            $table->string('source')->default('manual')->index();
            $table->text('notes')->nullable();
            $table->timestamp('suppressed_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
            $table->unique(['site_scope_key', 'email_hash', 'reason', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_suppressions');
    }
};
