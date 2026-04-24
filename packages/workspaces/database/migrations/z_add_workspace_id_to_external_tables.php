<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables owned by external packages (Spatie MediaLibrary, Spatie Tags,
     * Spatie LaravelSettings) that still need a `workspace_id` column so they
     * can participate in the workspace / version system. Core-owned tables
     * declare the column in their own create migrations.
     *
     * The column defaults to `0` (the "live" sentinel) so rows that existed
     * before the workspace rollout automatically belong to the live version.
     */
    public function up(): void
    {
        foreach ($this->externalTables() as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (! Schema::hasColumn($tableName, 'workspace_id')) {
                    $table->unsignedBigInteger('workspace_id')->default(0)->index();
                }

                if (! Schema::hasColumn($tableName, 'shadowed_by_workspace_id')) {
                    $table->unsignedBigInteger('shadowed_by_workspace_id')->default(0)->index();
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->externalTables() as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (Schema::hasColumn($tableName, 'shadowed_by_workspace_id')) {
                    $table->dropIndex([sprintf('%s_shadowed_by_workspace_id_index', $tableName)]);
                    $table->dropColumn('shadowed_by_workspace_id');
                }

                if (Schema::hasColumn($tableName, 'workspace_id')) {
                    $table->dropIndex([sprintf('%s_workspace_id_index', $tableName)]);
                    $table->dropColumn('workspace_id');
                }
            });
        }
    }

    /**
     * @return array<int, string>
     */
    private function externalTables(): array
    {
        return [
            'media',
            'settings',
        ];
    }
};
