<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('line1', 128);
            $table->string('line2', 128)->nullable();
            $table->string('city', 64)->nullable();
            $table->string('state', 32)->nullable();
            $table->string('postal_code', 16)->default('');
            $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->boolean('default')->default(0);
            $table->boolean('status')->default(1);
            $table->json('meta')->nullable();
            $table->softDeletes();
            $table->userstamps();
            $table->timestamps();

            // Composite indexes for common query patterns
            $table->index(['line1', 'postal_code', 'country_id'], 'address_part_index');
            $table->index(['line1', 'city', 'state', 'postal_code', 'country_id'], 'address_full_index');
            $table->index(['city', 'state', 'country_id']);
            $table->index(['state', 'postal_code', 'country_id']);

            // Indexes for foreign and status columns if needed for filtering
            $table->index('country_id');
            $table->index('status');
            $table->index('default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
