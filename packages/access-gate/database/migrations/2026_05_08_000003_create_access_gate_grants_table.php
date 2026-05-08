<?php

declare(strict_types=1);

use Capell\AccessGate\Support\AccessGateSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        AccessGateSchema::builder()->create('access_gate_grants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('access_area_id')->constrained('access_gate_areas')->cascadeOnDelete();
            $table->foreignId('registration_id')->nullable()->constrained('access_gate_registrations')->nullOnDelete();
            $table->string('subject_type')->index();
            $table->string('subject_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->string('status')->index();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->string('discount_label')->nullable();
            $table->string('discount_code')->nullable();
            $table->timestamp('discount_expires_at')->nullable();
            $table->json('discount_metadata')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['access_area_id', 'status']);
            $table->index(['access_area_id', 'user_id']);
            $table->index(['access_area_id', 'email']);
            $table->index(['access_area_id', 'subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        AccessGateSchema::builder()->dropIfExists('access_gate_grants');
    }
};
