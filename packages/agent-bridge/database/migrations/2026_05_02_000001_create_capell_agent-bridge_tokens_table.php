<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('capell_agent-bridge_tokens')) {
            return;
        }

        Schema::create('capell_agent-bridge_tokens', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('token_hash', 64)->unique();
            $table->morphs('user');
            $table->json('scopes');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capell_agent-bridge_tokens');
    }
};
