<?php

declare(strict_types=1);

use Capell\AccessGate\Support\AccessGateSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        AccessGateSchema::builder()->create('access_gate_browser_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('access_area_id')->constrained('access_gate_areas')->cascadeOnDelete();
            $table->foreignId('grant_id')->constrained('access_gate_grants')->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->string('status')->index();
            $table->string('ip_hash', 64)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('last_used_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['access_area_id', 'status']);
            $table->index(['access_area_id', 'grant_id']);
        });
    }

    public function down(): void
    {
        AccessGateSchema::builder()->dropIfExists('access_gate_browser_tokens');
    }
};
