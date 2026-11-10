<?php

declare(strict_types=1);

use Capell\Core\Support\TableName;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable(TableName::resolve('layouts'))) {
            return;
        }

        Schema::table(TableName::resolve('layouts'), function (Blueprint $table): void {
            if (! Schema::hasColumn(TableName::resolve('layouts'), 'containers')) {
                $table->json('containers')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable(TableName::resolve('layouts'))) {
            return;
        }

        Schema::table(TableName::resolve('layouts'), function (Blueprint $table): void {
            if (Schema::hasColumn(TableName::resolve('layouts'), 'containers')) {
                $table->dropColumn('containers');
            }

        });
    }
};
