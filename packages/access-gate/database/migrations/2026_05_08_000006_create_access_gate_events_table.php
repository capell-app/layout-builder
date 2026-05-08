<?php

declare(strict_types=1);

use Capell\AccessGate\Support\AccessGateSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        AccessGateSchema::builder()->create('access_gate_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('access_area_id')->nullable()->constrained('access_gate_areas')->nullOnDelete();
            $table->foreignId('registration_id')->nullable()->constrained('access_gate_registrations')->nullOnDelete();
            $table->foreignId('grant_id')->nullable()->constrained('access_gate_grants')->nullOnDelete();
            $table->foreignId('claim_token_id')->nullable()->constrained('access_gate_claim_tokens')->nullOnDelete();
            $table->foreignId('browser_token_id')->nullable()->constrained('access_gate_browser_tokens')->nullOnDelete();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('type')->index();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('payload')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->index(['access_area_id', 'type']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        AccessGateSchema::builder()->dropIfExists('access_gate_events');
    }
};
