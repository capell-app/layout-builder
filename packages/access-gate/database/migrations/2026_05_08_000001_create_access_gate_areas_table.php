<?php

declare(strict_types=1);

use Capell\AccessGate\Support\AccessGateSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        AccessGateSchema::builder()->create('access_gate_areas', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('status')->index();
            $table->string('identity_mode')->index();
            $table->string('approval_strategy')->index();
            $table->unsignedInteger('approval_limit')->nullable();
            $table->unsignedInteger('grant_duration_days')->nullable();
            $table->string('registration_policy')->index();
            $table->string('token_policy')->index();
            $table->json('public_allowlist')->nullable();
            $table->json('claim_url_hosts')->nullable();
            $table->string('gate_view')->nullable();
            $table->json('metadata')->nullable();
            $table->string('discount_label')->nullable();
            $table->string('discount_code')->nullable();
            $table->timestamp('discount_expires_at')->nullable();
            $table->json('discount_metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        AccessGateSchema::builder()->dropIfExists('access_gate_areas');
    }
};
