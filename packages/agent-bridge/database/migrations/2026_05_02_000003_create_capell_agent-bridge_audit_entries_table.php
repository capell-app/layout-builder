<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('capell_agent-bridge_audit_entries')) {
            return;
        }

        Schema::create('capell_agent-bridge_audit_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('agent_bridge_token_id')->nullable()->constrained('capell_agent-bridge_tokens')->nullOnDelete();
            $table->morphs('user');
            $table->string('event');
            $table->string('capability_key')->nullable();
            $table->string('scope')->nullable();
            $table->json('payload')->nullable();
            $table->json('result')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capell_agent-bridge_audit_entries');
    }
};
