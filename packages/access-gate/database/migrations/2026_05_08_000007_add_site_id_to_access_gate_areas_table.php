<?php

declare(strict_types=1);

use Capell\AccessGate\Support\AccessGateSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        AccessGateSchema::builder()->table('access_gate_areas', function (Blueprint $table): void {
            $table
                ->unsignedBigInteger('site_id')
                ->nullable()
                ->after('id')
                ->index();
        });
    }

    public function down(): void
    {
        AccessGateSchema::builder()->table('access_gate_areas', function (Blueprint $table): void {
            $table->dropColumn('site_id');
        });
    }
};
