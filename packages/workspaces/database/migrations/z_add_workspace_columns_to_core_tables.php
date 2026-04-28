<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private array $tablesToUpdate = [
        'pages',
        'navigations',
        'sites',
        'site_domains',
        'types',
        'themes',
        'layouts',
        'languages',
        'translations',
        'page_urls',
        'asset_relations',
    ];

    public function up(): void
    {
        foreach ($this->tablesToUpdate as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $schema): void {
                if (! Schema::hasColumn($schema->getTable(), 'workspace_id')) {
                    $schema->unsignedBigInteger('workspace_id')->default(0)->index();
                }

                if (! Schema::hasColumn($schema->getTable(), 'shadowed_by_workspace_id')) {
                    $schema->unsignedBigInteger('shadowed_by_workspace_id')->default(0)->index();
                }
            });
        }

        // The translations table has a unique constraint on (language_id, translatable_type,
        // translatable_id). Workspace-scoped clones share the same translatable_id as their
        // live counterpart, so workspace_id must be part of the key to avoid conflicts.
        if (Schema::hasTable('translations')) {
            Schema::table('translations', function (Blueprint $schema): void {
                $schema->dropUnique('translations_key_unique');
                $schema->unique(
                    ['language_id', 'translatable_type', 'translatable_id', 'workspace_id'],
                    'translations_workspace_key_unique',
                );
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('translations')) {
            Schema::table('translations', function (Blueprint $schema): void {
                $schema->dropUnique('translations_workspace_key_unique');
                $schema->unique(
                    ['language_id', 'translatable_type', 'translatable_id'],
                    'translations_key_unique',
                );
            });
        }

        foreach ($this->tablesToUpdate as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $schema): void {
                if (Schema::hasColumn($schema->getTable(), 'workspace_id')) {
                    // Index naming convention: {table_name}_{column_name}_index
                    $schema->dropIndex([sprintf('%s_workspace_id_index', $schema->getTable())]);
                    $schema->dropColumn('workspace_id');
                }

                if (Schema::hasColumn($schema->getTable(), 'shadowed_by_workspace_id')) {
                    // Index naming convention: {table_name}_{column_name}_index
                    $schema->dropIndex([sprintf('%s_shadowed_by_workspace_id_index', $schema->getTable())]);
                    $schema->dropColumn('shadowed_by_workspace_id');
                }
            });
        }
    }
};
