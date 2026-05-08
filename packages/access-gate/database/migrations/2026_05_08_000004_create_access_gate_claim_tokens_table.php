<?php

declare(strict_types=1);

use Capell\AccessGate\Support\AccessGateSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        AccessGateSchema::builder()->create('access_gate_claim_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('access_area_id')->constrained('access_gate_areas')->cascadeOnDelete();
            $table->foreignId('registration_id')->nullable()->constrained('access_gate_registrations')->nullOnDelete();
            $table->foreignId('grant_id')->nullable()->constrained('access_gate_grants')->nullOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->string('status')->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('consumed_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['access_area_id', 'status']);
        });
    }

    public function down(): void
    {
        AccessGateSchema::builder()->dropIfExists('access_gate_claim_tokens');
    }
};
