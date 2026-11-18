<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Actions\GrantLayoutBuilderDefaultRolePermissionsAction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = config('permission.table_names');
        if (! is_array($tables)) {
            return;
        }
        foreach (['permissions', 'roles', 'role_has_permissions'] as $key) {
            $table = $tables[$key] ?? null;
            if (! is_string($table) || ! Schema::hasTable($table)) {
                return;
            }
        }

        GrantLayoutBuilderDefaultRolePermissionsAction::run();
    }

    public function down(): void
    {
        // Preserve grants an operator may now rely on.
    }
};
