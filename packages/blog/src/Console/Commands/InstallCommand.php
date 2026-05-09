<?php

declare(strict_types=1);

namespace Capell\Blog\Console\Commands;

use Capell\Admin\Actions\AssignPermissionsToRole;
use Capell\Blog\Enums\ResourceEnum;
use Capell\Blog\Support\BlogModelRegistrar;
use Filament\Facades\Filament;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $description = 'Install blog package';

    protected $signature = 'capell:blog-install';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        BlogModelRegistrar::register();

        Filament::getDefaultPanel()
            ->resources(array_map(fn (ResourceEnum $resourceEnum) => $resourceEnum->value, ResourceEnum::cases()));

        AssignPermissionsToRole::run(resources: ResourceEnum::cases());

        $this->publishMigrations();

        $this->call('migrate');

        if (! app()->runningUnitTests()) {
            $this->callSilent('filament:assets');
        }

        $this->newLine();
        $this->info('Capell Blog installed successfully.');

        return self::SUCCESS;
    }

    private function publishMigrations(): bool
    {
        $migrations = [
            __DIR__ . '/../../../database/migrations/create_articles_table.php',
        ];

        $this->call('capell:publish-migrations', ['--items' => $migrations]);

        return true;
    }
}
