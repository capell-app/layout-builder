<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('widgets')) {
            return;
        }

        Schema::table('widgets', function (Blueprint $table): void {
            $table->dateTime('visible_from')->nullable()->change();
            $table->dateTime('visible_until')->nullable()->change();
        });
    }

    public function down(): void
    {
        // DATETIME is required for the publication sentinel and cannot be safely reverted.
    }
};
