<?php

declare(strict_types=1);

namespace Capell\Workspaces\Console\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /** @var string */
    protected $description = 'Install workspaces package';

    /** @var string */
    protected $signature = 'capell:workspaces-install';

    public function handle(): int
    {
        $this->publishMigrations();

        $this->call('migrate');

        $this->newLine();
        $this->info('Capell Workspaces installed successfully.');

        return self::SUCCESS;
    }

    private function publishMigrations(): bool
    {
        $migrations = [
            __DIR__ . '/../../../database/migrations/create_workspaces_table.php',
            __DIR__ . '/../../../database/migrations/create_workspace_approvals_table.php',
            __DIR__ . '/../../../database/migrations/create_workspace_review_assignments_table.php',
            __DIR__ . '/../../../database/migrations/create_workspace_field_comments_table.php',
            __DIR__ . '/../../../database/migrations/create_preview_links_table.php',
            __DIR__ . '/../../../database/migrations/create_versions_table.php',
            __DIR__ . '/../../../database/migrations/seed_bootstrap_workspace_version.php',
            __DIR__ . '/../../../database/migrations/z_add_workspace_columns_to_core_tables.php',
            __DIR__ . '/../../../database/migrations/z_add_workspace_id_to_import_sessions_table.php',
            __DIR__ . '/../../../database/migrations/z_add_workspace_id_to_external_tables.php',
        ];

        $this->call('capell:publish-migrations', ['--items' => $migrations]);

        return true;
    }
}
