<?php

declare(strict_types=1);

use Capell\AccessGate\Support\AccessGateSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        AccessGateSchema::builder()->create('access_gate_registrations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('access_area_id')->constrained('access_gate_areas')->cascadeOnDelete();
            $table->string('email')->index();
            $table->string('email_normalized')->index();
            $table->string('single_registration_key')->nullable()->unique();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('status')->index();
            $table->string('requested_url', 2048)->nullable();
            $table->string('requested_host')->nullable()->index();
            $table->unsignedInteger('position')->nullable();
            $table->json('field_values')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('requested_at')->nullable()->index();
            $table->timestamp('approved_at')->nullable()->index();
            $table->timestamp('rejected_at')->nullable()->index();
            $table->timestamp('claimed_at')->nullable()->index();
            $table->timestamp('expired_at')->nullable()->index();
            $table->timestamps();

            $table->index(['access_area_id', 'status']);
            $table->index(['access_area_id', 'email']);
            $table->index(['access_area_id', 'user_id']);
        });
    }

    public function down(): void
    {
        AccessGateSchema::builder()->dropIfExists('access_gate_registrations');
    }
};
