<?php

declare(strict_types=1);

namespace Capell\Tags\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class InstallCommand extends Command
{
    protected $description = 'Install tags package';

    protected $signature = 'capell:tags-install';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->call('vendor:publish', ['--tag' => 'capell-tags-config']);

        $this->publishMigrations();

        $this->call('migrate');

        $this->newLine();
        $this->info('Capell Tags installed successfully.');

        return self::SUCCESS;
    }

    private function publishMigrations(): bool
    {
        $migrations = [];

        if (! Schema::hasTable('tags') && ! Schema::hasTable('taggables')) {
            $migrations[] = base_path('vendor/spatie/laravel-tags/database/migrations/create_tag_tables.php.stub');
        }

        $migrations[] = __DIR__ . '/../../../database/migrations/alter_tags_table.php';

        $this->call('capell:publish-migrations', ['--items' => $migrations]);

        return true;
    }
}
