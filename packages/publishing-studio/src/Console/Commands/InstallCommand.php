<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Console\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /** @var string */
    protected $description = 'Install publishing-studio package';

    /** @var string */
    protected $signature = 'capell:publishing-studio-install';

    public function handle(): int
    {
        if (! $this->publishMigrations()) {
            return self::FAILURE;
        }

        $this->call('migrate');

        $this->newLine();
        $this->info('Capell PublishingStudio installed successfully.');

        return self::SUCCESS;
    }

    private function publishMigrations(): bool
    {
        $migrations = [
            __DIR__ . '/../../../database/migrations/2026_04_20_000001_create_publishing-studio_table.php',
            __DIR__ . '/../../../database/migrations/2026_04_20_000002_create_versions_table.php',
            __DIR__ . '/../../../database/migrations/create_workspace_approvals_table.php',
            __DIR__ . '/../../../database/migrations/create_workspace_review_assignments_table.php',
            __DIR__ . '/../../../database/migrations/create_workspace_field_comments_table.php',
            __DIR__ . '/../../../database/migrations/create_preview_links_table.php',
            __DIR__ . '/../../../database/migrations/seed_bootstrap_workspace_version.php',
            __DIR__ . '/../../../database/migrations/z_add_workspace_columns_to_core_tables.php',
            __DIR__ . '/../../../database/migrations/z_add_workspace_id_to_import_sessions_table.php',
            __DIR__ . '/../../../database/migrations/z_add_workspace_id_to_external_tables.php',
        ];

        if ($this->call('capell:publish-migrations', ['--items' => $migrations]) !== self::SUCCESS) {
            return false;
        }

        return true;
    }
}
