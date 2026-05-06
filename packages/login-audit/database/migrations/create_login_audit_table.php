<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('login-audit.table_name', 'login_audit');

        if (Schema::hasTable($tableName)) {
            return;
        }

        Schema::create($tableName, function (Blueprint $table): void {
            $table->id();
            $table->morphs('authenticatable');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_id')->nullable()->index();
            $table->string('device_name')->nullable();
            $table->boolean('is_trusted')->default(false);
            $table->timestamp('login_at')->nullable();
            $table->timestamp('logout_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->boolean('login_successful')->default(false);
            $table->boolean('cleared_by_user')->default(false);
            $table->json('location')->nullable();
            $table->boolean('is_suspicious')->default(false);
            $table->string('suspicious_reason')->nullable();
            $table->timestamps();

            $table->index(
                ['authenticatable_type', 'authenticatable_id', 'login_at'],
                'authenticatable_login_at_index',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('login-audit.table_name', 'login_audit'));
    }
};
