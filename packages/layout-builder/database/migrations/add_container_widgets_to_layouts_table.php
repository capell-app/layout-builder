<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('layouts', function (Blueprint $table): void {
            if (! Schema::hasColumn('layouts', 'containers')) {
                $table->json('containers')->nullable();
            }

            if (! Schema::hasColumn('layouts', 'widgets')) {
                $table->json('widgets')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('layouts', function (Blueprint $table): void {
            if (Schema::hasColumn('layouts', 'containers')) {
                $table->dropColumn('containers');
            }

            if (Schema::hasColumn('layouts', 'widgets')) {
                $table->dropColumn('widgets');
            }
        });
    }
};
