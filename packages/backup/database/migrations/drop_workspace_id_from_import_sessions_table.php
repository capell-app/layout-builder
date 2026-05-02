<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('import_sessions')) {
            return;
        }

        if (Schema::hasColumn('import_sessions', 'workspace_id')) {
            Schema::table('import_sessions', function (Blueprint $table): void {
                $table->dropColumn('workspace_id');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('import_sessions')) {
            return;
        }

        if (! Schema::hasColumn('import_sessions', 'workspace_id')) {
            Schema::table('import_sessions', function (Blueprint $table): void {
                $table->unsignedBigInteger('workspace_id')->nullable()->after('working_dir');
            });
        }
    }
};
