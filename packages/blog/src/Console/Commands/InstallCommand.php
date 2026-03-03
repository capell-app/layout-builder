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

        $this->call('vendor:publish', ['--tag' => 'capell-blog-config']);

        $this->publishMigrations();

        $this->call('migrate');

        $this->callSilent('filament:assets');

        $this->newLine();
        $this->info('Capell Blog installed successfully.');

        return self::SUCCESS;
    }

    private function publishMigrations(): bool
    {
        $migrations = [
            base_path('vendor/spatie/laravel-tags/database/migrations/create_tag_tables.php.stub'),
            __DIR__ . '/../../../database/migrations/2026_01_02_000001_alter_tags_table.php',
        ];

        $this->call('capell:publish-migrations', ['--items' => $migrations]);

        return true;
    }
}
