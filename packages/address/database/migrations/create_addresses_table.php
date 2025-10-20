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
            $table->string('line1', 128)->index();
            $table->string('line2', 128)->nullable();
            $table->string('city', 64)->index();
            $table->string('state', 32)->index();
            $table->string('postal_code', 16)->index()->default('');
            $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete()->index();
            $table->boolean('default')->index()->default(0);
            $table->boolean('status')->index()->default(1);
            $table->json('meta')->nullable();
            $table->softDeletes();
            $table->userstamps();
            $table->timestamps();
            $table->index(['city', 'state', 'country_id']);
            $table->index(['state', 'postal_code', 'country_id']);
            $table->index(['line1', 'city', 'state', 'postal_code', 'country_id'], 'address_full_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
