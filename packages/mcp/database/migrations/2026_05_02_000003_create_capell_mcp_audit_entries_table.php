<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capell_mcp_audit_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mcp_token_id')->nullable()->constrained('capell_mcp_tokens')->nullOnDelete();
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
        Schema::dropIfExists('capell_mcp_audit_entries');
    }
};
