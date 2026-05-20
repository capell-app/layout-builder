<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Console\Commands;

use Capell\LayoutBuilder\Support\CapellLayoutBuilderManager;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $description = 'Install layout builder package';

    protected $signature = 'capell:layout-builder-install';

    public function handle(): int
    {
        $this->publishMigrations();

        $this->newLine();
        $this->info('Capell Layout Builder installed successfully.');

        return self::SUCCESS;
    }

    private function publishMigrations(): void
    {
        $migrations = $this->migrations();

        if ($migrations === []) {
            return;
        }

        $this->call('capell:publish-migrations', ['--items' => $migrations]);
        $this->call('migrate');
    }

    /**
     * @return list<string>
     */
    private function migrations(): array
    {
        return array_map(
            static fn (string $migration): string => __DIR__ . '/../../../database/migrations/' . $migration . '.php',
            CapellLayoutBuilderManager::getMigrations(),
        );
    }
}
