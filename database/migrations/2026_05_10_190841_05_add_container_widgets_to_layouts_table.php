<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('layouts')) {
            return;
        }

        Schema::table('layouts', function (Blueprint $table): void {
            if (! Schema::hasColumn('layouts', 'containers')) {
                $table->json('containers')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('layouts')) {
            return;
        }

        Schema::table('layouts', function (Blueprint $table): void {
            if (Schema::hasColumn('layouts', 'containers')) {
                $table->dropColumn('containers');
            }

        });
    }
};
