<?php

declare(strict_types=1);

namespace Capell\SeoTools\Console\Commands;

use Capell\Core\Support\Migration\MigrationFilesystemInterface;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'capell:assistant-install';

    public function __construct(private readonly MigrationFilesystemInterface $fileManager)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $migrations = __DIR__ . '/../../../database/migrations';
        if (! $this->fileManager->isDir($migrations)) {
            $this->error('Migrations directory does not exist.');

            return Command::FAILURE;
        }

        $this->call('capell:publish-migrations', [
            '--items' => [
                'create_ai_generation_histories_table',
            ],
            '--path' => $migrations,
        ]);

        $settings = __DIR__ . '/../../../database/settings';
        if (! $this->fileManager->isDir($settings)) {
            $this->error('Settings directory does not exist.');

            return Command::FAILURE;
        }

        $this->call('capell:publish-migrations', [
            '--type' => 'settings',
            '--items' => [
                'create_assistant_settings',
                '2026_04_18_000001_update_assistant_settings_add_ai_creator',
            ],
            '--path' => $settings,
        ]);

        $this->info('Migrations published successfully.');

        $this->call('migrate');

        $this->newLine();
        $this->info('Capell Assistant installed successfully.');

        return Command::SUCCESS;
    }
}
