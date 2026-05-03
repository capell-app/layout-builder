<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('capell_mcp_confirmations')) {
            return;
        }

        Schema::create('capell_mcp_confirmations', function (Blueprint $table): void {
            $table->id();
            $table->string('token', 64)->unique();
            $table->foreignId('mcp_token_id')->constrained('capell_mcp_tokens')->cascadeOnDelete();
            $table->morphs('user');
            $table->string('capability_key');
            $table->string('scope');
            $table->string('payload_hash', 64);
            $table->json('payload');
            $table->json('preview');
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capell_mcp_confirmations');
    }
};
